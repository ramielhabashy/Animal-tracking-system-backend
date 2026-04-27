# Animal Tracking System (v0.1)

A full-stack livestock management system with Laravel API, React Web, and Flutter Mobile.

## Project Structure

```
animal-tracking-system-head/
├── backend/           # Laravel API (PHP 8.2+)
├── frontend/          # React Admin Panel
│   ├── src/         # React source files
│   └── dist/        # Built production files
├── mobile/           # Flutter Mobile App
│   └── test/        # Integration tests (30 tests)
├── database/        # MySQL migrations & seeders
│   └── oasis_staging_v0.1.sql  # Database dump for v0.1
└── .github/workflows/ # CI/CD workflows
```

## Version v0.1 - Changelog

### Added
- **Dynamic Languages System**: Languages loaded from `/api/languages` API
- **Dynamic Roles System**: Roles loaded from `/api/admin/roles` API
- **RTL Support**: Uses `language.direction` from API (ltr/rtl)
- **Cache Busting**: On language/translation changes

### Changes
- React frontend: Removed hardcoded language fallbacks
- Flutter mobile: Dynamic role dropdown from API
- Language detection uses API `direction` field

### Fixed
- CORS configuration for local development
- Dashboard 500 error (auth middleware)
- API response handling for nested data

---

## Tech Stack

| Component | Technology | Port |
|-----------|------------|------|
| Backend | Laravel 11 + PHP 8.2 | 8050 |
| Frontend | React 18 + Vite + Tailwind | 5173 |
| Database | MySQL | 3306 |
| Mobile | Flutter | - |

## Database Configuration

| Setting | Value |
|---------|-------|
| Database | `oasis_staging` |
| Host | 127.0.0.1 |
| Port | 3306 |
| Username | root |
| Password | (empty) |

**Database dump:** `database/oasis_staging_v0.1.sql`

---

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

# Create database in MySQL
# DB_DATABASE=oasis_staging

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start server
php artisan serve --port=8050
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

# Build for release (recommended)
flutter build web --release

# Serve the build
cd build/web
php -S localhost:8080
```

Then open: http://localhost:8080

---

## API Endpoints

Base URL: http://localhost:8050/api

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /auth/login | User login |
| POST | /auth/logout | User logout |

### Users
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /users | List users |
| POST | /users | Create user |
| GET | /users/{id} | Get user |
| PUT | /users/{id} | Update user |
| DELETE | /users/{id} | Delete user |
| PATCH | /users/{id}/toggle-status | Toggle active status |

### Roles (Dynamic)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /admin/roles | List all roles |

### Languages (Dynamic)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /languages | List active languages |
| GET | /admin/languages | Admin languages list |
| POST | /admin/languages | Create language |
| GET | /translations | Get translations |

### Animals
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /animals | List animals |
| POST | /animals | Create animal |
| GET | /animals/{id} | Get animal |
| PUT | /animals/{id} | Update animal |
| DELETE | /animals/{id} | Delete animal |

### Devices
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /devices | List devices |
| POST | /devices | Create device |

### Geofences
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /geofences | List geofences |
| POST | /geofences | Create geofence |

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /dashboard | Dashboard stats |

---

## Default Users

| Email | Password | Role |
|-------|----------|------|
| admin@oasis.com | password | Admin |
| khalid@oasis.com | password | Owner |
| ahmad@oasis.com | password | Owner |
| saeed@oasis.com | password | Owner |
| fatima@oasis.com | password | Manager |

---

## Features

- Animal management with species tracking (Camel, Goat, Sheep)
- GPS device tracking
- Geofencing with alerts
- Medical records & vaccinations
- Task management
- Team/user management with roles
- Subscriptions & payments
- Auction system
- AI assistant
- Multi-language (Arabic RTL, English, Urdu, Basque)

### Roles (Dynamic)
- **Admin**: Full system access
- **Owner**: Own data + manage users
- **Manager**: Limited management
- **Shepherd**: Animal view/create
- **Doctor**: Medical records
- **Employee**: Basic access

---

## Flutter Tests

Run integration tests:
```bash
cd mobile
flutter test
```

**30 tests passing:**
- User CRUD (8 tests)
- Role Management (5 tests)
- Language/i18n (8 tests)
- API Service (9 tests)

---

## Git Commands

```bash
# Check status
git status

# Add changes
git add .

# Commit
git commit -m "v0.1 - Dynamic languages and roles integration"

# Create tag
git tag -a v0.1 -m "v0.1 - Dynamic languages and roles"

# Push with tags
git push --tags
```

---

## License

Proprietary - All rights reserved