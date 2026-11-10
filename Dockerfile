FROM php:8.1-apache

# Instala as dependências de desenvolvimento do PostgreSQL e compila a extensão
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql

COPY . /var/www/html/