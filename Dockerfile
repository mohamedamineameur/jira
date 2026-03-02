# ─────────────────────────────────────────────────────────────────────────────
# Stage 1 – Node: compile frontend assets (Vite)
# ─────────────────────────────────────────────────────────────────────────────
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --prefer-offline

COPY vite.config.js eslint.config.js ./
COPY resources/ resources/
COPY public/ public/

RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
# Stage 2 – Composer: install PHP dependencies (no dev)
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts

COPY . .
RUN mkdir -p bootstrap/cache storage/framework/{sessions,views,cache} storage/logs \
    && composer dump-autoload --optimize --no-dev --no-scripts

#------------------------------------------------------------------------------
# ─────────────────────────────────────────────────────────────────────────────
# Stage 3 – Final: PHP 8.3-FPM + Nginx served by Supervisor
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine

LABEL maintainer="Agilify <contact@agilify.app>"
LABEL org.opencontainers.image.title="Agilify"
LABEL org.opencontainers.image.description="Agile project management for modern teams"
LABEL org.opencontainers.image.source="https://github.com/aminefaw89/agilify"

# System dependencies + PHP extensions
RUN apk add --no-cache \
        nginx \
        supervisor \
        mysql-client \
        curl \
        zip \
        unzip \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        opcache \
        gd \
        zip \
        intl \
        pcntl \
    && rm -rf /var/cache/apk/*

# Copy runtime configs
COPY docker/nginx.conf      /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini         /usr/local/etc/php/conf.d/app.ini
COPY docker/entrypoint.sh   /entrypoint.sh
RUN chmod +x /entrypoint.sh

# App
WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

# Copy compiled assets from node-builder
COPY --from=node-builder   --chown=www-data:www-data /app/public/build  public/build

# Copy vendor from composer-builder
COPY --from=composer-builder --chown=www-data:www-data /app/vendor vendor/

# Storage & cache permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
             storage/logs \
             bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Nginx needs to write its PID as root initially; www-data handles PHP
RUN chown -R www-data:www-data /var/lib/nginx /var/log/nginx /run \
    && touch /var/log/nginx/access.log /var/log/nginx/error.log \
    && chown www-data:www-data /var/log/nginx/access.log /var/log/nginx/error.log

EXPOSE 80 8000

ENTRYPOINT ["/entrypoint.sh"]
