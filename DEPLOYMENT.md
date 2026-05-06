# Oasis Trace - Deployment Guide

## Prerequisites

- Docker & Docker Compose
- Stripe account with API keys
- Domain name with SSL certificate
- MySQL database (or use included Docker setup)
- Redis server

## Environment Setup

1. Copy the production env file:
   ```bash
   cp backend/.env.production backend/.env
   ```

2. Generate application key:
   ```bash
   php artisan key:generate
   ```

3. Fill in all required environment variables in `backend/.env`:
   - `APP_URL` - Your production URL
   - `DB_*` - Database credentials
   - `STRIPE_*` - Stripe API keys
   - `REDIS_*` - Redis connection
   - `MAIL_*` - Email configuration

## Docker Deployment

### Build and start services:
```bash
docker-compose up -d --build
```

### Run migrations:
```bash
docker-compose exec app php artisan migrate --force
```

### Seed database (optional):
```bash
docker-compose exec app php artisan db:seed
```

### Restart queue worker:
```bash
docker-compose restart queue
```

## Stripe Webhook Setup

Configure Stripe webhook endpoint:
- URL: `https://your-domain.com/api/stripe/webhook`
- Events: `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`
- Copy the webhook signing secret to `STRIPE_WEBHOOK_SECRET` in your `.env`

## Health Checks

- App health: `GET /api/health`
- Database: `GET /api/health/database`
- Redis: `GET /api/health/redis`

## SSL/HTTPS Setup

Place SSL certificates in `./certs/`:
- `fullchain.pem`
- `privkey.pem`

Update nginx.conf to enable SSL.

## CI/CD

GitHub Actions workflows are configured in `.github/workflows/`:

- `ci.yml` - Combined pipeline for backend (PHP/Laravel) and frontend (React/Vite) with MySQL and Redis services
- `backend.yml` - Standalone backend CI with PHP 8.2, MySQL migrations, and Pest tests
- `frontend.yml` - Standalone frontend CI with Node 18, ESLint, TypeScript checks, and Vite build
- `mobile.yml` - Mobile CI for Flutter apps (when mobile directory exists)

### Secrets Needed

Configure these secrets in GitHub repository settings (`Settings > Secrets and variables > Actions`):

**Stripe:**
- `STRIPE_KEY` - Publishable key
- `STRIPE_SECRET` - Secret key
- `STRIPE_WEBHOOK_SECRET` - Webhook signing secret

**Database:**
- `DB_PASSWORD` - Production database password

**Optional:**
- `TWILIO_ACCOUNT_SID` / `TWILIO_AUTH_TOKEN` - For SMS notifications
- `MAIL_*` - SMTP credentials for email

Push to `main` or `develop` branches triggers all workflows. Pull requests run tests without deployment.

## Monitoring

- Queue worker logs: `docker-compose logs -f queue`
- App logs: `docker-compose logs -f app`
- Nginx logs: `docker-compose logs -f nginx`

## Rollback

```bash
docker-compose down
docker-compose up -d --build
```

## Pre-commit Hooks

Pre-commit hooks are configured in `.pre-commit-config.yaml` to ensure code quality:

- **ESLint** - Lints JavaScript/React files in `frontend/resources/js/`
- **Prettier** - Formats JS, JSX, TS, TSX, JSON, CSS, SCSS files
- **PHP CS Fixer / Laravel Pint** - Enforces PHP coding standards in backend
- **Flutter Format** - Formats Dart files (when mobile dir exists)

### Setup

```bash
pip install pre-commit
pre-commit install
```

Hooks run automatically on `git commit`. To run manually:
```bash
pre-commit run --all-files
```

## Local Development with Docker

Use `docker-compose.dev.yml` for local development with live reload:

```bash
docker-compose -f docker-compose.dev.yml up -d
```

Services:
- **MySQL** on port 3306 (database: `oasis_dev`)
- **Redis** on port 6379
- **Laravel app** on port 9000 (with hot reload via volume mount)
- **React frontend** on port 5173 (with Vite HMR)

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database passwords
- [ ] Stripe webhook secret configured
- [ ] Redis password set (if exposed)
- [ ] SSL/HTTPS enabled
- [ ] CORS origins restricted to production domains
- [ ] Environment variables not committed to git
