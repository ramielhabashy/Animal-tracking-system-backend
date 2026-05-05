#!/bin/bash
# Start script for Railway deployment

# Move to backend directory
cd backend

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Seed database (optional)
# php artisan db:seed --force

# Start Laravel server
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
