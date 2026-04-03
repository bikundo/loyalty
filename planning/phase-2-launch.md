# Phase 2 — Launch
### LoyaltyOS Africa · Mobile APIs, Public API, Full Feature Set

> **Timeline:** Months 5–9
> **Goal:** Launch mobile apps (React Native), activate Public API for Business/Enterprise merchants, complete all points rule types, go live with USSD, formalise subscription billing. Move from pilot to paid growth.
> **Exit criteria:** ≥50 paying merchants, Public API live for ≥2 Business-tier integrations, subscription auto-billing running, mobile app in Play Store.

---

## 2.1 Complete Points Engine — All Rule Types

Phase 1 shipped `visit` and `spend`. Phase 2 completes the rules engine with the remaining five types.

### `milestone` Rule
- Award bonus points on the Nth visit or purchase
- Config: `milestone_target` (Nth event), `milestone_bonus_points`
- Tracked via `customers.total_visits` and total `point_transactions` count
- Can stack with the base visit/spend rule on the same transaction
- Example: "On your 10th visit, earn 100 bonus points"

### `birthday` Rule
- Points multiplier during the customer's birthday week (7-day window)
- Config: `multiplier` (e.g. 2.0 = double points), `start_date`/`end_date` ignored (date range is always the birthday week)
- Requires `customers.date_of_birth` — skip evaluation if null
- Job: `TriggerBirthdayBonusesJob` — daily 08:00 EAT — activates birthday rule for eligible customers

### `referral` Rule
- Award points to the referrer after the referred customer completes a qualifying first purchase
- Config: `points_awarded` (to referrer), `referral_qualifying_spend_kes` (min first-purchase spend)
- Flow: enrolment with `referred_by_customer_id` set → creates `customer_referrals` row (`status = 'pending'`) → on referred customer's first qualifying spend → `status = 'qualified'` → points credited to referrer → `status = 'credited'`
- Limits enforced at application layer: max 5 credited referrals per customer per month
- Self-referral detection: same phone number pattern → `fraud_flags` entry (`flag_type = 'self_referral'`)

### `bonus` Rule
- Time-limited points multiplier (e.g. 2× points every weekend)
- Config: `multiplier`, `start_date`, `end_date`, `active_days_of_week` JSON
- Example: "Double points every Saturday and Sunday in December"
- Can stack with base rules; `priority` controls evaluation order

### `product` Rule
- Flat points for purchasing a specific product by SKU
- Config: `points_awarded`, `product_sku`
- Merchant enters SKU during rule creation; POS/cashier enters SKU on transaction
- Only evaluates when `product_sku` is passed in the transaction payload

### Points Void (Upgrade)
- Phase 1 had basic void; Phase 2 adds: high-value void requires manager approval (`tenant.points.void` permission is owner only; separate `tenant.points.void.request` for manager to flag for owner approval)

### Expiry Engine (Full)
- `ExpireStalePointsJob` already runs daily — Phase 2 adds the notification flows:
  - **30-day warning:** SMS + push notification ("150 points expiring on {date} at {merchant}")
  - **7-day warning:** Final reminder SMS + push
  - Both debited from merchant SMS wallet (optional — merchant can disable via `tenant_settings.enable_expiry_warning_sms`)
  - Points expiry write `expire`-type `point_transaction` entry on the expiry date

---

## 2.2 Customer App API Surface

All endpoints for the React Native unified customer app. **JSON API — no Blade views.**

### Authentication (`customer` guard)
- [ ] `POST /api/customer/auth/send-otp` — send OTP to phone number (Africa's Talking SMS)
- [ ] `POST /api/customer/auth/verify-otp` — verify OTP → return Sanctum token
- [ ] OTP: 6-digit numeric, 5-minute expiry, stored in Redis keyed by phone, max 3 attempts before lockout
- [ ] `DELETE /api/customer/auth/logout` — revoke token

### Customer Profile
- [ ] `GET /api/customer/me` — return name, phone (masked), enrolled programmes
- [ ] `PUT /api/customer/me` — update name, date of birth, preferred language, FCM token

### Loyalty Cards (per-tenant enrolment)
- [ ] `GET /api/customer/cards` — list all enrolled merchants (one card per tenant): merchant name, logo, brand colour, programme name, current balance, `points_name`
- [ ] `POST /api/customer/cards/enrol` — enrol at a merchant using `join_code` (no SMS keyword needed if app is installed)

### Balance & History (per tenant)
- [ ] `GET /api/customer/cards/{tenantUuid}/balance` — current points balance, lifetime earned, redeemable rewards count
- [ ] `GET /api/customer/cards/{tenantUuid}/history` — paginated `point_transactions` (type, points, description, created_at)

### Rewards
- [ ] `GET /api/customer/cards/{tenantUuid}/rewards` — list active, redeemable rewards (filtered to those customer has enough points for)
- [ ] `POST /api/customer/cards/{tenantUuid}/rewards/{rewardUuid}/redeem` — initiate redemption (`redemptions.status = 'pending'`)

### QR Code (Identity Token)
- [ ] `GET /api/customer/cards/{tenantUuid}/qr` — generate signed QR payload
  - Payload: `{ customer_uuid, tenant_uuid, nonce, expires_at }` signed with HMAC-SHA256
  - `nonce` stored in Redis with 90-second TTL (single-use)
  - `expires_at` = now + 90 seconds
  - Client regenerates automatically before `expires_at`
  - Endpoint callable offline-fallback: last-generated QR cached locally in app for 90s

### Notifications
- [ ] `GET /api/customer/notifications` — list unread `notification_queue` entries
- [ ] `PUT /api/customer/notifications/{id}/read` — mark notification read
- [ ] Firebase FCM token registration: stored in `customers.fcm_token` via `PUT /api/customer/me`

---

## 2.3 Scanner App API Surface

All endpoints for the React Native merchant scanner app. **`cashier` guard enforced on every route.**

### Authentication
- [ ] `POST /api/scanner/auth/login` — cashier enters PIN → return Sanctum token (short-lived, 8-hour expiry)
- [ ] `DELETE /api/scanner/auth/logout` — revoke token

### Customer Lookup (by QR scan)
- [ ] `POST /api/scanner/customers/lookup` — accepts QR payload
  - Validate HMAC signature → reject if invalid
  - Validate nonce → reject if replayed or expired (Redis TTL)
  - Invalidate nonce on successful lookup (single-use)
  - Return: customer name, current balance, `points_name`, last visit date
  - Response must be <200ms — denormalised `total_points` on `customers` table

### Points Preview
- [ ] `POST /api/scanner/points/preview` — accepts `customer_uuid`, `amount_spent_kes`, `is_visit`, `product_sku` (optional)
  - Runs `PointsEngine::evaluate()` without committing
  - Returns: list of rules that will fire and points each awards, total points to be awarded
  - Cashier shows this to customer before confirming

### Award Points
- [ ] `POST /api/scanner/points/award` — accepts `customer_uuid`, `amount_spent_kes`, `is_visit`, `reference`, `note`, `idempotency_key`
  - Dispatches `AwardPointsJob` to `high` queue
  - Returns `202 Accepted` immediately with job reference
  - Transactional SMS dispatched on job completion

### Confirm Redemption
- [ ] `POST /api/scanner/redemptions/{uuid}/confirm` — cashier confirms pending redemption
- [ ] `POST /api/scanner/redemptions/{uuid}/reject` — cashier rejects with reason

### Location Context
- Scanner app session carries `location_id` (set at cashier login based on `cashiers.location_id`)
- All awards and redemptions tagged with `location_id` in `point_transactions`

---

## 2.4 Public API (v1) — POS Integrations

Available on **Business and Enterprise plans only**, enabled per-tenant by super admin.

### API Key Management
- [ ] Super admin: enable `api_access_enabled` for tenant, set `rate_limit_per_day`
- [ ] Owner: create API keys (name, type = api_key) — plaintext shown once on creation, `key_hash` stored
- [ ] Owner: revoke API key (sets `is_active = false`, `revoked_at`)
- [ ] API Key rotation: create new key → grace period (default 48h both keys valid) → old key auto-revoked

### Authentication Middleware
Path: `app/Http/Middleware/AuthenticateApiKey.php`

Every request:
1. Extract key from `X-API-Key` header
2. Look up by `key_prefix` (first 8 chars — index lookup), verify hash match
3. Check `is_active`, not revoked, not past `rotation_expires_at`
4. Resolve tenant context from `tenant_api_keys.tenant_id`
5. Check `tenant_api_settings.api_access_enabled = true`
6. Check tenant plan is Business or Enterprise
7. Check rate limit (Redis counter, reset daily)
8. Log request to `api_request_logs`

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| POST | `/v1/customers/enrol` | Enrol new customer by phone |
| GET | `/v1/customers/{phone}/balance` | Current points balance |
| POST | `/v1/points/award` | Award points (amount_spent, is_visit, note, idempotency_key) |
| POST | `/v1/points/redeem` | Initiate redemption by reward UUID |
| GET | `/v1/customers/{phone}/history` | Paginated point transaction history |
| GET | `/v1/rewards` | List active rewards |
| POST | `/v1/webhooks/configure` | Register webhook URL and events |

All award requests pass through the **same** `PointsEngine` as the scanner app. Same rules, same transactional SMS.

### Responses
- Success: `200` or `201` with data envelope `{ "data": {...} }`
- Validation error: `422` with `{ "errors": {...} }`
- Auth failure: `401` with `{ "message": "..." }`
- Rate limit: `429` with `Retry-After` header
- Server error: `500` with reference ID (Sentry context)

### Rate Limiting

| Plan | Daily Limit | Mechanism |
|------|------------|-----------|
| Business | 500 requests/day | Redis counter keyed by `api_key_id` |
| Enterprise | Unlimited (fair use monitored) | Alert if >10,000/day |

### Outbound Webhooks
Events: `points.awarded`, `points.redeemed`, `customer.enrolled`, `points.expired`

- Tenant registers URL via `POST /v1/webhooks/configure` — stores in `tenant_api_settings`
- Dispatch: `FireWebhookJob` — Horizon `default` queue, exponential backoff retry (1m, 5m, 15m, 1h, 6h)
- Signature: `X-LoyaltyOS-Signature: sha256=<HMAC of raw body with webhook_secret>`
- Merchant verifies signature before trusting payload

### OAuth2 Client Credentials (Enterprise Only)
- Install **Laravel Passport** for OAuth2 server
- `POST /oauth/token` — client_id + client_secret → bearer token (1-hour expiry)
- Bearer token resolves tenant context same as API key

### API Versioning
- All endpoints prefixed `/v1/`
- Breaking changes → `/v2/` — v1 supported minimum 12 months
- `Deprecation` response header added when v1 deprecation begins

### API Request Logging
- Every request writes to `api_request_logs`: `endpoint`, `method`, `status_code`, `response_time_ms`, `ip_address`, sanitised `request_body` (no PII)
- Retention: 90 days (purged by `PurgeExpiredDataJob`)

---

## 2.5 USSD Channel (Premium Add-On)

Enabled per tenant via `tenant_settings.enable_ussd_channel`. Africa's Talking subscriber-billed USSD.

### USSD Webhook Handler
Path: `app/Http/Controllers/Webhooks/UssdController.php`

Africa's Talking sends a POST on each session interaction. Response must be synchronous — no queuing.

### USSD Menu Structure
```
*XXX# → Welcome to {programme_name}
1. Check Balance
2. Transaction History
3. Available Rewards
4. How to Earn
0. Exit
```

**Balance check:** → "Your balance: {points} {points_name}. Visit {merchant_name} to redeem! - {merchant}"

**Transaction history:** → Last 5 `point_transactions` formatted as numbered list

**Available rewards:** → Active rewards with `points_required` listed

**Enrolment via USSD:** If customer's phone not enrolled → prompt "Press 1 to join {programme_name}" → create customer on confirmation

### Economics
- Customer dials USSD → session cost from customer's own airtime (subscriber-billed)
- Zero cost to merchant — platform bears platform cost, recovers via Africa's Talking revenue share

---

## 2.6 Firebase FCM — Push Notifications

- [ ] Install `kreait/firebase-php` SDK
- [ ] `FirebaseService::sendToCustomer(Customer $customer, Notification $notification): void`
- [ ] Fall back to `notification_queue` if `customers.fcm_token` is null or send fails
- [ ] Push events:
  - Points earned (after `AwardPointsJob` completes)
  - Redemption confirmed
  - Points expiry warning (30-day and 7-day)
  - Campaign push (if merchant uses push instead of/alongside SMS — Phase 2 opt-in)

---

## 2.7 Full Subscription Billing

### Auto-Billing Flow
- [ ] `ProcessSubscriptionBillingJob` — runs daily, queries `subscriptions` where `next_billing_at <= now()`
- [ ] For each due subscription: initiate M-Pesa STK Push via Flutterwave → create `payment_transactions` row
- [ ] On success: update `subscriptions.last_payment_at`, `next_billing_at`, `failed_payment_count = 0`
- [ ] On first failure: `subscriptions.status = 'past_due'`, `grace_period_ends_at = now() + 7 days`, notify merchant
- [ ] Retry daily during grace period (7 days)
- [ ] After grace period: `subscriptions.status = 'suspended'` → dashboard and API access blocked until payment
- [ ] Plan upgrade: prorated credit applied, switch applied immediately
- [ ] Plan downgrade: applied at end of current billing cycle
- [ ] Reactivation (within 30 days of cancellation): one-click, all data preserved

### Invoice History
- [ ] Dashboard: list of all `payment_transactions` for the tenant

---

## 2.8 Full Analytics

| Report | Page | Key Metrics |
|--------|------|-------------|
| Overview | Dashboard home | Enrolled, active customers, redemption rate, SMS delivery rate, wallet balance |
| Customer analytics | /analytics/customers | Enrolment by channel (SMS/app/dashboard), cohort retention, top customers by LTV |
| Points analytics | /analytics/points | Points issued vs redeemed vs expired, outstanding liability (points × KES ratio) |
| Location analytics | /analytics/locations | Per-location: awards, redemptions, cashier usage |
| SMS analytics | /analytics/sms | All types (campaign/transactional/birthday/etc.), delivery rates, credit spend by type |
| ROI report | /analytics/roi | Loyalty-influenced revenue, SMS spend vs repeat visit revenue |

### Merchant Health Scores
- [ ] `CalculateMerchantHealthScoresJob` — daily 01:00 EAT — calculates score (0–100) and writes `merchant_health_scores`
- Score components: logins, enrolments, awards, campaigns, wallet balance

### Automated Merchant Interventions
| Trigger | Action |
|---------|--------|
| No dashboard login in 7 days | Automated email: "Your {programme} update" with key stats |
| No points awarded in 14 days | SMS to merchant owner: "Your staff haven't used {programme} recently" |
| Zero campaigns in 30 days | Email with campaign templates |
| Approaching zero wallet balance | Push + email with top-up link |
| Health score < 30 | Flag in super admin panel for manual outreach |

### Monthly ROI Email to Merchants
- [ ] `SendMonthlyRoiEmailJob` — 1st of each month
- Content: total repeat visits, SMS spend vs revenue influenced, top customers, platform comparison percentile

---

## 2.9 Enhanced Fraud Detection

- [ ] High-value redemption dual confirmation: if `points_used > configurable_threshold` → cashier initiates, manager must confirm (separate `PATCH /api/scanner/redemptions/{uuid}/manager-confirm` endpoint)
- [ ] Redemption velocity alert: customer redeems >X rewards in 7 days → `fraud_flags` (medium severity)
- [ ] Referral abuse detection: >5 credited referrals per month → block + flag
- [ ] QR nonce audit: log all nonce validation failures (replays) → sequence of replays → flag
- [ ] Merchant fraud dashboard: view flags by severity, investigate, dismiss

---

## 2.10 Jobs Added in Phase 2

| Job | Queue | Schedule | Purpose |
|-----|-------|----------|---------|
| `TriggerBirthdayBonusesJob` | high | Daily 08:00 EAT | Activate birthday rule for eligible customers |
| `TriggerWinBackCampaignsJob` | default | Weekly | Create win-back campaign for 30+ day inactive |
| `CalculateMerchantHealthScoresJob` | low | Daily 01:00 EAT | Write daily health score snapshot |
| `ProcessSubscriptionBillingJob` | default | Daily | Auto-charge due subscriptions |
| `SendMonthlyRoiEmailJob` | low | Monthly (1st) | Monthly ROI email to each merchant |
| `FireWebhookJob` | default | On event | Dispatch outbound webhooks with retry |

---

## 2.11 Tests Added in Phase 2

| Test File | Covers |
|-----------|--------|
| `MilestoneRuleTest` | Nth visit bonus fires correctly |
| `BirthdayRuleTest` | Birthday window, multiplier, skips if no DOB |
| `ReferralRuleTest` | Qualifying spend threshold, monthly cap, self-referral block |
| `BonusRuleTest` | Time window, day-of-week restriction |
| `PointsExpiryTest` | Warning at 30 days, 7 days; expiry on day; notification written |
| `CustomerAppAuthTest` | OTP send, verify, wrong OTP, lockout |
| `CustomerQrCodeTest` | QR generation, nonce single-use, replay rejected, expiry |
| `ScannerLookupTest` | Valid QR resolved, invalid signature rejected, replayed nonce rejected |
| `PublicApiAuthTest` | Valid key, revoked key, wrong tenant, rate limit |
| `PublicApiAwardTest` | Award via API hits same PointsEngine, transactional SMS sent |
| `WebhookDispatchTest` | Webhook fired on points.awarded, signature valid, retry on failure |
| `SubscriptionBillingTest` | Auto-charge, grace period, suspension, reactivation |
| `FraudVelocityTest` | Redemption velocity flag, referral cap, high-value dual confirmation |

---

## Phase 2 Exit Checklist

- [ ] ≥50 paying merchants
- [ ] Public API live for ≥2 Business-tier POS integrations
- [ ] Subscription auto-billing running without manual intervention
- [ ] Customer app in Play Store (beta, public)
- [ ] USSD live for ≥2 merchants as premium add-on
- [ ] All rule types tested and live
- [ ] Merchant health scoring running daily
- [ ] All Phase 2 tests green

---

*Phase 2 transforms the MVP into a platform. Phase 3 makes it a network.*
