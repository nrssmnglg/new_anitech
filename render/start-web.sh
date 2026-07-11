#!/usr/bin/env bash
set -euo pipefail

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

php artisan migrate --seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec /usr/bin/supervisord -c /etc/supervisord.conf
