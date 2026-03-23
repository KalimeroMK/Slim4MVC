FROM dunglas/frankenphp:1-php8.5-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl nano unzip bash zip \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libxml2-dev libonig-dev libzip-dev \
    libicu-dev g++ make autoconf \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd pdo_mysql mbstring exif pcntl bcmath zip intl opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Caddyfile
COPY docker/Caddyfile /etc/caddy/Caddyfile

# Set working directory
WORKDIR /var/www/html

# Create storage directories
RUN mkdir -p /var/www/html/storage/cache/view \
    /var/www/html/storage/logs \
    /var/www/html/storage/queue \
    /var/www/html/storage/sessions \
    && chown -R www-data:www-data /var/www/html/storage

# Copy application files
COPY --chown=www-data:www-data . .

# Install dependencies (if composer.json exists)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Enable FrankenPHP worker mode for better performance
ENV FRANKENPHP_WORKER_COUNT=4

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:80/health || exit 1

EXPOSE 80 443 443/udp

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
