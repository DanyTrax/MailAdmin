FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends whois \
 && rm -rf /var/lib/apt/lists/*

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && a2enmod rewrite

# Apache's workers must run as root so PHP can write docker-mailserver's
# config files (postfix-accounts.cf, postfix-virtual.cf, dovecot-quotas.cf)
# which are owned by root:root inside the shared mailconfig volume.
RUN sed -ri 's/^export APACHE_RUN_USER=.*/export APACHE_RUN_USER=root/'   /etc/apache2/envvars \
 && sed -ri 's/^export APACHE_RUN_GROUP=.*/export APACHE_RUN_GROUP=root/' /etc/apache2/envvars

COPY public/ /var/www/html/public/
COPY src/    /var/www/html/src/

RUN chown -R root:root /var/www/html
