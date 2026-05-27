FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY backend/ ./

RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["apache2-foreground"]
