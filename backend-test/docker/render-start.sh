#!/bin/sh
set -eu

cd /var/www/html

PORT="${PORT:-10000}"
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-60}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "base64:" ]; then
    php artisan key:generate --force --no-interaction
fi

if [ "${DB_CONNECTION:-}" = "mysql" ]; then
    echo "Waiting for MySQL to accept connections..."
    elapsed=0

    while ! php artisan db:show --no-interaction >/dev/null 2>&1; do
        elapsed=$((elapsed + 2))

        if [ "${elapsed}" -ge "${DB_WAIT_TIMEOUT}" ]; then
            echo "MySQL was not ready within ${DB_WAIT_TIMEOUT} seconds."
            exit 1
        fi

        sleep 2
    done
fi

php artisan migrate --force --no-interaction

exec apache2-foreground
