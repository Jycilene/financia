FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_pgsql
COPY . /var/www/html/FROM php:8.1-apache

# Instala a biblioteca do PostgreSQL necessária
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY . /var/www/html/