# AGENTS.md — Oasis Trace (Animal Tracking System)

## Quick Start

```bash
# Backend (port 8050)
cd backend && cp .env.example .env && composer install && php artisan migrate --seed && php artisan serve --port=8050

# Frontend (port 5173)
cd frontend && npm install && npm run dev
```

Default users (password: `password`):

| Role | Email |
|------|-------|
| Admin | `admin@oasis.com` |
| Owner | `khalid@oasis.com` |
| Manager | `ahmad@oasis.com` |
| Doctor | `fatima@oasis.com` |
| Shepherd | `omar@oasis.com` |

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

7. **Workflow test requires dev server**: The `WorkflowTestService` makes real HTTP requests to `APP_URL` (default `http://localhost:8050`). Run `php artisan serve --port=8050` before triggering the test from CLI or web UI.

8. **Shipping phone field mismatch**: Frontend `ShippingStep` removed the phone field, but `CheckoutController@init` still requires `shipping_address.phone`. Workaround: backend validation needs updating, or shipping state still includes a phone from earlier.

## Style conventions
- Laravel: PSR-12 (enforced by pint), controllers at `App\Http\Controllers\Api\*`
- React: functional components, JSX, Tailwind utility classes with custom brand colors (`#002819` primary, `#D4AF37` accent)
- No comments in JSX components unless asked
- Material Symbols for icons (string names like `"pets"`, `"vaccines"`)

## Session Log

### 2026-05-19 (final) — DemoDataSeeder + staff accounts + logins.txt

**DemoDataSeeder** (runs last in DatabaseSeeder):
- Truncates all content tables (animals, devices, groups, geofences, medical records, vaccinations, tasks, auctions, bids, conversations, messages, transfers, subscriptions, orders, banners) before creating fresh data
- Preserves system data (users, roles, permissions, settings, languages, tiers)

**Demo accounts (password: `password`)**:
- `demo1@oasis.com` — Ahmed Al-Demo (Owner, 3 camels + full dataset)
- `demo2@oasis.com` — Sara Al-Demo (Owner, 2 animals + full dataset)
- `support@oasis.com` — Khalid Support (Support role)
- `accounts@oasis.com` — Mona Accountant (Accountant role)
- `cs@oasis.com` — Nora Customer Service (Customer Service role)
- `youssef@demo.com` — Youssef Shepherd (assigned to Ahmed)
- `hassan@demo.com` — Hassan Shepherd (assigned to Sara)

**Full demo dataset per run:**
- 5 demo animals w/ devices + 3 groups + 2 geofences
- 7 medical records + 6 vaccinations + 5 tasks + 3 auctions (1 draft, 2 active)
- 1 conversation (4 messages) + 1 completed transfer (DMO-004)
- 2 subscriptions + 2 orders + 1 banner + settings (commission 5%, manual auction approval)

**Side fixes:**
- `OwnershipHistory` model: added `$table = 'ownership_history'` (Laravel was looking for `ownership_histories`)
- `backend/logins.txt` created with all credentials

**Verification:**
- `php artisan migrate:fresh --seed` — all seeders pass in sequence, 19 users, 5 animals, clean state
- `npm run build` still clean (no frontend changes)
- `php -l` passes for all 3 modified/created files

### 2026-05-19 — Temperature display fixes (React + Flutter)

**Root cause — all pages used `animal.baseline_temperature`**
- The live device temperature (`device.temperature` / `temperature`) from the simulator was available in all API responses but ignored
- `baseline_temperature` (animal's normal baseline, set at registration) is often null for seeded/test animals → shows `'N/A'` or `'-'`

**React fixes (4 files):**
- `MapView.jsx` (4 locations): `animal.baseline_temperature` → `animal.temperature ?? animal.baseline_temperature`
- `AnimalList.jsx` (table + card + `getAnimalStatus`): `animal.baseline_temperature` → `animal.device?.temperature ?? animal.baseline_temperature`
- `Dashboard/MapWidget.jsx`: same pattern
- `SimulatorPage.jsx` (new): temperature state + slider (35–42°C, 0.1°C steps) per device card, calls `POST /api/simulator/set-temperature` on change

**Flutter fixes (2 files):**
- `animals_page.dart`: `animal['baseline_temperature']` → `animal['device']?['temperature'] ?? animal['baseline_temperature']`
- `dashboard_widgets.dart`: `a['baseline_temperature']` → `a['device']?['temperature'] ?? a['baseline_temperature']`

**Verification**
- `npm run build` — clean
- `dart analyze lib/animals_page.dart lib/dashboard_widgets.dart` — 0 errors

**Key gotcha**: JavaScript esbuild does not allow mixing `??` with `||` without parentheses — `(a ?? b) || fallback` is required.

### 2026-05-19 — Embed Codes System

**What it does**: A new "Embed Codes" tab in Settings (`/settings`) generates iframe snippets users can copy-paste into other websites to embed live auctions or the checkout flow.

**Backend — `EmbedController`**
- Created `App\Http\Controllers\Api\EmbedController` with `auctions()` method
- Returns active/live auctions with animal image, title, price, owner, status
- Registered as public route `GET /api/embed/auctions` (no auth)

**Frontend — `EmbedAuctionList.jsx`** (new page)
- Compact auction grid page at `/embed/auctions`
- Shows active auctions in responsive cards (animal image, title, species, current bid, seller)
- Status badges with pulse animation for live auctions
- "Powered by Oasis Trace" footer — links back to main site via `target="_top"`
- No auth, no filters, no bid modal — lightweight for iframe embedding

**Frontend — `CheckoutPage.jsx`** (modified)
- Supports `?embed=1` query parameter
- When present: hides page header and Footer, uses tighter padding
- Lets customers embed subscription checkout directly

**Frontend — `EmbedCodesSettings.jsx`** (new Settings tab)
- Settings tab: `id: 'embedCodes'`, icon `'code'`
- Two widget cards: Auctions Widget + Checkout Widget
- Each shows: name, description, height/width inputs, Preview link, copyable `<iframe>` code snippet
- Copy-to-clipboard button with success state
- Uses `usePlatform` to auto-detect the platform URL for embed snippets

**Frontend — i18n** (all 4 locales)
- Added `settings.embedCodes` for the tab label
- Added root-level `embedCodesSection` with 10 keys (title, description, copyCode, copied, height, width, auctionsTitle, auctionsDesc, checkoutTitle, checkoutDesc)

**New/modified files:**
| File | Status |
|------|--------|
| `backend/app/Http/Controllers/Api/EmbedController.php` | New |
| `backend/routes/api.php` | Modified (added embed route) |
| `frontend/src/pages/EmbedAuctionList.jsx` | New |
| `frontend/src/pages/CheckoutPage.jsx` | Modified (embed mode) |
| `frontend/src/components/Settings/EmbedCodesSettings.jsx` | New |
| `frontend/src/components/Settings/index.js` | Modified (added export) |
| `frontend/src/pages/SettingsPage.jsx` | Modified (added tab) |
| `frontend/src/App.jsx` | Modified (added route) |
| `frontend/src/i18n/en.js`, `ar.js`, `ur.js`, `eu.js` | Modified (added keys) |

**Verification**
- `npm run build` — clean
- `php -l` — no syntax errors
- `GET /api/embed/auctions` — returns public auction data

**Key gotchas**: `platformUrl` comes from `PlatformContext` via `usePlatform()`, not from env vars. Embed auction page uses `target="_top"` on links so they break out of iframes. The `?embed=1` param is read via `useSearchParams()` at the top of CheckoutPage.

### 2026-05-19 — Auction admin approval workflow, transfer integration, payment management

**Backend — Auction approval workflow**
- `AuctionController@store`: now checks `auction_auto_approve` setting. When false (default), creates auction as `draft` status instead of `active`. Adjusted notification body and added owner notification for pending approval.
- Added `adminPendingApproval()`: returns paginated draft auctions for admin
- Added `adminApprove()`: changes `draft` → `active`, recalculates `ends_at` from original duration, notifies owner
- Added `adminReject()`: changes `draft` → `cancelled`, stores rejection notes, notifies owner
- Added `adminPayments()`: returns sold/ended auctions with payment status, filterable by `payment_status`

**Backend — Auction-to-transfer integration**
- `verifyPayment()` (approved path): now creates `OwnershipTransfer` (type=auction, status=completed) + `OwnershipHistory` before updating `animal.owner_id`. Commission calculated from `transfer_commission` settings. Transfer linked via `reference_type=auction`, `reference_id`.

**Backend — Admin settings**
- `AdminSettingsController`: added `getAuctionSettings()` + `saveAuctionSettings()` (auto_approve boolean)

**Backend — Routes**
- 4 admin auction routes in Admin middleware group (`pending-approval`, `approve`, `reject`, `payments`)
- 2 auction settings routes (`GET/POST /admin/settings/auction`)

**Frontend — AuctionList.jsx**
- Added "Pending Approval" and "Payments" filter tabs (Admin-only)
- Pending tab: draft auction list with Approve/Reject buttons per row
- Payments tab: sold auction list with payment status badges
- Reject modal with notes textarea

**Frontend — AuctionDetails.jsx**
- Shows "Pending Approval" amber banner when status=draft
- Admin sees Approve/Reject buttons on draft auctions
- Added `approveAuction()` and `rejectAuction()` functions

**Frontend — SettingsPage.jsx**
- Added "Auction" tab with `AuctionSettings` component (auto-approve toggle)

**Frontend — i18n**
- Added 14 keys to `auctionsPage` section in all 4 locales (pendingApproval, payments, noPending, noPayments, awaitingApproval, approve, reject, approved, rejected, failedApprove, failedReject, rejectTitle, rejectDesc, rejectNotesPlaceholder)

**Flutter — auction_page.dart**
- Added `draft` status to card status switch (amber "Pending" badge)
- Added approve/reject buttons in detail sheet for admin when status=draft
- Added `_confirmApprove()` / `_confirmReject()` dialogs
- Added payment status display for sold auctions
- Loads pending/payment auctions on init for Admin

**Flutter — api_service.dart**
- Added 6 new methods: `getPendingApprovalAuctions()`, `getPaymentAuctions()`, `adminApproveAuction()`, `adminRejectAuction()`, `getAuctionSettings()`, `saveAuctionSettings()`

**Flutter — data_provider.dart**
- Added `_pendingAuctions`, `_paymentAuctions` state + getters
- Added `loadPendingAuctions()`, `loadPaymentAuctions()`, `adminApproveAuction()`, `adminRejectAuction()`

**Verification**
- `php -l` passes for all 3 backend files (AuctionController, AdminSettingsController, api.php)
- `npm run build` passes clean
- `dart analyze lib/auction_page.dart lib/api_service.dart lib/data_provider.dart` — 0 errors (only pre-existing warnings/infos)
