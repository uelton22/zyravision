###############################################
# ZyraVision — PHP Application Image
#
# Multi-stage build otimizado:
#   1. builder  — compila extensoes PHP (descartado)
#   2. runtime  — imagem final limpa (~180 MB)
#
# Servicos: app, queue, scheduler, reverb
# Usa UMA unica imagem para todos.
###############################################

# ═══════════════════════════════════════════════
# Stage 1: Builder — compila extensoes PHP
# ═══════════════════════════════════════════════
FROM php:8.4-fpm-alpine AS builder

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev

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
    sockets \
    ftp

RUN pecl install redis \
    && docker-php-ext-enable redis

# ═══════════════════════════════════════════════
# Stage 2: Runtime — imagem final limpa
# ═══════════════════════════════════════════════
FROM php:8.4-fpm-alpine AS base

# Apenas bibliotecas runtime (sem headers/compiladores)
RUN apk add --no-cache \
    curl \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    icu-libs \
    oniguruma \
    libpq

# Copiar extensoes compiladas do builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ═══════════════════════════════════════════════
# Target: Development
# ═══════════════════════════════════════════════
FROM base AS development

USER www-data

# ═══════════════════════════════════════════════
# Target: Production
# ═══════════════════════════════════════════════
FROM base AS production

COPY --chown=www-data:www-data . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && rm -rf /root/.composer/cache

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

USER www-data
