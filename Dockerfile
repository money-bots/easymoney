FROM php:8.1-fpm-alpine

RUN apk update \
    && apk upgrade curl \
    && apk upgrade xz \
    && apk add \
    autoconf \
    make \
    g++ \
    oniguruma-dev \
    libzip-dev \
    libpng-dev \
    icu-dev \
    libpq-dev \
    libxml2-dev \
    curl-dev \
    git \
    bash \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mbstring zip gd exif xml bcmath pcntl sockets soap

COPY --from=composer:2.3 /usr/bin/composer /usr/local/bin/composer
