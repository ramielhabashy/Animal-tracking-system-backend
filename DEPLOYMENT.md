# Animal Tracking System - Deployment Guide

## Table of Contents
- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Environment Configuration](#environment-configuration)
- [Docker Deployment](#docker-deployment)
- [CI/CD Pipeline](#cicd-pipeline)
- [Railway Deployment](#railway-deployment)
- [Manual Deployment](#manual-deployment)
- [Backup & Recovery](#backup--recovery)
- [Monitoring & Health Checks](#monitoring--health-checks)
- [Troubleshooting](#troubleshooting)

## Overview

The Animal Tracking System (Oasis Trace) is a Laravel + React application with:
- **Backend**: PHP 8.2, Laravel 10, MySQL 8.0, Redis 7
- **Frontend**: React 18, Vite, Node.js 18
- **Infrastructure**: Docker, Nginx, GitHub Actions CI/CD

## Prerequisites

- Docker & Docker Compose (v2+)
- Git
- Stripe account (for payments)
- Domain name with DNS access
- SSL certificate (Let's Encrypt or commercial)

## Environment Configuration

### Backend Environment Variables

Copy and configure `backend/.env` from `backend/.env.production`:

```bash
cp backend/.env.production backend/.env
```

#### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_NAME` | Application name | "Oasis Trace" |
| `APP_ENV` | Environment (production/local) | production |
| `APP_DEBUG` | Debug mode (false in prod) | false |
| `APP_URL` | Backend URL | https://api.your-domain.com |
| `APP_KEY` | Laravel app key (generate with `php artisan key:generate`) | base64:... |
| `FRONTEND_URL` | Frontend URL for CORS | https://app.your-domain.com |
| `DB_CONNECTION` | Database driver | mysql |
| `DB_HOST` | Database host (use `mysql` in Docker) | mysql |
| `DB_PORT` | Database port | 3306 |
| `DB_DATABASE` | Database name | oasis_production |
| `DB_USERNAME` | Database user | oasis_user |
| `DB_PASSWORD` | Database password | strong_password |
| `REDIS_HOST` | Redis host (use `redis` in Docker) | redis |
| `REDIS_PORT` | Redis port | 6379 |
| `QUEUE_CONNECTION` | Queue driver | redis |
| `CACHE_DRIVER` | Cache driver | redis |
| `SESSION_DRIVER` | Session driver | redis |
| `STRIPE_KEY` | Stripe publishable key | pk_live_... |
| `STRIPE_SECRET` | Stripe secret key | sk_live_... |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook secret | whsec_... |
| `MAIL_MAILER` | Mail driver | smtp |
| `MAIL_HOST` | SMTP host | smtp.mailtrap.io |
| `MAIL_PORT` | SMTP port | 587 |
| `MAIL_USERNAME` | SMTP username | user |
| `MAIL_PASSWORD` | SMTP password | pass |
| `MAIL_FROM_ADDRESS` | From email address | noreply@your-domain.com |
| `MAIL_FROM_NAME` | From name | "Oasis Trace" |

#### Optional Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `TWILIO_ACCOUNT_SID` | Twilio SID for SMS | - |
| `TWILIO_AUTH_TOKEN` | Twilio auth token | - |
| `SMS_FROM_NUMBER` | SMS sender number | - |
| `SMS_ENABLED` | Enable SMS notifications | false |
| `CALL_ENABLED` | Enable call notifications | false |
| `PUSHER_*` | Pusher for real-time features | - |

### Frontend Environment Variables

Create `frontend/.env`:

```bash
VITE_API_URL=https://api.your-domain.com
VITE_APP_NAME="Oasis Trace"
VITE_APP_ENV=production
```

## Docker Deployment

### Quick Start

```bash
# Clone repository
git clone <repo-url>
cd animal-tracking-backup-20260505-224235

# Configure environment
cp backend/.env.production backend/.env
# Edit backend/.env with your values
nano backend/.env

# Generate app key
docker-compose run --rm app php artisan key:generate

# Build and start services
docker-compose up -d --build

# Run migrations
docker-compose exec app php artisan migrate --force

# Verify services
docker-compose ps
```

### Services

| Service | Description | Port |
|---------|-------------|------|
| `mysql` | MySQL 8.0 database | 3306 (localhost only) |
| `redis` | Redis 7 cache/queue | 6379 (localhost only) |
| `app` | Laravel backend (PHP-FPM) | 9000 |
| `frontend` | React frontend (Nginx) | 5173 |
| `nginx` | Nginx reverse proxy | 80, 443 |
| `queue` | Laravel queue worker | - |

### Docker Commands

```bash
# View logs
docker-compose logs -f [service]

# Restart service
docker-compose restart [service]

# Run artisan commands
docker-compose exec app php artisan [command]

# Execute migrations
docker-compose exec app php artisan migrate --force

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Optimize for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Development Environment

Use `docker-compose.dev.yml` for local development with hot reload:

```bash
docker-compose -f docker-compose.dev.yml up -d
```

Services:
- Laravel app: http://localhost:9000
- React frontend with HMR: http://localhost:5173
- MySQL: localhost:3306
- Redis: localhost:6379

## CI/CD Pipeline

GitHub Actions workflows are configured in `.github/workflows/`:

### Workflows

1. **ci.yml** - Combined pipeline triggered on push/PR to main/develop
   - Runs backend tests (PHP 8.2, MySQL, Redis)
   - Runs frontend tests (Node 18, lint, typecheck, build)
   - Builds Docker images on main branch push

2. **backend.yml** - Standalone backend CI
   - PHP setup with extensions
   - MySQL service container
   - Migrations and Pest tests

3. **frontend.yml** - Standalone frontend CI
   - Node.js setup with npm cache
   - Lint, typecheck, tests, build

### Required GitHub Secrets

Configure in `Settings > Secrets and variables > Actions`:

| Secret | Description |
|--------|-------------|
| `STRIPE_KEY` | Stripe publishable key |
| `STRIPE_SECRET` | Stripe secret key |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook secret |
| `DB_PASSWORD` | Production database password |

### CI/CD Status Badge

Add to README.md:
```markdown
![CI/CD Pipeline](https://github.com/your-org/animal-tracking/actions/workflows/ci.yml/badge.svg)
```

## Railway Deployment

Railway.app supports the Procfile in `backend/Procfile`:

```
web: vendor/bin/heroku-php-apache2 public/
queue: php artisan queue:work --tries=3 --timeout=90
```

### Deploy to Railway

1. Connect GitHub repository to Railway
2. Configure environment variables in Railway dashboard
3. Deploy automatically on push to main

Note: Railway uses its own MySQL and Redis services - update `DB_HOST` and `REDIS_HOST` accordingly.

## Manual Deployment

Use the provided deployment script:

```bash
# Make scripts executable
chmod +x scripts/deploy.sh scripts/backup.sh

# Run deployment
./scripts/deploy.sh
```

The script will:
1. Pull latest changes
2. Build Docker images
3. Run migrations
4. Clear and rebuild caches
5. Restart queue workers

## Backup & Recovery

### Automated Backup

```bash
# Run backup script
./scripts/backup.sh
```

Backups are stored in `./backups/`:
- Database dumps: `backup_YYYYMMDD_HHMMSS.sql.gz`
- Storage files: `backup_YYYYMMDD_HHMMSS-storage.tar.gz`
- Environment config: `env_YYYYMMDD_HHMMSS.bak`

Backups older than 7 days are automatically cleaned up.

### Restore from Backup

```bash
# Restore database
gunzip < backups/backup_YYYYMMDD_HHMMSS.sql.gz | docker-compose exec -T mysql mysql -u root -p oasis_production

# Restore storage
cat backups/backup_YYYYMMDD_HHMMSS-storage.tar.gz | docker-compose exec -T app tar -xzf - -C /
```

### Automated Backups with Cron

Add to crontab:
```bash
0 2 * * * cd /path/to/animal-tracking && ./scripts/backup.sh >> logs/backup.log 2>&1
```

## Monitoring & Health Checks

### Health Check Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /api/health` | Overall system health |
| `GET /api/health/database` | Database connectivity |
| `GET /api/health/redis` | Redis connectivity |

### Log Locations

```bash
# Application logs
docker-compose logs -f app

# Queue worker logs
docker-compose logs -f queue

# Nginx logs
docker-compose logs -f nginx

# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log
```

### Monitoring Tools

- **Queue monitoring**: `docker-compose exec app php artisan queue:monitor`
- **Horizon** (if installed): `docker-compose exec app php artisan horizon`

## Troubleshooting

### Common Issues

1. **Database connection refused**
   - Check if MySQL is healthy: `docker-compose ps mysql`
   - Verify credentials in `.env`
   - Wait for MySQL health check to pass

2. **Redis connection failed**
   - Check Redis health: `docker-compose ps redis`
   - Verify `REDIS_HOST=redis` in Docker environment

3. **Permission denied errors**
   - Fix storage permissions: `docker-compose exec app chown -R www-data:www-data storage bootstrap/cache`

4. **Nginx 502 Bad Gateway**
   - Check if PHP-FPM is running: `docker-compose logs app`
   - Verify `APP_URL` matches the request

5. **Stripe webhook failures**
   - Verify `STRIPE_WEBHOOK_SECRET` in `.env`
   - Check webhook URL: `https://your-domain.com/api/stripe/webhook`
   - View Stripe webhook logs in Stripe dashboard

### Debug Mode

For temporary debugging (NEVER in production):
```bash
# Enable debug
docker-compose exec app sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env

# Clear config cache
docker-compose exec app php artisan config:clear
```

### Getting Help

1. Check logs: `docker-compose logs -f [service]`
2. Verify environment: `docker-compose exec app php artisan env`
3. Test database: `docker-compose exec app php artisan db:monitor`
4. Review GitHub Actions logs for CI/CD issues

## Security Checklist

Before going live:
- [ ] `APP_DEBUG=false` in production
- [ ] Strong database passwords
- [ ] Stripe webhook secret configured
- [ ] Redis password set (if exposed)
- [ ] SSL/HTTPS enabled
- [ ] CORS origins restricted to production domains
- [ ] Environment files not in git
- [ ] Regular backups configured
- [ ] Queue worker running
- [ ] Health checks passing

## File Structure

```
.
├── backend/
│   ├── Dockerfile              # PHP 8.2 backend image
│   ├── Procfile                # Railway deployment config
│   ├── .env.example            # Environment template
│   └── .env.production         # Production defaults
├── frontend/
│   ├── Dockerfile              # Node 18 build + Nginx serve
│   └── .env.example            # Frontend env template
├── docker-compose.yml          # Production composition
├── docker-compose.dev.yml      # Development composition
├── .github/workflows/
│   ├── ci.yml                  # Combined CI/CD pipeline
│   ├── backend.yml             # Backend CI
│   ├── frontend.yml            # Frontend CI
│   └── mobile.yml              # Mobile CI (if applicable)
├── scripts/
│   ├── deploy.sh               # Deployment script
│   └── backup.sh               # Backup script
└── DEPLOYMENT.md               # This file
```
