# AGENTS.md — Oasis Trace (Animal Tracking System)

## Quick Start

```bash
# Backend (port 8050)
cd backend && cp .env.example .env && composer install && php artisan migrate --seed && php artisan serve --port=8050

# Frontend (port 5173)
cd frontend && npm install && npm run dev
```

Default users (password: `password`): `admin@oasis.com` (Admin), `khalid@oasis.com` (Owner), `fatima@oasis.com` (Doctor), `omar@oasis.com` (Shepherd).

## Stack

| Layer | Tech | Port |
|-------|------|------|
| Backend | Laravel 11 / PHP 8.2 | 8050 |
| Frontend | React 18 / Vite / Tailwind 3 | 5173 |
| DB | MySQL 8.0 | 3306 |
| Mobile | Flutter 3.x | — |

## Commands

```bash
# Frontend (run from frontend/ — NOT frontend/src/)
npm run dev         # dev server with HMR
npm run build       # production build
npm run lint        # eslint
npm run typecheck   # runs `tsc --noEmit` — there is NO TypeScript, this is JSX; expect false positives
npm run test:run    # vitest
npm run test        # vitest watch

# Backend (run from backend/)
php artisan migrate --seed
php artisan test                        # PHPUnit (MySQL required)
php artisan test --filter=SomeTest      # single test
php artisan queue:work                  # process queue jobs
./vendor/bin/pint                       # Laravel Pint (PSR-12)
```

## Architecture

### Entrypoints
- Backend: `backend/public/index.php`
- Frontend: `frontend/src/main.jsx` → `App.jsx` (router)
- API routes: `backend/routes/api.php` (322 lines — all logic)
- Frontend routes: `frontend/src/config/routes.js` (role-based access)

### Auth flow
1. Login: `POST /api/auth/login` → returns `{ user: { role, ... }, token }`
2. Sanctum Bearer token in `Authorization` header
3. Legacy: frontend API client (`api.js`) also sends `X-User-Id` and `X-User-Role` headers
4. Role-based access via Spatie (`backend/app/Models/User.php` has `HasRoles` trait)
5. `getRoleAttribute()` calls `getPrimaryRoleName()` → `getRoleNames()->first() ?? 'Owner'`

### Role comparison
Frontend checks `user?.role === 'Admin'` (capital A). Backend seeds `Role::firstOrCreate(['name' => 'Admin'...])`. Match exactly.

### Routes behavior
- `/subscription` → `SubscriptionsPage.jsx` (admin management)
- `/subscription/select` → `SubscriptionPage.jsx` (user plan selection)
- `/react.oasis/` is the Vite `base` path — all frontend routes are prefixed

### Dashboard widget system
Located in `frontend/src/components/Dashboard/`:
- `DashboardGrid.jsx` — drag-and-drop container with `@dnd-kit`
- `DashboardWidget.jsx` — sortable wrapper
- `dashboardConfig.js` — role-based layouts + localStorage persistence
- Available widgets per role defined in `ROLE_AVAILABLE` and `ROLE_LAYOUTS`
- 10 widgets in `widgets/` directory, including `TasksWidget` (embeds `TaskCalendar`)

### i18n
- Translations: `frontend/src/i18n/en.js`, `ar.js`, `ur.js`, `eu.js`
- API-loaded translations override static keys
- RTL supported via `dir` prop and CSS rules in `index.css`

## Important Gotchas

1. **Two package.json / vite.config files**: active ones are `frontend/package.json` and `frontend/vite.config.js`. The ones in `frontend/src/` are stale/incorrect.

2. **No TypeScript**: the `typecheck` script calls `tsc --noEmit` but all code is JSX. Expect type errors — the CI uses `|| true` for this reason.

3. **Two migration directories**: `backend/database/migrations/` (56 files, canonical) and `database/migrations/` (33 files, duplicate). Always use `backend/database/migrations/`.

4. **`useAuth` exists in two places**: `frontend/src/hooks/useAuth.js` (standalone hook) and `frontend/src/context/AuthContext.jsx` (context provider with same export name). The context provider is what's wrapped in App.jsx — components may import from either source. `SubscriptionPage` imports from `../hooks/useAuth` while `DashboardGrid` imports from `../../context/AuthContext`.

5. **Auth middleware quirks**: backend uses `auth:sanctum` for most routes. Admin routes additionally check `role:Admin`. Some controllers still read `X-User-Id` header instead of `$request->user()`.

6. **Testing requires MySQL**: `php artisan test` connects to `ra_animal_tracking_testing` database. No SQLite fallback.

## Style conventions
- Laravel: PSR-12 (enforced by pint), controllers at `App\Http\Controllers\Api\*`
- React: functional components, JSX, Tailwind utility classes with custom brand colors (`#002819` primary, `#D4AF37` accent)
- No comments in JSX components unless asked
- Material Symbols for icons (string names like `"pets"`, `"vaccines"`)
