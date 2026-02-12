###############################################
# ZyraVision — FFmpeg Transcoder Worker
#
# Container dedicado para transcodificacao de video.
# Roda processos FFmpeg gerenciados por um daemon Laravel.
# Escalavel via deploy.replicas no docker-compose.
###############################################

FROM php:8.4-cli-alpine AS base

# System dependencies (FFmpeg + PHP minimal para artisan)
RUN apk add --no-cache \
    curl \
    ffmpeg \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    linux-headers \
    autoconf \
    gcc \
    g++ \
    make

# PHP extensions (minimal set for queue worker)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    sockets

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Health check: verify FFmpeg is available and daemon is running
HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD ffmpeg -version > /dev/null 2>&1 && pgrep -f "transcoder:worker" > /dev/null 2>&1

# Default command: run the transcoder worker daemon
CMD ["php", "artisan", "transcoder:worker", "--verbose"]
