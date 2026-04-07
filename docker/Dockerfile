FROM php:8.4-fpm-bookworm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    nano \
    unzip \
    bash \
    zip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libzip-dev \
    libicu-dev \
    g++ \
    make \
    autoconf \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring exif pcntl bcmath zip intl opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP-FPM configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www/html

# Create storage directories (build-time; entrypoint re-applies perms at runtime for WSL)
RUN mkdir -p /var/www/html/storage/cache/view \
    /var/www/html/storage/cache/data \
    /var/www/html/storage/logs \
    /var/www/html/storage/queue \
    /var/www/html/storage/sessions \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Entrypoint fixes storage permissions on WSL where volume mounts override build chown
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose PHP-FPM port
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
