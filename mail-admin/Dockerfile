FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends whois \
 && rm -rf /var/lib/apt/lists/*

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && a2enmod rewrite

COPY public/ /var/www/html/public/
COPY src/    /var/www/html/src/

RUN chown -R www-data:www-data /var/www/html
