# Third-Party Laravel & PHP Packages
### LoyaltyOS Africa · Complete Dependency Reference

> All packages required across Phase 0, 1, 2, and 3.
> Packages already installed in the current repo are marked.
> Do not install packages without updating this file.

---

## Already Installed (Current Repo)

These are confirmed in `composer.json` / AGENTS.md — do not reinstall:

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | v13 | Core framework |
| `laravel/fortify` | v1 | Auth backend (login, register, password reset, email verify) |
| `laravel/prompts` | v0 | CLI prompts for Artisan commands |
| `livewire/livewire` | v4 | Reactive UI components |
| `livewire/flux` | v2 | Flux UI free component library |
| `livewire/flux-pro` | v2 | **Flux UI Pro** — licensed; provides advanced components: data tables, date pickers, command palette, kanban, and more. Required for the merchant dashboard and admin panel. |
| `laravel/boost` | v2 | MCP tooling for this project |
| `laravel/mcp` | v0 | Model Context Protocol |
| `laravel/pail` | v1 | Real-time log tailing |
| `laravel/pint` | v1 | Code formatter (enforced on all PHP files) |
| `pestphp/pest` | v4 | Testing framework |
| `phpunit/phpunit` | v12 | PHPUnit (Pest runs on top) |

---

## Phase 0 — Foundation

Packages required before any product feature can be built.

### Multi-Tenancy

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `stancl/tenancy` | `composer require stancl/tenancy` | Tenant context management, future single-to-dedicated-DB migration path | 0 |

**Note:** We are using single-database, shared-schema tenancy. `stancl/tenancy` is used for the `TenantContext` container and middleware resolution, and to future-proof for the enterprise dedicated-DB escape hatch. We are **not** using the full tenancy auto-migration features.

### Auth & Permissions

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `spatie/laravel-permission` | `composer require spatie/laravel-permission` | Role-based access control — 6 roles, ~40 permissions | 0 |
| `pragmarx/google2fa-laravel` | `composer require pragmarx/google2fa-laravel` | TOTP 2FA for super admin login | 0 |
| `bacon/bacon-qr-code` | `composer require bacon/bacon-qr-code` | QR code generation for 2FA setup (dependency of google2fa) | 0 |

### Queue & Job Processing

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `laravel/horizon` | `composer require laravel/horizon` | Queue monitoring, worker management, rate limiting per tenant | 0 |

### Redis Client

**Use the native PHP `phpredis` extension — not Predis.**

Herd ships with `phpredis` (the C extension) pre-compiled. It is significantly faster than Predis (pure PHP) and has zero Composer dependencies. Laravel auto-detects `phpredis` when the extension is loaded.

Verify it is active:
```bash
herd php:list  # confirm PHP 8.4 is active
php -m | grep redis  # should output: redis
```

In `config/database.php`, set the Redis client:
```php
'client' => env('REDIS_CLIENT', 'phpredis'),
```

No `composer require` needed. If the extension is ever missing on a new environment, install via:
```bash
herd extension:install redis
```

### Observability

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `sentry/sentry-laravel` | `composer require sentry/sentry-laravel` | Error tracking and alerting in production | 0 |
| `laravel/telescope` | `composer require laravel/telescope --dev` | Local development debugging (never in production) | 0 |
| `laravel/pulse` | `composer require laravel/pulse` | Production performance monitoring | 0 |

### Storage

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `league/flysystem-aws-s3-v3` | `composer require league/flysystem-aws-s3-v3` | AWS S3 driver for merchant logos and assets | 0 |
| `aws/aws-sdk-php` | Pulled in transitively by flysystem-s3 | AWS SDK | 0 |

---

## Phase 1 — MVP

Packages required to ship the first paying merchants.

### SMS Integration

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `africastalking/africastalking` | `composer require africastalking/africastalking` | Africa's Talking PHP SDK — SMS send, inbound parsing, delivery webhook | 1 |

**Note:** Africa's Talking does not have an official Laravel wrapper. We wrap their PHP SDK in our own `SmsService` class. The SDK handles HTTP to their API. Our service handles credit deduction, logging to `sms_logs`, and retry logic.

### Payment Integration

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `flutterwave/flutterwave-php` | `composer require flutterwave/flutterwave-php` | Flutterwave PHP SDK — M-Pesa STK Push for wallet top-ups and subscription billing | 1 |

**Alternative to official SDK:** If the official SDK is outdated, use `GuzzleHTTP` directly and wrap in a `FlutterwaveService` class. The SDK should be evaluated at install time.

### Image Handling

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `intervention/image-laravel` | `composer require intervention/image-laravel` | Resize and validate merchant logo uploads before storing to S3 | 1 |

### PDF & CSV Export (DSAR / Reports)

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `barryvdh/laravel-dompdf` | `composer require barryvdh/laravel-dompdf` | Generate PDF exports for customer data (DSAR fulfilment) and invoice exports | 1 |
| `league/csv` | `composer require league/csv` | CSV export for merchant analytics reports and customer data exports | 1 |

### Money / Currency

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `brick/money` | `composer require brick/money` | Type-safe money arithmetic — prevents floating-point errors in credit calculations, KES amounts, and denormalised balances | 1 |

**Why this matters:** Currency arithmetic like `0.1 + 0.2` in PHP floats gives `0.30000000000000004`. All KES and credit amounts must use integer arithmetic or a money library. `brick/money` provides `Money::of('100.50', 'KES')` with correct rounding.

### Typed Data Objects

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `spatie/laravel-data` | `composer require spatie/laravel-data` | Typed Data Transfer Objects for API requests/responses and PointsEngine inputs — replaces plain array passing | 1 |

**Usage:** `PointsEngine::evaluate(AwardPointsData $data)` instead of `evaluate(array $data)`. Catches type errors at the boundary, self-documenting.

---

## Phase 2 — Launch

Packages required for mobile APIs, Public API, USSD, and full billing.

### OAuth2 — Enterprise API Auth

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `laravel/passport` | `composer require laravel/passport` | OAuth2 server for Enterprise tier API client credentials grant | 2 |

**Note:** Phase 1 uses API key auth only (custom `AuthenticateApiKey` middleware with Sanctum tokens). Passport is only added in Phase 2 when the first Enterprise merchant needs OAuth2 client credentials.

**Important:** Passport and Sanctum can coexist. Sanctum handles customer OTP tokens and cashier PIN tokens. Passport handles OAuth2 client credentials for `api_client` grant. Use separate guards.

### Firebase Push Notifications

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `kreait/firebase-php` | `composer require kreait/firebase-php` | Firebase Admin PHP SDK — FCM push notifications to customer app | 2 |
| `kreait/laravel-firebase` | `composer require kreait/laravel-firebase` | Laravel service provider for kreait/firebase-php | 2 |

### API Query Filtering

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `spatie/laravel-query-builder` | `composer require spatie/laravel-query-builder` | Filter, sort, and paginate Public API responses cleanly — `GET /v1/customers/{phone}/history?filter[type]=earn&sort=-created_at` | 2 |

### API Resources

Built into Laravel — use `php artisan make:resource`. No additional package needed.

### Webhook Signature Verification

Built into our `FireWebhookJob` using PHP's `hash_hmac('sha256', $payload, $secret)`. No additional package needed.

---

## Phase 3 — Scale

Packages required for WhatsApp, multi-country, and coalition.

### WhatsApp Business API

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `netflie/whatsapp-cloud-api` | `composer require netflie/whatsapp-cloud-api` | Meta WhatsApp Cloud API client for PHP — send template messages, receive webhooks | 3 |

**Alternative:** Build a thin `WhatsAppService` wrapping Meta's REST API directly via Guzzle, avoiding a third-party package dependency. Evaluate package maturity at Phase 3 start.

### Advanced Analytics / Statistics

| Package | Install Command | Purpose | Phase |
|---------|----------------|---------|-------|
| `php-science/php-statistics` | `composer require php-science/php-statistics` | Standard deviation calculation for fraud velocity detection (award pattern analysis) | 3 |

### Internationalisation (i18n)

Native Laravel `lang/` files — no additional package needed for i18n. Laravel's `__()` and `@lang()` helpers are sufficient.

---

## Dev-Only Packages

Installed with `--dev` flag. Never in production.

| Package | Install Command | Purpose | When |
|---------|----------------|---------|------|
| `laravel/telescope` | `composer require laravel/telescope --dev` | Request inspection, query analysis, job monitoring in local dev | Phase 0 |
| `fakerphp/faker` | Pulled in by Laravel | Test data generation in factories | Phase 0 |
| `pestphp/pest-plugin-laravel` | `composer require pestphp/pest-plugin-laravel --dev` | Pest Laravel integration (artisan, Livewire helpers) | Phase 0 |
| `pestphp/pest-plugin-arch` | `composer require pestphp/pest-plugin-arch --dev` | Architecture tests (`arch()` helper) | Phase 0 |
| `mockery/mockery` | Pulled in by Laravel | Mock objects in tests | Phase 0 |

---

## Packages Explicitly Ruled Out

These are common options that we are **not** using, and why:

| Package | Why Not |
|---------|---------|
| `laravel/sail` | Not needed — the app is served by **Laravel Herd** locally. Sail adds Docker overhead we don't want. |
| `predis/predis` | Not needed — using the native **phpredis** C extension (faster, zero Composer dependencies, pre-installed in Herd). |
| `filament/filament` | Our admin panel is custom Livewire + Flux Pro — already established in codebase. |
| `spatie/laravel-activitylog` | We have custom `audit_logs` table and `Auditable` trait — more control over retention and schema. |
| `spatie/laravel-backup` | Handled at infrastructure level (AWS RDS automated backups + S3 replication) — not application-level. |
| `maatwebsite/excel` | `league/csv` is sufficient and much lighter; Excel format not required. |
| `tymon/jwt-auth` | Using Laravel Sanctum for customer/cashier tokens and Passport for OAuth2 — JWT not needed. |
| `laravel/socialite` | Customer app uses phone OTP auth, not social login — no OAuth SSO planned. |
| `atymic/twilio-notification-channel` | Africa's Talking is our primary SMS provider; Infobip is the secondary fallback, not Twilio. |
| `owen-it/laravel-auditing` | Custom `Auditable` trait gives full control over retention, format, and what gets logged. |
| `bavix/laravel-wallet` | Evaluated and ruled out — no `tenant_id` awareness, schema control lost, PointsEngine complexity remains regardless. Custom `point_transactions` ledger is the right call. |

---

## Package Evaluation Criteria

Before adding any new package, ask:

1. **Is it actively maintained?** — Check GitHub stars, last commit, open issues. Reject packages not updated in 12+ months for core functionality.
2. **Does it conflict with existing packages?** — Check `composer require` for dependency conflicts before committing.
3. **Can we build it in <2 hours?** — If yes, build it. A thin service class we own is better than an opinionated package we can't control.
4. **Is there a Laravel-native way?** — Sanctum, Horizon, Pulse, Telescope, Fortify cover a lot. Check Laravel docs first.
5. **Does it lock us into a vendor-specific pattern?** — Packages that wrap third-party APIs well (Flutterwave, AT, Firebase) are fine. Packages that impose their own architectural patterns on our domain models need scrutiny.

---

## Full Dependency List (Installation Order)

Run these in order during Phase 0:

```bash
# Confirm phpredis extension is loaded (no composer install needed)
php -m | grep redis

# Core auth and permissions
composer require spatie/laravel-permission
composer require pragmarx/google2fa-laravel
composer require bacon/bacon-qr-code

# Multi-tenancy
composer require stancl/tenancy

# Queue management
composer require laravel/horizon

# Observability
composer require sentry/sentry-laravel
composer require laravel/pulse
composer require laravel/telescope --dev

# Storage
composer require league/flysystem-aws-s3-v3

# Dev tools
composer require pestphp/pest-plugin-laravel --dev
composer require pestphp/pest-plugin-arch --dev

# Note: livewire/flux-pro is installed via the Flux Pro private Composer repository.
# Follow https://fluxui.dev/docs/installation for licence key setup.
```

Phase 1 additions:
```bash
composer require africastalking/africastalking
composer require flutterwave/flutterwave-php
composer require intervention/image-laravel
composer require barryvdh/laravel-dompdf
composer require league/csv
composer require brick/money
composer require spatie/laravel-data
```

Phase 2 additions:
```bash
composer require laravel/passport
composer require kreait/laravel-firebase
composer require spatie/laravel-query-builder
```

Phase 3 additions:
```bash
composer require netflie/whatsapp-cloud-api
composer require php-science/php-statistics
```

---

*Last updated: April 2026. Update this file before every `composer require`.*
