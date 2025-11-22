# syntax=docker/dockerfile:1

FROM php:8.3-fpm

ARG DEBIAN_FRONTEND=noninteractive

# Install dependencies and PHP extensions (intl, mbstring, mysqli, ldap)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libonig-dev libzip-dev libldap2-dev libpng-dev \
    libxml2-dev zlib1g-dev libssl-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl mbstring mysqli \
    && docker-php-ext-configure ldap --with-ldap=/usr \
    && docker-php-ext-install ldap \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Ensure writable directories
RUN mkdir -p writable/cache writable/logs writable/session \
    && chown -R www-data:www-data writable \
    && chmod -R 775 writable

# Environment
ENV PHP_MEMORY_LIMIT=256M

CMD ["php-fpm"]
