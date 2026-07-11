#!/usr/bin/env bash
set -euo pipefail

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS_ON_BOOT:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec /usr/bin/supervisord -c /etc/supervisord.conf
