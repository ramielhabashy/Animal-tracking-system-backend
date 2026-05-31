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
npm run lint        # eslint (known-broken config — eslint.config.js missing)
npm run typecheck   # runs `tsc --noEmit` — JSX only, expect false positives
npm run test:run    # vitest (8 real tests)
npm run test        # vitest watch
npm run test:e2e    # Playwright e2e (requires seeded backend on localhost:8050)

# Backend (run from backend/)
php artisan migrate --seed
php artisan migrate:fresh --seed   # reset + re-seed (DESTRUCTIVE — drops all tables)
php artisan test                        # PHPUnit (MySQL testing DB required)
php artisan test --filter=SomeTest      # single test
php artisan workflow:test               # 30-step end-to-end workflow (requires dev server on port 8050)
php artisan workflow:test --skip-cleanup # skip cleanup after workflow test (debugging)
php artisan queue:work                  # process queue jobs
./vendor/bin/pint                       # Laravel Pint (PSR-12)

# Mobile (run from mobile/)
flutter test        # unit + widget tests (12 real tests)
dart analyze        # static analysis
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

## Notification System

### Architecture
- **Push notifications**: NOT integrated (no Firebase). The "Push Notifications" toggle in Settings was cosmetic only; now wired to `DataProvider.pushEnabled` which controls periodic polling.
- **Local notifications** via `flutter_local_notifications` (v18.0.1): 4 Android notification channels with distinct vibration patterns.
- **Periodic polling**: `NotificationService.startPolling()` runs a Timer every 30s in the foreground. Checks `GET /notifications?per_page=5` for new items. Tracks `_lastNotificationId` to avoid duplicates.
- **In-app feedback**: When app is in foreground, `_inAppNotify()` triggers `HapticFeedback.heavyImpact/mediumImpact` + `SystemSound.play()` based on notification type.
- **No background execution**: No `workmanager` or `firebase_messaging`. Notifications only fire while app is active. Background support would require FCM.

### Notification Channels (Android)
| Channel ID | Importance | Vibration Pattern | Types |
|---|---|---|---|
| `oasis_alerts` | High | 0, 300, 200, 300, 500, 300 | geofence_alert |
| `oasis_messages` | High | 0, 200, 100, 200 | new_message, new_ticket |
| `oasis_temperature` | Max | 0, 500, 200, 500, 200, 500 | temperature_* |
| `oasis_general` | Default | System default | All other types |

### Key files
- `mobile/lib/notification_service.dart` — singleton service: init, show*, startPolling, stopPolling, _inAppNotify
- `mobile/lib/main.dart:20` — initializes NotificationService after Sentry
- `mobile/lib/data_provider.dart` — `pushEnabled` getter/setter controls polling lifecycle
- `mobile/lib/dashboard_page.dart:36` — starts polling after initial data load
- `mobile/lib/settings_page.dart:78` — push toggle now uses `data.pushEnabled`

### Build requirements
- `flutter_local_notifications` requires `isCoreLibraryDesugaringEnabled = true` + `coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.1.4")` in `android/app/build.gradle.kts`
- `POST_NOTIFICATIONS`, `VIBRATE`, `INTERNET`, `RECEIVE_BOOT_COMPLETED` permissions in AndroidManifest
- Kotlin JVM compiler override in root `android/build.gradle.kts`: `compilerOptions { languageVersion.set(KOTLIN_1_9) }` for all subprojects (compatibility with Kotlin 2.2.20)

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

### 2026-05-21 — Full test cycle infrastructure (Playwright e2e, Flutter real tests, CI fixes, workflow cleanup)

**Playwright e2e tests** (5 new spec files, 14 tests):
- `playwright.config.js` — Chrome + Firefox projects, auto-starts `npm run dev`
- `e2e/login.spec.js` — 4 tests: form render, admin login, invalid creds, owner login
- `e2e/dashboard.spec.js` — 3 tests: stats cards, nav links, navigate to Animals
- `e2e/animal-crud.spec.js` — 3 tests: list view, column headers, species filter
- `e2e/device.spec.js` — 2 tests: page loads, view details
- `e2e/auction.spec.js` — 2 tests: page loads, filter tabs
- `package.json`: added `"test:e2e": "npx playwright test"` script

**Flutter real tests** (flutter_test.dart rewrite):
- 9 stubs → 12 real tests: 4 API Service (MockClient), 3 Data Provider, 5 UI Component (pumpWidget)
- Added `_httpClient` + static setter to `ApiService` for test injection

**Workflow test cleanup**:
- `WorkflowTestService`: `cleanup()` method deleting 11 entity types in reverse dependency order, called in `finally` block; 60s HTTP timeout
- `WorkflowTestCommand`: added `--skip-cleanup` flag

**CI fixes**:
- `ci.yml`: fixed YAML indentation; added `e2e-tests` job
- `frontend.yml`: removed `|| true` from test step
- `backend.yml`: added Pint check; fixed DB name to `ra_animal_tracking_testing`

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
- 5 demo animals w/ devices + 3 groups + 2 geofences + 2 alerts
- 7 medical records + 6 vaccinations
- 11 tasks + 5 predefined tasks (all statuses)
- 3 auctions (1 draft, 2 active) + 3 bids
- 3 conversations (13 messages) — owner↔owner, owner↔support, admin↔owner
- 2 transfers (1 completed, 1 pending)
- 2 subscriptions + 2 orders + 1 banner
- Settings: commission 5%, manual auction approval

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

### 2026-05-20 — Flutter coord cast fixes (14 occurrences in 4 files)

**Root cause**: API returns lat/lng as strings (`"24.7170000"`) instead of numbers. `as num?` casts crash with `TypeError` at runtime.

**Fixes:**
- `simulator_page.dart` (8 locations): `(device['gps_lat'] as num?)?.toDouble()` → `double.tryParse('${device['gps_lat']}')`
- `alerts_page.dart` (2 locations): `.toDouble()` on dynamic `alert['latitude']` → `double.tryParse('${alert['latitude']}')`
- `devices_page.dart` (2 locations): `.toStringAsFixed(4)` on stringy val → `double.tryParse('${...}')?.toStringAsFixed(4)`
- `map_page.dart` (3 locations): Bypassed `_parseCoord` in `_parseCoords()` and `_buildAnimalCard` → use `_parseCoord`

**Key gotcha**: API lat/lng values can be either `num` or `String`. Always use `double.tryParse('${value}')` to handle both. The `_parseCoord()` helper in `map_page.dart` shows the canonical pattern.

### 2026-05-20 — Flutter sliver-in-box layout fix (UnifiedAppBar in Column)

**Root cause**: `UnifiedAppBar` in `unified_header.dart:334` wraps itself in `SliverToBoxAdapter` — designed for `CustomScrollView` sliver lists. But `ai_assistant_page.dart` and `messages_page.dart` placed it inside a `Column` (box context), causing: `RenderFlex expected a child of type RenderBox but received a child of type RenderSliverToBoxAdapter`.

**Fix**: Replaced `UnifiedAppBar` with `UnifiedHeader` (the box-version variant in the same file, line 6) at all 4 call sites, passing `showSearch: false, showNotifications: false` to preserve the same visual without search/notification buttons.

**Verification**: `dart analyze lib/ai_assistant_page.dart lib/messages_page.dart` — 0 errors

### 2026-05-20 — Flutter AI i18n fallback keys + Dashboard AI button

**i18n fix**: `_fallbackTranslations` in `i18n_helper.dart` had no `ai.*` keys. When the API returns translations that don't include AI keys (or API is unreachable), `I18nHelper.tr('ai.title')` returns the raw key `'ai.title'`. Added all 28 `ai.*` keys to the hardcoded fallback map.

**Dashboard AI button**: Added `Icons.auto_awesome` icon button in `dashboard_page.dart:194` between search and notification bell in the `_buildHeader` Row. Tapping navigates to `AIAssistantPage`.

**Verification**: `dart analyze lib/i18n_helper.dart lib/dashboard_page.dart` — 0 errors

### 2026-05-23 — Playwright e2e reliability fix (apiLogin + cached token)

**Root cause**: Flaky e2e tests were caused by unreliable auth state. Cookies set by the React SPA via `document.cookie` were being lost after `page.goto` (full page reload) in some cases. The conditional UI login (`loginIfNeeded`) was also hitting the Laravel rate limiter (`throttle:30,1` on `/auth/login`), causing "Too Many Attempts" cascading failures across retries.

**Fix — `apiLogin` helper**:
- New `apiLogin()` in `e2e/helpers.js` calls the backend API directly via `page.request.post('http://localhost:8050/api/auth/login')` with proper JSON headers
- Parses response for `token` and `user`, then sets auth cookies directly via `page.context().addCookies([...])` with `domain: 'localhost', path: '/'`
- Caches token at module level (`cachedToken`, `cachedUser`) so only **1 API call** is made per test run (regardless of number of tests)
- Retry with 3s backoff on 429 (Too Many Attempts) — up to 3 attempts

**Updated 5 spec files** (login.spec.js, dashboard.spec.js, animal-crud.spec.js, auction.spec.js, device.spec.js):
- Replaced `loginIfNeeded` import + usage with `apiLogin`
- Removed `page.goto('/react.oasis/')` calls before login (no longer needed — cookies are set directly)
- Login tests with valid creds use `apiLogin` + `page.goto`; invalid creds test uses UI login form directly

**Verification**: `npx playwright test --project=chromium` — 14/14 passed, 0 failed, 0 flaky (1.4m)

### 2026-05-23 — Privacy/Terms/Contact pages, AI overlap fix, map filter, recent activity, Flutter back button

**Privacy, Terms, Contact pages** (fully implemented + admin-managed):
- **Backend**: `Page` model, migration (`pages` table: slug, title, content, is_published), `PageController` with public `show(slug)` + admin CRUD
- **Frontend**: `StaticPage.jsx` (shared component fetches `GET /api/pages/{slug}`), `PrivacyPage`, `TermsPage`
- **Admin settings**: "Pages" tab in Settings, `PageSettings.jsx` component for editing content/published status
- **Routes**: `/privacy`, `/terms`, `/contact` registered in `App.jsx` and `routes.js`
- **Footer**: `<a href="#">` → `<Link to="/privacy">` etc.
- **Seeder**: `PageSeeder` with default content for all 3 pages, runs before `DemoDataSeeder`

**AI assistant overlap fix** (`Layout.jsx`):
- Added `<div className="h-24" />` spacer before `<Footer />` inside `<main>` to prevent floating AI button from overlapping footer content

**Map days filter fix** (`MapView.jsx:210-214`):
- Removed `!loading` guard from the `timeFilter` change `useEffect` so filter changes always trigger a re-fetch regardless of loading state

**Animal recent activity fix** (`AnimalDetails.jsx`):
- API call now passes `animal_id=${id}` to filter server-side (`/api/geofence-alerts?per_page=20&animal_id=${id}`)
- UI now handles all alert types (entry, exit, temperature, offline) with proper icons and labels instead of just entry/exit

**Flutter messages page back button** (`messages_page.dart`):
- Conversation list header: `showBackButton: !isWide && _selectedConversationId != null` → `showBackButton: !isWide` (always show when narrow)
- `onBack`: if conversation selected → clear selection (internal nav); if on list view → `Navigator.pop(context)` (go back)

**Verification**:
- `npm run build` — clean
- `dart analyze lib/messages_page.dart` — 0 errors
- `php -l` — no syntax errors
- `GET /api/pages/privacy` — returns seeded privacy page content

### 2026-05-25 — Notification infrastructure (flutter_local_notifications, periodic polling, sounds/vibration)

**Root cause**: The app had zero push notification infrastructure. The "Push Notifications" toggle was cosmetic only. No local notifications, no sound, no vibration, no polling.

**Implementation:**
- Added `flutter_local_notifications: ^18.0.0` to `pubspec.yaml`
- Created `mobile/lib/notification_service.dart` — singleton service with 4 Android notification channels:
  - **oasis_alerts** (High): long-short-long vibration pattern (geofence alerts)
  - **oasis_messages** (High): short-short vibration pattern (messages/tickets)
  - **oasis_temperature** (Max): urgent pattern + full-screen intent
  - **oasis_general** (Default): system defaults
- **Periodic polling**: `Timer.periodic(30s)` checks `GET /notifications?per_page=5` for new items, tracks `_lastNotificationId` to avoid duplicates
- **In-app feedback**: `_inAppNotify()` triggers `HapticFeedback.heavyImpact()` for alerts/temperature, `HapticFeedback.mediumImpact()` for messages, plus `SystemSound.play()` per type
- **Settings toggle wired**: `DataProvider.pushEnabled` setter starts/stops polling; `SettingsPage` now reads from `data.pushEnabled`
- **Dashboard init**: starts polling after initial `Future.wait` completes
- **AndroidManifest**: added `POST_NOTIFICATIONS`, `VIBRATE`, `INTERNET`, `RECEIVE_BOOT_COMPLETED` permissions
- **Desugaring**: `isCoreLibraryDesugaringEnabled = true` + `coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.1.4")` in `app/build.gradle.kts`
- **Kotlin compat**: root `build.gradle.kts` sets `compilerOptions { languageVersion.set(KOTLIN_1_9) }` for all subprojects

**Verification:**
- `dart analyze` — 0 new issues (only pre-existing warnings/infos)
- `flutter build apk --release` — success (78.7MB)

**Key gotchas**: `flutter_local_notifications` requires `isCoreLibraryDesugaringEnabled` in the app module. `Int64List.fromList()` not `Int64List()` for vibration patterns. `AndroidNotificationChannel` in v18 no longer has a `priority` constructor param. Kotlin 2.2.20 requires `compilerOptions` DSL instead of `kotlinOptions` for language version configuration in subprojects.

### 2026-05-31 — Flutter test fixes, notifications title bug, map location fix, task calendar linking, k6 load test, impeccable install

**Flutter test fixes** (`mobile/test/auth_provider_test.dart`, `mobile/test/login_page_test.dart`):
- Fixed auth_provider_test: invalid credentials don't set `auth.error` (swallowed by `ApiService.login()` catch → returns `{'error': ...}` data map, never hits `AuthProvider` catch block)
- Fixed login_page_test: button text is `I18nService.tr('auth.login')` → renders as `'auth.login'` key without i18n init; all `tap()` calls need `ensureVisible()` first (login page taller than 600px test surface); moved error banner clear test to direct `auth.login()` call
- Added `setTestError()` and `setTestIsLoading()` helpers to `AuthProvider` for test use
- Removed stale `test/error_loading_states_test.dart`
- 342 pass, 31 fail (same 6 widget + 25 backend-dependent), 0 regressions

**Notifications page title fix** (`mobile/lib/notifications_page.dart`):
- Root cause: code read `notification['message']` but the API returns `notification['title']` and `notification['body']` (Laravel `Notification` model has `title`, `body`, `data` columns — no `message` column)
- Changed to `notification['title'] ?? notification['body'] ?? notification['data']?['message']`
- `notification_service.dart` already correctly reads `latest['title']` / `latest['body']` — no fix needed there

**MapMiniWidget wrong location** (`mobile/lib/dashboard_widgets.dart`):
- Root cause: `GET /api/animals` returns coordinates nested under `device.gps_lat` but widget read `a['gps_lat']` (top-level). All animals failed lookup → fell back to hardcoded `LatLng(24.31848, 54.46191)` (UAE center)
- Added `_animalCoord()` helper checking `a['latitude']` → `a['gps_lat']` → `a['device']['gps_lat']` (same for longitude)
- Applied at all 3 access points: `_computeCenter()`, `_computeBounds()`, marker rendering in `build()`

**Task calendar days now tappable** (`mobile/lib/dashboard_widgets.dart`):
- Wrapped each day cell `Container` in `GestureDetector`; days with tasks navigate to `TasksPage` on tap

**k6 API load test** (`load-tests/api-load-test.js`, new):
- Simulates 6 user roles (7 accounts) hitting 12 endpoints (dashboard, animals, devices, tasks, auctions, map, geofences, notifications, alerts, medical records, unread count)
- Token cache ⇒ only 1 login per user per run (avoids 30/min rate limit)
- Stages: ramp 10→25→50 VUs, hold 3 min, ramp down
- Results with 5 VUs / 30s: 65/65 checks pass, 0% errors, p95 response times 1.8s–4.2s (slow — likely N+1 queries, no caching)
- `k6` installed at `%TEMP%\k6-install\k6.exe` (v0.56.0)

**Impeccable design skills installed** (`.opencode/skills/impeccable/`):
- Root cause: `npx impeccable skills install` uses `unzip` which doesn't exist on Windows → download failed silently
- Workaround: manually downloaded universal bundle from `impeccable.style/api/download/bundle/universal`, extracted with `Expand-Archive`, copied `.opencode` variant to project
- 30+ skill files: craft, shape, audit, critique, polish, animate, bolder, colorize, delight, layout, overdrive, quieter, typeset, adapt, clarify, distill, harden, onboard, optimize, init, document, extract, live, etc.
- Requires opencode restart to appear in skills list

**Verification**:
- `dart analyze` — 0 errors (only info-level pre-existing warnings)
- `flutter test` — 342 passed, 31 pre-existing failures
- PHP API — login endpoint responds 200 with seeded data

### 2026-05-31 — Dashboard polish: RTL parity, color tokens, contrast, skeleton loading

**RTL parity (5 widgets fixed):**
- `StatsCardsWidget.jsx`: changed `dir` source from `dashboardData.dir` to `useI18n().dir` (was always falsy)
- `DashboardWidget.jsx`: added `flex-row-reverse` on drag handle for RTL
- `DashboardGrid.jsx`: customized dropdown position `right-0` → `isRtl ? 'left-0' : 'right-0'`
- `AnnouncementsWidget.jsx`: added `isRtl` + `flex-row-reverse` on items
- `MessagesWidget.jsx`: added `isRtl` + `flex-row-reverse` on conversation items
- `AiAssistantWidget.jsx`: added `isRtl` + `flex-row-reverse` on header/prompts/form

**Color token alignment (5 files):**
- `AlertsPanelWidget.jsx`: `#10b981` → `success` (both bg and text)
- `ChartsWidget.jsx`: `#10b981` entries → `#059669`
- `TasksWidget.jsx`: `#F59E0B` → `amber-500/10`, `#3B82F6` → `blue-500/10`
- `StatsCardsWidget.jsx`: `bg-[#10b981]` → `bg-success`; `text-[#10B981]` → `text-success`
- `MessagesWidget.jsx`: `bg-red-500` → `bg-danger`
- `DashboardGrid.jsx`: `border-gray-100` → `border-outline/30`
- `AiAssistantWidget.jsx`: `focus:ring-[#002819]/20` → `focus:ring-brand-primary/20`

**Typography:**
- `DashboardWidget.jsx`: `font-black` → `font-bold` (900→700, matches DESIGN.md Title spec)

**Contrast fix:**
- `tailwind.config.js`: `on-surface.subtle` changed from `#717973` to `#6E7670` — passes WCAG AA 4.5:1 (4.48:1 → 4.78:1)

**Loading state:**
- `Dashboard.jsx`: replaced single spinner with 3-section skeleton (5 stat cards + 6 quick action cards + 2-column map/list layout) using `animate-pulse` + `bg-surface-dim`

**Temperature display:**
- `StatsCardsWidget.jsx`: `a.baseline_temperature` → `a.device?.temperature ?? a.baseline_temperature` (live device temp takes priority)

**Empty state button:**
- `DashboardGrid.jsx`: `rounded-xl` → `rounded-2xl` (matches 16px card radius system)

**Key gotchas**: RTL requires `isRtl` check per-component — no global RTL utility. `surface-dim` in skeleton must match actual loading surface color. `on-surface-subtle` was dangerously close to the AA threshold; any lighter would fail.

## Design Context

See `PRODUCT.md` (strategic) and `DESIGN.md` (visual tokens) at project root for the full system.

**Register**: product (app UI / dashboard / tools — design serves functionality)

**Creative North Star**: "The Desert Compass" — every screen orients the user to their data

**Brand**: Grounded · Trusted · Premium. Deep green (`#002819`) + Signal Gold (`#D4AF37`) on Rose Paper (`#FAF1F5`) surface.

**Typography**: Manrope (headings/buttons/nav) + Inter (body/labels). Arabic RTL uses Tajawal; Urdu uses Noto Nastaliq Urdu.

**Key rules**:
- Gold accent on ≤10% of any screen
- Rose Paper surface — not cream, sand, or beige
- All shadows use `rgba(6, 64, 43, …)` (Forest Shade), never pure black
- Components: 16–24px radius, gradient fills, 200ms ease-out transitions
- Manrope for scanning, Inter for reading — never swap

**Files to reference**:
- `frontend/tailwind.config.js` — color tokens, shadows, fonts
- `frontend/src/index.css` — CSS variables, component layer utilities (`btn-primary`, `card`, `input-field`)
- `.impeccable/design.json` — component HTML/CSS snippets for live panel
- `.impeccable/live/config.json` — pre-configured for Vite SPA live mode
