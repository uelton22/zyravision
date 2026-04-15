###############################################
# ZyraVision — FFmpeg Transcoder Worker
#
# Multi-stage build otimizado:
#   1. builder  — compila extensoes PHP (descartado)
#   2. runtime  — PHP-CLI + FFmpeg (~350 MB)
#
# Extensoes minimas: apenas o necessario para
# rodar o queue worker com acesso ao banco e Redis.
###############################################

# ═══════════════════════════════════════════════
# Stage 1: Builder — compila extensoes PHP
# ═══════════════════════════════════════════════
FROM php:8.4-cli-alpine AS builder

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev

RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    pcntl \
    bcmath \
    zip \
    intl \
    opcache \
    sockets

RUN pecl install redis \
    && docker-php-ext-enable redis

# ═══════════════════════════════════════════════
# Stage 2: Runtime — imagem final com FFmpeg
# ═══════════════════════════════════════════════
FROM php:8.4-cli-alpine AS runtime

RUN apk add --no-cache \
    curl \
    ffmpeg \
    libzip \
    icu-libs \
    oniguruma \
    libpq

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD ffmpeg -version > /dev/null 2>&1 && pgrep -f "transcoder:worker" > /dev/null 2>&1

CMD ["php", "artisan", "transcoder:worker", "--verbose"]
