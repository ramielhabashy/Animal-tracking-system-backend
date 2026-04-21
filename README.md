# Animal Tracking System

A full-stack livestock management system with Laravel API, React Web, and Flutter Mobile.

## Project Structure

```
animal-tracking-system-head/
├── backend/           # Laravel API (PHP 8.2+)
├── frontend/          # React Admin Panel
│   ├── src/         # React source files
│   └── dist/       # Built production files
├── mobile/          # Flutter Mobile App
└── database/       # MySQL migrations & seeders
```

## Tech Stack

| Component | Technology | Port |
|-----------|------------|------|
| Backend | Laravel 11 + PHP 8.2 | 8000 |
| Frontend | React 18 + Vite + Tailwind | 5173 |
| Database | MySQL | 3306 |
| Mobile | Flutter | - |

## Quick Start

### Backend (Laravel)

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
copy .env.example .env

# Generate app key
php artisan key:generate

# Create database in MySQL and update .env
DB_DATABASE=animal_tracking
DB_USERNAME=root
DB_PASSWORD=yourpassword

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Start server
php artisan serve --port=8000
```

### Frontend (React)

```bash
cd frontend

# Install dependencies
npm install

# Start dev server
npm run dev
```

Access at: http://localhost:5173

### Mobile (Flutter)

```bash
cd mobile

# Get dependencies
flutter pub get

# Run app
flutter run
```

## API Endpoints

Base URL: http://localhost:8000/api

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /animals | List all animals |
| POST | /animals | Create animal |
| GET | /animals/{id} | Get animal |
| PUT | /animals/{id} | Update animal |
| DELETE | /animals/{id} | Delete animal |
| GET | /devices | List devices |
| GET | /geofences | List geofences |
| GET | /dashboard | Dashboard stats |
| POST | /auth/login | User login |

## Features

- Animal management with species tracking
- GPS device tracking
- Geofencing with alerts
- Medical records & vaccinations
- Task management
- Team/user management
- Subscriptions & payments
- Auction system
- AI assistant
- Multi-language (Arabic/English)

## Default Users

Check `database/seeders/UserSeeder.php` for default credentials.