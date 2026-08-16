# Stage 1: install runtime + app dependencies against a locked dependency graph
# (before the source COPY) so layer caching is efficient.

FROM sail-8.5/app AS runtime

USER root

WORKDIR /var/www/html

ARG WWWUSER=1000
ARG WWWGROUP=1000

# MinIO client, used at first boot to create the configured bucket.
RUN curl -sSL https://dl.min.io/client/mc/release/linux-amd64/mc -o /usr/local/bin/mc \
    && chmod +x /usr/local/bin/mc

# Install Composer + Node deps from the lockfiles first (best cache hit on rebuilds).
COPY composer.json composer.lock package.json package-lock.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && npm ci --no-audit --no-fund

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