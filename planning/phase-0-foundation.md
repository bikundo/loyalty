# Phase 0 — Foundation
### LoyaltyOS Africa · Project Scaffolding & Core Infrastructure

> **Timeline:** Weeks 1–3 (before any product feature ships)
> **Goal:** A running Laravel app with no visible product yet — but with every infrastructure concern locked down correctly so every feature built on top of it is correct by default.
> **Exit criteria:** All migrations run, all model traits in place, all guards configured, Horizon running, Sentry connected, tenant isolation verified by tests, CI passing.

---

## Why Phase 0 Exists

Multi-tenancy, encryption, guard separation, and audit logging are **foundational constraints** — not features you bolt on later. Getting them wrong early means retrofitting every model, every route, and every test. Phase 0 exists to make these constraints load-bearing from day one.

---

## 0.1 Project Scaffolding

### Laravel App Initialisation
- [ ] Fresh Laravel 13 install with PHP 8.4
- [ ] Configure `.env` — app name, URL, DB, Redis, queue driver (`redis`), cache driver (`redis`)
- [ ] Set `APP_TIMEZONE=Africa/Nairobi`
- [ ] Set `APP_LOCALE=en`
- [ ] Configure `config/app.php` — timezone, locale, faker locale (`en_KE`)

### Code Quality Tools
- [ ] Install and configure **Laravel Pint** — `pint.json` preset
- [ ] Install **Pest 4** with Laravel plugin
- [ ] Configure `phpunit.xml` / `pest.php` — use in-memory SQLite for unit tests, separate test DB for feature tests
- [ ] Set up **GitHub Actions** CI — run `vendor/bin/pint --test` + `php artisan test --compact` on every push
- [ ] Configure `.editorconfig` — 4-space indent, LF line endings

### Observability (from day 0)
- [ ] Install **Sentry Laravel SDK** — configure `SENTRY_DSN` in `.env`
- [ ] Install **Laravel Telescope** — `APP_ENV=local` guard, never in production
- [ ] Install **Laravel Pulse** — production performance monitoring
- [ ] Install **Laravel Horizon** — configure `config/horizon.php` with three queues (`high`, `default`, `low`)

### Storage
- [ ] Configure **AWS S3** driver in `config/filesystems.php`
- [ ] `FILESYSTEM_DISK=s3` in production
- [ ] Local disk for development

---

## 0.2 Database Setup

### Core Configuration
- [ ] Primary: **MySQL 8** — `DB_CONNECTION=mysql`
- [ ] Cache + Sessions + Queues: **Redis** — `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`
- [ ] Install **Predis** as Redis client

### Migrations — Full Dependency Order

Run `php artisan make:migration` for each. Create in this exact order (foreign key dependencies):

| # | Table | Key Dependencies |
|---|-------|-----------------|
| 1 | `plans` | none |
| 2 | `users` | none (tenant_id added later) |
| 3 | `tenants` | plans, users |
| 4 | Add `tenant_id` to `users` | tenants |
| 5 | `tenant_settings` | tenants |
| 6 | `tenant_locations` | tenants |
| 7 | `cashiers` | tenants, tenant_locations, users |
| 8 | `loyalty_programs` | tenants |
| 9 | `loyalty_rules` | loyalty_programs, tenants |
| 10 | `customers` | tenants |
| 11 | `customer_consents` | customers, tenants |
| 12 | `customer_referrals` | tenants, customers |
| 13 | `rewards` | loyalty_programs, tenants |
| 14 | `point_transactions` | customers, tenants, tenant_locations, loyalty_rules |
| 15 | `redemptions` | customers, rewards, tenants, tenant_locations, point_transactions |
| 16 | `sms_wallets` | tenants |
| 17 | `sms_credit_purchases` | tenants, sms_wallets |
| 18 | `campaigns` | tenants, users |
| 19 | `campaign_recipients` | campaigns, customers |
| 20 | `sms_logs` | tenants, campaigns, customers |
| 21 | `tenant_api_settings` | tenants |
| 22 | `tenant_api_keys` | tenants, users |
| 23 | `api_request_logs` | tenants, tenant_api_keys |
| 24 | `audit_logs` | tenants |
| 25 | `subscriptions` | tenants, plans |
| 26 | `payment_transactions` | tenants |
| 27 | `fraud_flags` | tenants, users |
| 28 | `notification_queue` | customers, tenants |
| 29 | `merchant_health_scores` | tenants |
| 30 | Spatie permission tables | (run via `artisan vendor:publish`) |

**All migrations must include:**
- Composite indexes on `(tenant_id, ...)` for every tenant-scoped table
- Soft deletes (`deleted_at`) on business-critical tables: `users`, `tenants`, `tenant_locations`, `cashiers`, `customers`, `loyalty_programs`, `loyalty_rules`, `rewards`
- No `updated_at` on `point_transactions` and `audit_logs` (immutable ledgers)

---

## 0.3 Eloquent Model Traits

Create these traits before writing any models. Every model that uses them must have them applied at creation time — retrofitting is dangerous.

### `BelongsToTenant`
Path: `app/Models/Concerns/BelongsToTenant.php`

- Global scope: injects `WHERE tenant_id = ?` on every query
- `creating` hook: auto-sets `tenant_id` from `app('tenant.context')`
- `tenant()` relation: `belongsTo(Tenant::class)`
- Static `withoutTenantScope()` helper for admin bypass

**Applied to:** `TenantSettings`, `TenantLocation`, `Cashier`, `LoyaltyProgram`, `LoyaltyRule`, `Customer`, `CustomerConsent`, `CustomerReferral`, `Reward`, `PointTransaction`, `Redemption`, `SmsWallet`, `SmsCreditPurchase`, `Campaign`, `CampaignRecipient`, `SmsLog`, `TenantApiKey`, `TenantApiSettings`, `ApiRequestLog`, `AuditLog`, `Subscription`, `PaymentTransaction`, `FraudFlag`, `NotificationQueue`, `MerchantHealthScore`

### `HasUuid`
Path: `app/Models/Concerns/HasUuid.php`

- `creating` hook: generates UUID if empty
- `getRouteKeyName()` returns `'uuid'` — all routes resolved by UUID, not integer ID

**Applied to:** `User`, `Tenant`, `TenantLocation`, `Cashier`, `Customer`, `LoyaltyProgram`, `LoyaltyRule`, `Reward`, `PointTransaction`, `Redemption`, `Campaign`

### `Auditable`
Path: `app/Models/Concerns/Auditable.php`

- `updated` hook: writes to `audit_logs` via `AuditLog::record()`
- `deleted` hook: writes to `audit_logs`

**Applied to:** `LoyaltyRule`, `Reward`, `PointTransaction` (voids only), `Cashier`, `TenantApiKey`, `Subscription`

---

## 0.4 Models

Create all models with correct traits, casts, relations, and fillable arrays. Use `php artisan make:model` for each.

### Encryption Casts
`Customer` model must cast:
```php
protected $casts = [
    'phone' => 'encrypted',
    'name'  => 'encrypted',
];
```

### Key Model Relationships to Define

| Model | Relations |
|-------|-----------|
| `Tenant` | hasOne TenantSettings, hasMany TenantLocations, hasMany Cashiers, hasMany Customers, hasMany LoyaltyPrograms, hasOne SmsWallet, hasMany Campaigns, hasOne TenantApiSettings, hasMany TenantApiKeys, hasMany Subscriptions |
| `LoyaltyProgram` | belongsTo Tenant, hasMany LoyaltyRules, hasMany Rewards |
| `Customer` | belongsTo Tenant, hasMany PointTransactions, hasMany Redemptions, hasMany CustomerConsents, belongsTo Customer (referredBy) |
| `PointTransaction` | belongsTo Customer, belongsTo Tenant, belongsTo TenantLocation, belongsTo LoyaltyRule |
| `Redemption` | belongsTo Customer, belongsTo Reward, belongsTo PointTransaction |
| `SmsWallet` | belongsTo Tenant, hasMany SmsCreditPurchases |
| `Campaign` | belongsTo Tenant, hasMany CampaignRecipients, hasMany SmsLogs |

---

## 0.5 Multi-Tenancy Infrastructure

### Tenant Context Container
Path: `app/Services/TenantContext.php`

- Singleton bound in `AppServiceProvider`
- `set(Tenant $tenant)` — sets current tenant
- `id()` — returns current tenant ID, `null` for super admin context
- `current()` — returns full `Tenant` model
- `forgetTenant()` — clears context (for testing)

### Tenant Resolution Middleware
Path: `app/Http/Middleware/ResolveTenant.php`

Resolution strategies (try in order):
1. **Subdomain** — `nakustore.loyaltyos.co.ke` → look up by `tenants.subdomain`
2. **API Key header** — `X-API-Key` → resolve tenant from `tenant_api_keys`
3. **Bearer token** — OAuth2 bearer → resolve tenant from token claims

If no tenant resolved and route requires one → `403 Forbidden`.

Register in route groups:
- `web.tenant` group: subdomain resolution
- `api.tenant` group: API key or bearer resolution

### Admin Context
- `php artisan` commands and super admin panel routes bypass tenant scoping
- Use `TenantContext::forgetTenant()` to explicitly clear context in admin contexts
- Global scope has early return when `tenant.context->id()` is null

---

## 0.6 Authentication & Guards

### Guards Configuration (`config/auth.php`)

```
guards:
  web      → users table    (super_admin, merchant_owner, merchant_manager)
  cashier  → cashiers table (PIN auth, scanner app)
  customer → customers table (OTP auth, customer app)
  api      → token-based (API key / OAuth2 bearer)
```

### Fortify Configuration
- Enable: `login`, `register` (merchant owners), `email-verification`, `reset-passwords`
- Disable: `two-factor-authentication` for web guard (use manual TOTP for super admin only)
- Custom `LoginResponse` — redirect merchant owner to `/dashboard`, super admin to `/admin`
- Route guards: web dashboard routes protected by `auth:web`

### Super Admin 2FA
- Manual TOTP implementation using `pragmarx/google2fa-laravel`
- Required on super admin login only (not merchant owners in MVP)
- 2FA secret stored in `users.two_factor_secret`
- Backup codes not required in MVP

### Cashier PIN Auth
- Custom `CashierGuard` — queries `cashiers` table, verifies bcrypt PIN
- Session-less — token returned on successful PIN entry
- Route middleware: `auth:cashier` — rejects non-PIN sessions even if other guards are active
- No password reset flow — owner creates new PIN via dashboard

### API Guard
- `Sanctum`-based for API key auth
- Custom middleware `AuthenticateApiKey` — looks up `tenant_api_keys` by hashed key
- Returns `401` with JSON error for invalid/revoked keys

---

## 0.7 Roles & Permissions

### Setup
- Install **Spatie Laravel Permission**
- Publish config and migration
- Enable `teams` feature for tenant-scoped role assignment

### Constants Classes
Path: `app/Enums/Role.php` and `app/Enums/Permission.php`

Using PHP 8 backed string enums — never reference role/permission names as raw strings in application code.

```php
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case MerchantOwner = 'merchant_owner';
    case MerchantManager = 'merchant_manager';
    case Cashier = 'cashier';
    case Customer = 'customer';
    case ApiClient = 'api_client';
}
```

### Seeder
Path: `database/seeders/RolesAndPermissionsSeeder.php`

- Create all roles
- Create all permissions (see master plan §9 for full list)
- Assign permissions to roles

### Super Admin Gate Bypass
In `AppServiceProvider::boot()`:
```php
Gate::before(function (User $user) {
    if ($user->hasRole(Role::SuperAdmin)) {
        return true;
    }
});
```

---

## 0.8 Horizon Queue Configuration

### Queues

| Queue | Priority | Workers | Job Types |
|-------|----------|---------|-----------|
| `high` | Highest | 2 | `AwardPointsJob`, `SendTransactionalSmsJob` |
| `default` | Normal | 4 | `DispatchCampaignJob`, `SendSmsBatchJob`, `ProcessPaymentJob` |
| `low` | Background | 1 | `ExpireStalePointsJob`, `ReconcileSmsCreditsJob`, `PurgeExpiredDataJob`, `CalculateMerchantHealthScoresJob` |

### Horizon Settings
- `config/horizon.php` — define the three environments (local, staging, production)
- Rate limits per tenant — prevent one large merchant's campaign batch from starving other queues
- Metrics dashboard — accessible to `super_admin` only (Horizon auth gate)
- Slack notification on queue failure

---

## 0.9 Factories & Seeders

Create factories for **every model** before writing any tests. Factories are the testing infrastructure.

| Factory | Key States |
|---------|-----------|
| `TenantFactory` | `->active()`, `->suspended()`, `->onPlan(Plan $plan)` |
| `UserFactory` | `->superAdmin()`, `->merchantOwner(Tenant $t)`, `->merchantManager(Tenant $t)` |
| `CustomerFactory` | `->active()`, `->inactive()`, `->withApp()` |
| `LoyaltyRuleFactory` | `->visitRule()`, `->spendRule()`, `->birthdayRule()` |
| `PointTransactionFactory` | `->earn()`, `->redeem()`, `->expire()` |
| `CampaignFactory` | `->draft()`, `->completed()`, `->failed()` |
| `SmsWalletFactory` | `->withBalance(int $credits)`, `->empty()`, `->lowBalance()` |

### Seeders
- `RolesAndPermissionsSeeder` — runs in production on deploy
- `PlanSeeder` — seeds the four subscription plans
- `DevelopmentSeeder` — seeds test merchants, customers, transactions (local only)

---

## 0.10 Testing Foundation

### Tenant Isolation Tests
Path: `tests/Feature/MultiTenancy/TenantIsolationTest.php`

**These tests run on every deployment — non-negotiable:**
- Tenant A cannot read Tenant B's customers
- Tenant A cannot read Tenant B's point transactions
- Tenant A cannot read Tenant B's rewards or rules
- Merchant owner cannot access admin routes
- Cashier cannot access dashboard routes (guard restriction)
- Eloquent global scope applied on all 20+ tenant-scoped models
- `unique` and `exists` validation rules are tenant-scoped

### Architecture Tests
Path: `tests/Architecture/`

Using Pest's `arch()` helper:
- All tenant-scoped models use `BelongsToTenant` trait
- All externally-exposed models use `HasUuid` trait
- No raw SQL in controllers (use Eloquent)
- All jobs implement `ShouldQueue` and `Middleware\WithoutOverlapping`

---

## 0.11 CI/CD Pipeline

### GitHub Actions Workflow
```
on: push to any branch
steps:
  1. composer install
  2. vendor/bin/pint --test (fail on style violations)
  3. php artisan test --compact (fail on test failures)
  4. php artisan migrate --env=testing (verify all migrations run)

on: push to main
steps:
  + deploy to staging (Envoyer or Forge)
  + run php artisan migrate --force
  + restart Horizon workers
```

---

## Phase 0 Exit Checklist

Before starting Phase 1, every item below must be ✅:

- [ ] All 30 migrations run cleanly on a fresh database
- [ ] All model traits applied (`BelongsToTenant`, `HasUuid`, `Auditable`)
- [ ] Phone and name fields `encrypted` cast active on `customers`
- [ ] All four auth guards configured and returning correct responses
- [ ] All roles and permissions seeded
- [ ] Horizon running with three queues
- [ ] Sentry receiving test events
- [ ] Tenant isolation test suite passes (all assertions green)
- [ ] Architecture tests pass
- [ ] CI pipeline running on every push
- [ ] Factory for every model exists

---

*Phase 0 builds the floor. Everything else is walls and roof.*
