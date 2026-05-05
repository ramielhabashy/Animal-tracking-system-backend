FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip zip curl libxml2-dev libcurl4-openssl-dev libzip-dev \
    libgd-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install mbstring xml curl zip gd tokenizer fileinfo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Disable opcache to prevent segfaults
RUN echo 'opcache.enable=0' > /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/memory.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy backend files
COPY . .

# Install composer dependencies with --no-scripts to avoid segfault
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Run artisan discover separately (with error handling)
RUN php artisan package:discover --ansi || echo "package:discover failed, continuing..."

# Configure Apache
RUN mv /app/public /var/www/html/public && \
    ln -s /app /var/www/html/laravel && \
    a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
