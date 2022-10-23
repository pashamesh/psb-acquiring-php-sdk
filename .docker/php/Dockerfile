ARG PHP_VERSION=7.4
FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add --no-cache --virtual .persistent-deps \
    # etc \
    bash \
    && rm -rf /tmp/* /var/cache/apk/*

WORKDIR /app

# Install Composer
ENV COMPOSER_NO_INTERACTION 1
ENV COMPOSER_MAX_PARALLEL_HTTP=50
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
