FROM composer:2 AS vendor

WORKDIR /app

COPY backend-test/ ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip zip libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY backend-test/ ./
COPY --from=vendor /app/vendor ./vendor
COPY backend-test/docker/render-start.sh /usr/local/bin/render-start
COPY backend-test/docker/apache-laravel.conf /etc/apache2/conf-available/laravel.conf

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
    && a2enconf laravel \
    && chmod +x /usr/local/bin/render-start \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && php artisan package:discover --ansi 2>/dev/null || true

EXPOSE 10000

CMD ["render-start"]
