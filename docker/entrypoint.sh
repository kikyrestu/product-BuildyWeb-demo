#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ "${RUN_PACKAGE_DISCOVER:-true}" = "true" ]; then
    php artisan package:discover --ansi --no-interaction
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
