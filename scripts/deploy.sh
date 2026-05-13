#!/bin/bash
set -e

echo "🚀 Starting deployment of Animal Tracking System"

# Check if .env exists
if [ ! -f backend/.env ]; then
    echo "❌ backend/.env not found. Copy backend/.env.production and configure it."
    exit 1
fi

# Pull latest changes
echo "📦 Pulling latest changes..."
git pull origin main

# Build and start services
echo "🏗️  Building Docker images..."
docker-compose build

echo "🚀 Starting services..."
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for database to be ready..."
sleep 10

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear

# Optimize for production
echo "⚡ Optimizing for production..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Restart queue worker
echo "🔄 Restarting queue worker..."
docker-compose restart queue

echo "✅ Deployment complete!"
echo "📊 Service status:"
docker-compose ps
