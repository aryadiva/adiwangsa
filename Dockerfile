# syntax=docker/dockerfile:1

# Stage 1: Sail PHP 8.5 runtime base.
# Mirrors docker/8.5/Dockerfile — keep the two in sync on Sail bumps.
# Inlined here (instead of a separate php-base compose service that tags
# sail-8.5/app:latest) so the app build is fully self-contained: no race
# between a separate base-image build and the consumer (laravel.test /
# worker) builds, and no external image lookup that can fall through to a
# registry pull with a misleading "no such host" error.

FROM ubuntu:24.04 AS base

LABEL maintainer="Taylor Otwell"

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG NODE_VERSION=24
ARG MYSQL_CLIENT="mysql-client"
ARG POSTGRES_VERSION=18
ARG PHP_EXTENSIONS=""

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV LANG=C.UTF-8
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"
ENV PLAYWRIGHT_BROWSERS_PATH=0

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin fswatch ffmpeg nano \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y \
        libgd3 \
        php8.5-cli \
        php8.5-dev \
        php8.5-pgsql \
        php8.5-sqlite3 \
        php8.5-gd \
        php8.5-curl \
        php8.5-mongodb \
        php8.5-imap \
        php8.5-mysql \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-zip \
        php8.5-bcmath \
        php8.5-soap \
        php8.5-intl \
        php8.5-readline \
        php8.5-ldap \
        php8.5-msgpack \
        php8.5-igbinary \
        php8.5-redis \
        php8.5-swoole \
        php8.5-memcached \
        php8.5-pcov \
        php8.5-imagick \
        php8.5-xdebug \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm pnpm bun corepack \
    && corepack enable \
    && corepack prepare yarn@stable --activate \
    && npx -y playwright install-deps \
    && curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt noble-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y $MYSQL_CLIENT \
    && apt-get install -y postgresql-client-$POSTGRES_VERSION \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN if [ -n "$PHP_EXTENSIONS" ]; then \
    apt-get update \
    && apt-get install -y $(for ext in $PHP_EXTENSIONS; do echo "php8.5-$ext"; done) \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*; \
fi

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.5

RUN userdel -r ubuntu
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail
RUN git config --global --add safe.directory /var/www/html

COPY docker/8.5/start-container /usr/local/bin/start-container
COPY docker/8.5/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/8.5/php.ini /etc/php/8.5/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

# Stage 2: app layer — install runtime + app dependencies against a locked
# dependency graph (before the source COPY) so layer caching is efficient.

FROM base AS runtime

USER root

WORKDIR /var/www/html

ARG WWWUSER=1000
ARG WWWGROUP=1000

# MinIO client, used at first boot to create the configured bucket.
RUN curl -sSL https://dl.min.io/client/mc/release/linux-amd64/mc -o /usr/local/bin/mc \
    && chmod +x /usr/local/bin/mc

# Install Composer + Node deps from the lockfiles first (best cache hit on rebuilds).
# On a fresh device with no layer cache, composer downloads ~9000 classes; on
# slow/unstable links the default 300s process-timeout fires mid-download, and
# unauthenticated GitHub API calls hit the 60/hr rate limit → exit 100.
# Mitigations: 900s timeout, 3-attempt retry loop, optional GITHUB_TOKEN ARG.
COPY composer.json composer.lock package.json package-lock.json ./

ARG GITHUB_TOKEN=""
ENV COMPOSER_PROCESS_TIMEOUT=900 \
    COMPOSER_NO_INTERACTION=1
RUN if [ -n "$GITHUB_TOKEN" ]; then \
        composer config --global github-oauth.github.com "$GITHUB_TOKEN"; \
    fi \
    && for i in 1 2 3; do \
        composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts \
            && break \
            || { echo "composer install attempt $i failed, retrying in 5s..."; sleep 5; }; \
    done \
    && npm ci --no-audit --no-fund --fetch-retries=5 --fetch-timeout=300000

# Copy the full application and finish the assembly.
COPY . .

RUN npm run build \
    && mkdir -p -m 775 storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/logs storage/app/docker \
    && composer dump-autoload --optimize --no-dev \
    && chown -R "$WWWUSER:$WWWGROUP" storage bootstrap/cache /var/www/html/.env.example

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

EXPOSE 80/tcp

# Provisioning runs as root (chown, keygen, migrate, mc bucket), then drops to
# the Sail runtime's entrypoint for the actual PHP serve / queue supervisor.
ENTRYPOINT ["app-entrypoint"]
