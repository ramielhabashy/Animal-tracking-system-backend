FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip curl libxml2-dev libcurl4-openssl-dev \
    libzip-dev libgd-dev libpng-dev libjpeg-dev \
    libfreetype6-dev libicu-dev libonig-dev \
    && docker-php-ext-install mbstring xml curl zip gd \
    fileinfo pdo_mysql intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP configuration
RUN echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/memory.ini \
    && echo 'upload_max_filesize=100M' >> /usr/local/etc/php/conf.d/memory.ini \
    && echo 'post_max_size=100M' >> /usr/local/etc/php/conf.d/memory.ini

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

WORKDIR /var/www/html

# Copy backend files
COPY backend/ /var/www/html/

# Install composer deps
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy frontend files and build
COPY frontend/ /tmp/frontend/
RUN cd /tmp/frontend && \
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get update && apt-get install -y nodejs && \
    npm install && \
    npm run build && \
    cp -r dist/* /var/www/html/public/ && \
    rm -rf /tmp/frontend

# Generate APP_KEY if not set
RUN if [ -z "$APP_KEY" ]; then php artisan key:generate --force; fi

# Run migrations
RUN php artisan migrate --force

EXPOSE 9000
CMD ["php-fpm"]
