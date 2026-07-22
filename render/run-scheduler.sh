#!/usr/bin/env bash
set -euo pipefail

while true; do
    php /var/www/html/artisan schedule:run --no-interaction
    sleep 60
done
