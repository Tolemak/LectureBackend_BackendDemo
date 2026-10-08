FROM php:8.3-apache

RUN apt-get update -y && apt-get install -y --no-install-recommends \
      git zip unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache

RUN pecl install mongodb-2.5.2 && docker-php-ext-enable mongodb

ARG INSTALL_XDEBUG=0
RUN if [ "$INSTALL_XDEBUG" = "1" ]; then \
      pecl install xdebug && docker-php-ext-enable xdebug; \
    fi

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

RUN mkdir -p /app/var && chown -R www-data:www-data /app/var

WORKDIR /app
COPY --chown=www-data:www-data ./ ./

USER www-data
