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
FROM php:8.3-fpm-alpine

# Install runtime dependencies
RUN apk add --no-cache libpng libjpeg freetype libzip icu

# Copy built dependencies from builder
COPY --from=builder /usr/local /usr/local
COPY --from=builder /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Ensure `www-data` user exists
RUN getent passwd www-data || adduser -D -u 1000 -G www-data www-data

# Set user
USER www-data

# Start PHP-FPM
CMD ["php-fpm"]
