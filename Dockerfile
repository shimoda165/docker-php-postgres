# syntax=docker/dockerfile:1

FROM composer:lts as deps
WORKDIR /lib
RUN --mount=type=bind,source=./composer.json,target=composer.json \
    --mount=type=bind,source=./composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-interaction

FROM php:8.5-apache as final
RUN apt-get update && apt-get install -y \
        libpq-dev \
        git \
        unzip \
    && docker-php-ext-install pdo_pgsql pgsql
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --from=deps lib/vendor/ /var/www/html/lib/vendor
COPY ./src /var/www/html
