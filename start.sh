#!/bin/bash
# Start script for local development

set -e

echo "Starting Animal Tracking System..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "Error: Docker is not running. Please start Docker first."
    exit 1
fi

# Check if .env exists, copy from example if not
if [ ! -f backend/.env ]; then
    echo "Creating backend/.env from .env.example..."
    cp backend/.env.example backend/.env
    echo "Please update backend/.env with your configuration before continuing."
fi

if [ ! -f frontend/.env ]; then
    echo "Creating frontend/.env from .env.example..."
    cp frontend/.env.example frontend/.env
fi

# Start services with docker-compose
cd backend
docker-compose up -d

echo ""
echo "Services started!"
echo "Backend API: http://localhost:8080"
echo "MySQL: localhost:3306"
echo ""
echo "To view logs: docker-compose logs -f"
echo "To stop: docker-compose down"
