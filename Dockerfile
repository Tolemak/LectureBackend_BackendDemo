FROM php:8.2-fpm

RUN apt-get update -y && apt-get install -y \
      wait-for-it git zip unzip ngrep \
    && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache

RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && pecl install mongodb-1.18.0 && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY ./ ./
