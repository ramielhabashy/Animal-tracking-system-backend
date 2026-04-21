# Animal Tracking System - Startup Guide

## Project Location
All files are in: `C:\animal-tracking-system-head`

## Structure
```
C:\animal-tracking-system-head\
├── backend/              # Laravel API
├── frontend/             # React Dashboard  
│   └── dist/            # Built React files
├── mobile/               # Flutter App
└── database/            # SQLite database
```

---

## Start Commands

Run these in **2 separate terminal windows**:

### Terminal 1 - Laravel API (port 8050)
```powershell
cd C:\animal-tracking-system-head\backend
php artisan serve --port=8050
```

### Terminal 2 - Frontend (port 8080)
```powershell
php -S localhost:8080 -t C:\animal-tracking-system-head\frontend\dist
```

### Keep both terminals OPEN while using the app!

---

## Access URLs

| Service | URL |
|---------|-----|
| Frontend (login here) | http://localhost:8080 |
| API | http://localhost:8050 |
| Database | C:\animal-tracking-system-head\database\animal_tracking |

---

## Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@oasis.com | password |
| Owner | khalid@oasis.com | password |
| Manager | fatima@oasis.com | password |

---

## If Login Fails

1. Make sure BOTH servers are running
2. Check you're using port 8050 for API
3. Try: admin@oasis.com / password

---

## Troubleshooting

### Port in use?
```powershell
netstat -ano | findstr :8050
netstat -ano | findstr :8080
```

### Database error?
Delete `database\animal_tracking` and run:
```powershell
cd backend
php artisan migrate
php artisan db:seed
```