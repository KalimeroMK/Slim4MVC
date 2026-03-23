# Stage 1: Build dependencies
FROM php:8.4-fpm-alpine AS builder

# Install system dependencies
RUN apk add --no-cache \
    git curl nano unzip bash zip \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libxml2-dev oniguruma-dev libzip-dev \
    icu-dev g++ make autoconf

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd pdo_mysql mbstring exif pcntl bcmath zip intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Ensure `www-data` user exists
RUN getent passwd www-data || adduser -D -u 1000 -G www-data www-data

# Stage 2: Final image (Minimal)
FROM php:8.4-fpm-alpine

# Install runtime dependencies + Redis client for Queue
RUN apk add --no-cache libpng libjpeg freetype libzip icu

# Copy built dependencies from builder
COPY --from=builder /usr/local /usr/local
COPY --from=builder /usr/bin/composer /usr/bin/composer

# Install Redis extension via PECL (needed for Queue)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Set working directory
WORKDIR /var/www/html

# Ensure `www-data` user exists
RUN getent passwd www-data || adduser -D -u 1000 -G www-data www-data

# Create storage directories with proper permissions
RUN mkdir -p /var/www/html/storage/cache/view \
    /var/www/html/storage/logs \
    /var/www/html/storage/queue \
    && chown -R www-data:www-data /var/www/html/storage

# Set user
USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php -v || exit 1

# Start PHP-FPM
CMD ["php-fpm"]
