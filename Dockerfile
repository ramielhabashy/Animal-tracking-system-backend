FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip curl libxml2-dev libcurl4-openssl-dev \
    libzip-dev libgd-dev libpng-dev libjpeg62-turbo-dev \
    libfreetype6-dev libicu-dev \
    && docker-php-ext-install mbstring xml curl zip gd tokenizer \
    fileinfo pdo_mysql intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 18 (LTS, stable)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

WORKDIR /app

# Copy backend files
COPY backend/ .

# Install composer deps with scripts disabled
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Copy frontend and build
COPY frontend/ /tmp/frontend/
RUN cd /tmp/frontend && npm install && npm run build && \
    cp -r dist/* /app/public/ && rm -rf /tmp/frontend

# Configure Apache
RUN mv /app/public /var/www/html/public && \
    ln -s /app /var/www/html/laravel && a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
