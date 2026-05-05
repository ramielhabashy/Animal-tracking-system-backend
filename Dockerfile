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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Install backend dependencies
RUN cd backend && \
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Install frontend dependencies and build
RUN cd frontend && \
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs && \
    npm install && \
    npm run build

# Set environment variable to allow superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Start Apache with Laravel
RUN mv backend/public /var/www/html/public && \
    ln -s /app/backend /var/www/html/laravel

EXPOSE 80

CMD ["apache2-foreground"]
