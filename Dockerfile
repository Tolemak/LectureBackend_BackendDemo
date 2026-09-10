FROM php:8.3-apache

RUN apt-get update -y && apt-get install -y --no-install-recommends \
      git zip unzip ngrep \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache

RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && pecl install mongodb-1.21.0 && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

WORKDIR /app
COPY ./ ./
