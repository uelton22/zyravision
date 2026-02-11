FROM php:8.4-fpm-alpine AS base

# System dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    linux-headers \
    ffmpeg \
    $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    sockets

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- Development target ---
FROM base AS development

# Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

USER www-data

# --- Production target ---
FROM base AS production

# Copy application
COPY --chown=www-data:www-data . /var/www/html

# Install dependencies (no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Optimize Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

USER www-data
