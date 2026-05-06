FROM php:8.2-fpm as php-builder

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-install pdo pdo_mysql gd mbstring exif pcntl zip bcmath sockets \
    && pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY backend/ ./
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

FROM nginx:alpine as frontend-builder
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci --production
COPY frontend/ ./
RUN npm run build

FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-install pdo pdo_mysql gd mbstring exif pcntl zip bcmath sockets \
    && pecl install redis && docker-php-ext-enable redis

COPY --from=php-builder /var/www/html /var/www/html
COPY --from=frontend-builder /app/dist /var/www/html/public

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

WORKDIR /var/www/html

CMD ["php-fpm"]
