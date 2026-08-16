#!/usr/bin/env bash
#
# First-boot provisioning for the self-bootstrapping app image.
# Web role provisions (env, app key, MinIO bucket, migrations + seed) and then
# defers to the Sail runtime entrypoint, which serves PHP. The queue worker
# skips provisioning and only starts the queue worker.
set -euo pipefail

ROLE="${APP_ROLE:-web}"
STATE_DIR="${DOCKER_STATE_DIR:-/var/www/html/storage/.docker}"
APP_DIR="/var/www/html"

chown -R "${WWWUSER:-1000}:${WWWGROUP:-1000}" "${APP_DIR}/storage" >/dev/null 2>&1 || true

# MinIO is reached as a single host by both the app SDK and the browser's
# signed URLs (see AWS_ENDPOINT in .env.example). host.docker.internal resolves
# on both the container and the host on Docker Desktop; on native Linux the
# operator points AWS_ENDPOINT/AWS_URL at their LAN IP instead. Nothing to patch
# here — the value simply comes through .env.
log() { echo "[provision] $*"; }

wait_for_pgsql() {
    log "waiting for PostgreSQL..."
    for i in $(seq 1 60); do
        if PGPASSWORD="${DB_PASSWORD:-password}" pg_isready -h "${DB_HOST:-pgsql}" \
            -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-sail}" -q; then
            log "PostgreSQL is ready."
            return 0
        fi
        sleep 2
    done
    log "PostgreSQL did not become ready." >&2
    exit 1
}

wait_for_minio() {
    local host="${MINIO_HOST:-host.docker.internal}"
    log "waiting for MinIO at http://${host}:${AWS_PORT:-9000}..."
    for i in $(seq 1 60); do
        if curl -fsS "http://${host}:${AWS_PORT:-9000}/minio/health/live" >/dev/null 2>&1; then
            log "MinIO is ready."
            return 0
        fi
        sleep 2
    done
    log "MinIO did not become ready." >&2
    exit 1
}

ensure_env() {
    cd "${APP_DIR}"
    if [ ! -f ".env" ]; then
        log "no .env found; provisioning from .env.example"
        cp .env.example .env
        php artisan key:generate --force
    fi
}

provision() {
    local host="${MINIO_HOST:-host.docker.internal}"
    local bucket="${AWS_BUCKET:-construction-ops}"

    php artisan migrate --force

    log "ensuring MinIO bucket '${bucket}' exists"
    until mc alias set "local" "http://${host}:${AWS_PORT:-9000}" \
        "${MINIO_ROOT_USER:-sail}" "${MINIO_ROOT_PASSWORD:-password}" >/dev/null 2>&1; do
        sleep 2
    done
    mc mb --ignore-existing "local/${bucket}" >/dev/null 2>&1

    mkdir -p "${STATE_DIR}"
    if [ ! -f "${STATE_DIR}/.seeded" ]; then
        log "seeding database with demo data"
        php artisan db:seed --force
        touch "${STATE_DIR}/.seeded"
    fi
}

# Both roles need a valid .env (DB/Redis/AWS/queue config); only the web role
# migrates, seeds, and ensures the MinIO bucket.
ensure_env

if [ "${ROLE}" = "worker" ]; then
    log "booting queue worker"
    exec /usr/local/bin/start-container "${@}"
fi

wait_for_pgsql
wait_for_minio
provision

log "booting web server"
exec /usr/local/bin/start-container