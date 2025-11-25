# syntax=docker/dockerfile:1

FROM php:8.3-fpm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl ca-certificates gnupg libicu-dev libonig-dev libzip-dev libldap2-dev libpng-dev \
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

# --- Node.js & npm (for icon assets via npm) ---
# Install Node.js 20.x (NodeSource)
RUN set -eux; \
    ARCH="$(dpkg --print-architecture)"; \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -; \
    apt-get update; \
    apt-get install -y --no-install-recommends nodejs; \
    rm -rf /var/lib/apt/lists/*

# Copy npm manifests and scripts first to leverage Docker laydcer caching
COPY package.json package-lock.json ./
COPY scripts ./scripts

# Ensure public directory exists before running npm postinstall (it syncs assets there)
COPY public ./public



# Install npm dependencies (runs postinstall to sync icon assets)
# Use `npm install` instead of `npm ci` because the lockfile is not curated in repo
RUN npm install --no-audit --no-fund

# Snapshot built front-end assets into the image so they are always available
RUN mkdir -p /opt/toolpages-assets/vendor \
    && cp -r ./public/assets/vendor/* /opt/toolpages-assets/vendor/ || true

# Now copy the rest of your application code, NOT vendor, NOT system
COPY app ./app
COPY writable ./writable
COPY spark ./spark

# Entrypoint to sync prebuilt assets into mounted public/ at runtime (if missing)
COPY infra/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permissions
RUN chown -R www-data:www-data ./writable \
    && chmod -R 775 ./writable

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]