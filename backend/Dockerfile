FROM php:8.2-apache

# Install system dependencies - using correct package names for Debian Bookworm (php:8.2 uses Bookworm)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libxml2-dev \
    libcurl4-openssl-dev \
    libzip-dev \
    libgd-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    && docker-php-ext-install \
        mbstring \
        xml \
        curl \
        zip \
        gd \
        tokenizer \
        fileinfo \
        pdo_mysql \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Disable opcache to prevent segfaults
RUN echo 'opcache.enable=0' > /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/memory.ini \
    && echo 'max_execution_time=300' >> /usr/local/etc/php/conf.d/memory.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

WORKDIR /app

# Copy backend files
COPY . .

# Install composer dependencies with --no-scripts to avoid segfault
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Run artisan discover separately (with error handling)
RUN php artisan package:discover --ansi || echo "package:discover failed, continuing..."

# Build frontend and copy to public
COPY ../frontend /tmp/frontend
RUN cd /tmp/frontend && \
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get update && apt-get install -y nodejs && \
    npm install && \
    npm run build && \
    cp -r dist/* /app/public/ && \
    rm -rf /tmp/frontend

# Configure Apache
RUN mv /app/public /var/www/html/public && \
    ln -s /app /var/www/html/laravel && \
    a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
