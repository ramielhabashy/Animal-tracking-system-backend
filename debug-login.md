# Debug Agent - Login Unauthorized Investigation

## Purpose
Investigate why login returns "Unauthorized" even when credentials are correct.

## Investigation Steps

### 1. Check if Laravel API is running
```bash
curl http://localhost:8050/api/auth/login -X POST -H "Content-Type: application/json" -d '{"email":"admin@oasis.com","password":"password"}'
```
Expected: JSON with user and token

### 2. Check if Frontend is hitting correct API port
The frontend built JS hardcodes API to port 8050. Check:
- Frontend running on 8080 makes requests to http://localhost:8050

### 3. Check CORS settings
In `backend/.env`:
```
SANCTUM_STATEFUL_DOMAINS=localhost:8050,localhost:8080
```

### 4. Check API returns correct response
If login at /api/auth/login returns 200 with token, the issue is:
- Frontend not sending request to correct URL
- Frontend not handling the response correctly

### 5. Check Frontend network calls
Open browser DevTools → Network tab → check what URL login request goes to

## Common Issues
1. Server not running on correct port
2. Browser caching old response
3. API CORS blocking request
4. Frontend pointing to wrong API URL

## Quick Fix
Restart servers:
```powershell
# Terminal 1
cd C:\animal-tracking-system-head\backend
php artisan serve --port=8050

# Terminal 2  
php -S localhost:8080 -t C:\animal-tracking-system-head\frontend\dist
```