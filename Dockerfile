# Build stage - install dependencies
FROM php:8.4-fpm-bookworm AS builder

# Install system dependencies for Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions needed for composer install
RUN docker-php-ext-install zip

# Copy Composer (specific version for reproducibility)
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install dependencies (no dev for production)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader

# Copy rest of the application
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# =============================================================================
# Production stage
# =============================================================================
FROM php:8.4-fpm-bookworm AS production

# Install production dependencies only
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libzip-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        intl \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /var/www/html

# Copy application from builder stage
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# Create storage directories and set permissions
RUN mkdir -p storage/cache/view \
    storage/logs \
    storage/queue \
    storage/sessions \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD php-fpm -t || exit 1

# Expose PHP-FPM port
EXPOSE 9000

USER www-data

CMD ["php-fpm"]

# =============================================================================
# Development stage (extends production with dev dependencies)
# =============================================================================
FROM production AS development

USER root

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy Composer for development
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Install dev dependencies
RUN composer install --prefer-dist --optimize-autoloader

USER www-data
