# Admin Page Testing Task

## Context
You are testing the `/oasis-trace` Laravel app (frontend at localhost:5173, API at localhost:8050).

## Test File
A Playwright test is saved at: `test-admin-pages.mjs`

## Your Task

### 1. Run the test
```bash
cd /Users/afazza/Documents/Camels/oasis-trace
node test-admin-pages.mjs
```

### 2. Fix any issues found

The test checks ALL 16 admin pages:
- Dashboard
- Animals
- Devices
- Users
- Auctions
- Geofences
- Animal Groups
- Subscriptions
- Tasks
- Reports
- Payments
- Alerts
- Map
- Team
- Task Logs Archive
- My Payments

### 3. Requirements

For each page:
- Page must load without errors
- All buttons must be clickable and functional
- No JavaScript console errors
- All API calls must succeed

### 4. Fix process

1. Run test to identify failing pages
2. For each failing page:
   - Open the page in browser manually
   - Check browser console for JavaScript errors
   - Check network tab for failed API calls
   - Identify the root cause
   - Fix the issue in the codebase
   - Re-run test to verify fix
3. Repeat until all tests pass

### 5. Success criteria
- All 16 pages pass
- All buttons clickable
- No console errors
- Exit code: 0

## Notes
- Use admin login: `admin@oasis.com` / `password`
- Real database data exists (35 animals, 14 devices, users, auctions, etc.)
- Test must be run from the oasis-trace directory