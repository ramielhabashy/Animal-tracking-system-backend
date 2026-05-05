FROM php:8.2-apache

# Install system dependencies
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
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-install \
        mbstring \
        xml \
        curl \
        zip \
        gd \
        tokenizer \
        fileinfo \
        pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 22 for frontend build
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy backend files
COPY backend/ .

# Install backend dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs && \
    php artisan package:discover --ansi || true

# Copy frontend files and build
COPY frontend/ /tmp/frontend/
RUN cd /tmp/frontend && \
    npm install && \
    npm run build && \
    cp -r dist/* /app/public/ && \
    rm -rf /tmp/frontend

# Set environment variable to allow superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Configure Apache for Laravel
RUN mv /app/public /var/www/html/public && \
    ln -s /app /var/www/html/laravel && \
    a2enmod rewrite

# Apache .htaccess for Laravel + SPA routing
RUN printf '<IfModule mod_rewrite.c>\nOptions -MultiViews -Indexes\nRewriteEngine On\nRewriteCond %%{HTTP:Authorization} .\nRewriteRule .* - [E=HTTP_AUTHORIZATION:%%{HTTP:Authorization}]\nRewriteCond %%{REQUEST_FILENAME} !-d\nRewriteCond %%{REQUEST_FILENAME} !-f\nRewriteRule ^ index.php [L]\n</IfModule>\n' > /var/www/html/public/.htaccess

EXPOSE 80

CMD ["apache2-foreground"]
