# Limit/Tier System Analysis — Oasis Trace Backend

## 1. How Tier Limits Work

### Data Model (`SubscriptionTier` model)
Each tier has three **numeric limits** and seven **boolean feature flags**:

| Field | Type | Semantics |
|---|---|---|
| `max_animals` | int | Max animals allowed (0 = unlimited) |
| `max_devices` | int | Max devices allowed (0 = unlimited) |
| `max_users` | int | Max team members allowed (0 = unlimited) |
| `has_geofencing` | bool | Feature access |
| `has_auctions` | bool | Feature access |
| `has_medical_records` | bool | Feature access |
| `has_tasks` | bool | Feature access |
| `has_advanced_reports` | bool | Feature access |
| `has_api_access` | bool | Feature access |
| `has_ai_assistant` | bool | Feature access |

### Seed Tiers
| Tier | Animals | Devices | Users | Features disabled |
|---|---|---|---|---|
| Free | 5 | 5 | 1 | `has_advanced_reports` |
| Starter ($99/mo) | 20 | 20 | 3 | `has_advanced_reports` |
| Professional ($299/mo) | 100 | 100 | 10 | *(all enabled)* |
| Enterprise ($799/mo) | ∞ (0) | ∞ (0) | ∞ (0) | *(all enabled)* |

### Enforcement Logic (`CheckSubscriptionLimits` middleware)
- **Resource types** handled via `$resource` parameter: `animals`, `devices`, `users`, `geofences`, `auctions`.
- **Admins bypass** all checks (line 20-22).
- **Only write methods** are checked: `POST`, `PUT`, `PATCH` (`$isCreating`). GET/DELETE pass through unconditionally.
- **Numeric limits** (`animals`, `devices`, `users`): fetches current count via `$user->get{*}Count()` and compares to `$tier->max_{*}`. Blocked when `$currentCount >= $maxAllowed` (and `$maxAllowed !== 0`). Returns 403 with limit details.
- **Feature gates** (`geofences`, `auctions`): checks `$tier->has_geofencing` / `$tier->has_auctions`. Blocks if feature is false. No count checks.
- **No tier**: returns 403 on writes; passes on reads. Testing environment bypasses the 403.

### `FeatureGate` Service
A secondary feature-check system used by `CheckFeatureAccess` middleware. Supports 7 features via `match`. Has its own `checkLimit()` and `getUserFeatures()` methods with **different fallback defaults** (`5` animals/devices, `1` user) when no tier is found.

### User Model Limit Methods (`isOver*Limit`)
Three methods: `isOverUserLimit()`, `isOverAnimalLimit()`, `isOverDeviceLimit()`. Use **strict greater-than** (`>`), treat `0` as unlimited. **These methods are dead code** — no middleware or controller calls them.

### User Count Semantics
`getUserCount()` counts users where `managed_by = $this->id`. This only captures **direct reports**, not the entire hierarchy. An Owner's team members are those they manage, not all users in the system.

---

## 2. Routes with Limits Middleware

### `limits:animals`
- `GET /animals/stats`
- All `apiResource('animals')` routes (index, store, show, update, destroy)

### `limits:devices`
- All `apiResource('devices')` routes
- `POST /devices/provision`
- `POST /devices/batch`

### `limits:users`
- `POST /users` (store — correct)
- `GET /users/{user}` (show — unnecessary, passes through on GET but middleware still runs)
- `PUT/PATCH /users/{user}` (update — **incorrect**: updating a user should not count as creating a new team member)

### `limits:geofences`
- All geofence routes (index, store, show, update, destroy, assign animals/groups, etc.)

### `limits:auctions`
- All auction routes (index, store, show, update, destroy, bid, payment, etc.)

### Routes **without** any limits or feature middleware:
- **Medical records** (all CRUD) — no `limits:` or `feature:` middleware
- **Tasks** (all CRUD) — no limits
- **Vaccination schedules** — no limits
- **Animal groups** — no limits
- **AI** (`/ai/chat`) — no limits
- **Reports** — no limits (though `has_advanced_reports` exists)

### Note on `feature:` middleware
The `feature:` middleware alias is registered but **never used in routes/api.php**. The `CheckFeatureAccess` middleware exists but is not applied anywhere. Feature gates for geofences and auctions are handled directly inside `CheckSubscriptionLimits`.

---

## 3. Consistency of Enforcement

### Inconsistencies found

#### A. `>=` vs `>` for limit checks
- Middleware uses `$currentCount >= $maxAllowed` (blocks AT the limit)
- `isOver*Limit()` methods use `$currentCount > $maxAllowed` (blocks only when OVER)
- The middleware correctly prevents creating the resource that would exceed the limit; the model methods would only detect a violation that already happened

#### B. PUT/PATCH counted as "creating"
The middleware treats `PUT` and `PATCH` as `$isCreating = true`. This means:
- Updating a user (e.g., changing name/email) checks the team member limit
- Updating an animal checks the animal limit
- This is incorrect — only `POST` (store) should count as a new resource

#### C. Feature gates not enforced for medical_records, tasks, ai_assistant, advanced_reports, api_access
The `CheckSubscriptionLimits` middleware only handles `geofences` and `auctions` via feature flags. The other 5 feature flags (`has_medical_records`, `has_tasks`, `has_ai_assistant`, `has_advanced_reports`, `has_api_access`) exist in the model and `FeatureGate` service, but have **zero route enforcement**. A Free tier user can access all medical record and task routes despite these being gated features conceptually.

#### D. FeatureGate vs middleware behavior divergence
- `FeatureGate::getUserTier()` falls back to the `free` tier if `subscription_tier_id` is null
- `CheckSubscriptionLimits` returns 403 if tier is null and it's a write request
- `FeatureGate::checkLimit()` defaults to `max_animals=5, max_devices=5, max_users=1` for null tiers
- These different fallback strategies produce different behaviors for the same user state

#### E. No subscription status validation
The middleware only checks if `$user->subscriptionTier` exists (the relationship). It does NOT verify:
- Whether the subscription is active, paused, cancelled, or expired
- Whether the user has an active `UserSubscription` record
- A user with a stale subscription still gets full access

#### F. Downgrade/cancel doesn't enforce new limits
When a user cancels (moved to Free tier) or downgrades, there is no check that their current counts fit within the new tier's limits. A user with 100 animals could be downgraded to Free (max 5) with no data access restrictions or warnings.

---

## 4. Gaps and Issues Summary

| # | Issue | Severity | File |
|---|---|---|---|
| 1 | PUT/PATCH falsely trigger creation limits | Medium | `CheckSubscriptionLimits.php:25` |
| 2 | `isOver*Limit()` methods are dead code — never invoked | Low | `User.php:143-162` |
| 3 | Medical records, tasks, AI, advanced reports, API access have no middleware enforcement | High | `routes/api.php` |
| 4 | No subscription status validation (expired/cancelled users keep access) | High | `CheckSubscriptionLimits.php:24` |
| 5 | Downgrade/cancel doesn't enforce new tier limits | Medium | `SubscriptionController.php` methods |
| 6 | FeatureGate fallback defaults conflict with middleware behavior | Medium | `FeatureGate.php:52-55` vs `CheckSubscriptionLimits.php:32-40` |
| 7 | Race condition: count is checked before DB insert — no atomicity | Medium | `CheckSubscriptionLimits.php:88-91` |
| 8 | `limits:users` applied to `GET/PUT/PATCH /users/{id}` is unnecessary/incorrect | Low | `routes/api.php:101-103` |
| 9 | Geofence and auction routes have no count limit, only feature gate | Low | `CheckSubscriptionLimits.php:66-83` |
| 10 | `$isCreating` includes PUT/PATCH, but the name suggests only POST | Low | `CheckSubscriptionLimits.php:25` |
| 11 | `getUserCount()` only counts direct reports (`managed_by`), may undercount | Low | `User.php:128-131` |

### Recommended Fixes (in priority order)
1. Add `feature:medical_records`, `feature:tasks`, `feature:ai_assistant`, `feature:advanced_reports` middleware to the relevant route groups
2. Check subscription status (active/paused/expired) in the middleware, not just tier existence
3. On downgrade/cancel, check if the user exceeds the new tier's limits and either block or warn
4. Change PUT/PATCH to not count as `$isCreating`, or rename the variable and separate update from create
5. Make the count check atomic (e.g., `DB::transaction` with lock or use `forceCreate` with a database check)
6. Remove `limits:users` from the show/update/destroy user routes, keeping it only on `POST /users`
