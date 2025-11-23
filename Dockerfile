# syntax=docker/dockerfile:1

FROM php:8.3-fpm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libonig-dev libzip-dev libldap2-dev libpng-dev \
    libxml2-dev zlib1g-dev libssl-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl mbstring mysqli \
    && docker-php-ext-configure ldap --with-ldap=/usr \
    && docker-php-ext-install ldap \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# COPY ONLY composer files first
COPY composer.json composer.lock ./

# Install framework inside container
RUN composer install --no-interaction --no-progress

# Now copy ONLY your application code, NOT vendor, NOT system
COPY app ./app
COPY public ./public
COPY writable ./writable
COPY spark ./spark

# Permissions
RUN chown -R www-data:www-data ./writable \
    && chmod -R 775 ./writable

CMD ["php-fpm"]