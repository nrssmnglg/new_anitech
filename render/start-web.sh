#!/usr/bin/env bash
set -euo pipefail

: "${APP_KEY:?APP_KEY is required}"
: "${APP_URL:?APP_URL is required}"
: "${DB_CONNECTION:?DB_CONNECTION is required}"

if [[ -z "${DB_URL:-}" && -z "${MYSQL_PUBLIC_URL:-}" ]]; then
    : "${DB_HOST:?DB_HOST is required when DB_URL and MYSQL_PUBLIC_URL are not set}"
    : "${DB_PORT:?DB_PORT is required when DB_URL and MYSQL_PUBLIC_URL are not set}"
    : "${DB_DATABASE:?DB_DATABASE is required when DB_URL and MYSQL_PUBLIC_URL are not set}"
    : "${DB_USERNAME:?DB_USERNAME is required when DB_URL and MYSQL_PUBLIC_URL are not set}"
    : "${DB_PASSWORD:?DB_PASSWORD is required when DB_URL and MYSQL_PUBLIC_URL are not set}"
fi

export PORT="${PORT:-10000}"

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

envsubst '${PORT}' \
    < /etc/nginx/templates/default.conf.template \
    > /etc/nginx/http.d/default.conf

if [[ "${RUN_MIGRATIONS_ON_BOOT:-false}" == "true" ]]; then
    php artisan migrate --force --no-interaction
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec /usr/bin/supervisord -c /etc/supervisord.conf
