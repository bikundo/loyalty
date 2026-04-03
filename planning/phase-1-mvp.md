# Phase 1 — MVP
### LoyaltyOS Africa · First Paying Merchants Live

> **Timeline:** Months 1–4 (after Phase 0 is complete)
> **Channel:** Web dashboard + SMS only. No mobile app. No Public API.
> **Goal:** 10–20 pilot merchants in Nairobi live, enrolling customers, awarding points, sending campaigns. First paying subscription revenue. First SMS credit revenue.
> **Exit criteria:** ≥10 merchants activated (≥10 customers enrolled + ≥5 point awards in 14 days).

---

## What "MVP" Means Here

The minimum feature set that creates a complete loyalty loop:

```
Merchant signs up → configures rules → enrolls customers
→ customer earns points (SMS confirmation fires)
→ redeems reward → gets confirmation SMS
→ merchant sees results in analytics → buys more SMS credits
```

---

## 1.1 Merchant Onboarding & Auth

### Super Admin — Tenant Provisioning
- [ ] Super admin panel: list all tenants with status, plan, health score
- [ ] Create tenant: assign plan, create `tenants` row, `tenant_settings`, default `tenant_locations`, `sms_wallets` (zero balance), `tenant_api_settings`
- [ ] Create merchant owner `users` row, assign `merchant_owner` role, send welcome email
- [ ] Activate / suspend / deactivate tenant
- [ ] Change tenant plan

### Merchant Owner — Registration & Login
- [ ] Email + password login (Fortify `web` guard)
- [ ] Email verification, password reset
- [ ] First-login wizard: upload logo (S3), brand colours, programme name, points currency name, SMS language
- [ ] Merchant must configure ≥1 loyalty rule before going live (enforced in wizard)

### Merchant Staff Management
- [ ] Owner invites merchant manager via email
- [ ] Owner creates cashier accounts: name, assigned location, 4–6 digit PIN (bcrypt hashed)
- [ ] Owner resets / deactivates cashier PIN
- [ ] Owner sets cashier daily award cap (`daily_award_cap_kes`, default KES 50,000)
- [ ] All staff management actions logged to `audit_logs`

---

## 1.2 White-Label Configuration

- [ ] Dashboard settings: logo, colours, programme name, points name
- [ ] SMS sender ID management: default `LOYALTYOS` (live immediately), custom sender ID request flow (super admin approves → telco approval)
- [ ] Customisable SMS welcome message template with placeholders
- [ ] Join keyword (e.g. "MAMA") stored in `tenant_settings.join_keyword`
- [ ] Auto-generated join code for app enrolment (`tenant_settings.join_code`)

---

## 1.3 Loyalty Programme & Rules Engine (MVP: visit + spend only)

### Programme Setup
- [ ] View / edit `loyalty_programs` — programme name, points-to-KES ratio, expiry window (days), expiry warning window

### Rule Management
- [ ] List, create, edit, deactivate rules (owner — all actions / manager — toggle only)
- [ ] All rule changes logged to `audit_logs`

**MVP Rule Types:**

| Type | Key Config Fields |
|------|------------------|
| `visit` | `points_awarded` (flat), `max_points_per_customer_per_day` |
| `spend` | `min_spend_kes`, `points_per_kes`, `max_points_per_customer_per_day` |

**Stacking:** `stack_with_others` + `priority` evaluated when multiple active rules exist.

### PointsEngine Service
Path: `app/Services/PointsEngine.php`

- `evaluate(Customer $customer, array $data): Collection` — returns rules that fired + points each awards (no DB write — used for preview)
- `award(Customer $customer, array $data, string $idempotencyKey): void` — wraps `evaluate` in `DB::transaction`: writes `point_transactions`, updates `customers` denormalised columns, dispatches `PointsAwarded` event
- Idempotency: check `point_transactions.idempotency_key` before inserting; silently skip duplicates

---

## 1.4 Customer Management

### Enrolment — Dashboard (Manual)
- [ ] Search by phone → show existing or create new
- [ ] Create `customers` row + `customer_consents` (`channel = 'dashboard'`)
- [ ] Dispatch welcome SMS (debits wallet)

### Enrolment — SMS Keyword (Africa's Talking inbound)
- [ ] Webhook receiver: `POST /webhooks/sms/inbound`
- [ ] Identify tenant from keyword → `tenant_settings.join_keyword`
- [ ] Rate limit: max 10 enrolments per keyword per minute (Redis)
- [ ] If already enrolled → reply with balance SMS
- [ ] If new → create `customers` + `customer_consents` (channel = 'sms') → reply with consent + welcome SMS
- [ ] `STOP` handler: revoke consent, set `customers.status = 'inactive'`, stop all future SMS

### Customer List & Profile
- [ ] Paginated table: encrypted phone (masked display), name, balance, visits, last visit
- [ ] Full point transaction history, redemption history on profile
- [ ] Manual points adjustment (owner only) — creates `adjust`-type `point_transaction`, logged to `audit_logs`
- [ ] Block / unblock customer

---

## 1.5 Point Awards — Dashboard Initiated

### Award Flow
1. Select customer (by phone lookup)
2. Enter: amount spent (KES), visit tick, optional reference/note
3. System shows **points preview** — `PointsEngine::evaluate()` without committing
4. Confirm → dispatch `AwardPointsJob`

### AwardPointsJob
- Queue: `high`, retries: 3, backoff: 5/15/30s
- Idempotency key checked before inserting `point_transactions`
- On success: dispatch `SendTransactionalSmsJob`
- On permanent failure: Slack alert + write to `notification_queue`

### Transaction Void (Owner Only)
- Creates equal-and-opposite `void`-type `point_transaction` entry (original is never modified)
- Requires `void_reason`, updates `customers.total_points`, logged to `audit_logs`

---

## 1.6 Rewards Catalogue & Redemption

### Rewards
- [ ] Create, edit, deactivate rewards: name, type (`discount/freebie/cashback/custom`), `points_required`, optional discount value, stock limits, expiry

### Redemption Flow
1. Select customer → select reward → validate (sufficient points, reward available, within limits)
2. Create `redemptions` row (`status = 'pending'`) + debit `redeem`-type `point_transaction`
3. Cashier/manager confirms: `status = 'confirmed'`, `confirmed_at`, `confirmed_by`
4. Redemption confirmation SMS dispatched (debits wallet)
5. Rejection: `status = 'rejected'` + `rejection_reason` + refund points

---

## 1.7 SMS Architecture & Wallet

### Africa's Talking Integration
Service: `app/Services/SmsService.php`

- `sendTransactional(Customer $customer, string $message): SmsLog`
- `sendBatch(array $recipients, string $message, Campaign $campaign): void`
- Webhook delivery handler: `POST /webhooks/sms/delivery`
- All sends and inbound messages logged to `sms_logs`

### Wallet Operations (Atomic)
1. **Reserve:** `credits_reserved += N` before sending
2. **Deduct:** `credits_balance -= N`, `credits_reserved -= N`, `credits_used_total += N` on delivery confirmation
3. **Refund:** `credits_reserved -= N` on failure after retry exhaustion
4. Use pessimistic locking (`lockForUpdate()`) on `sms_wallets` to prevent race conditions

### Insufficient Credits
- Transactional SMS: skip send → write `notification_queue` → log as skipped
- Campaign: block dispatch entirely, return error to merchant

### Low Balance Alert
- After every deduction, if `credits_balance < low_wallet_alert_threshold`:
  - And last alert was >24 hours ago (`low_balance_alerted_at`)
  - Send email + dashboard banner to owner

### Wallet Top-Up (Flutterwave / M-Pesa STK Push)
- [ ] Dashboard: select bundle → initiate STK Push via Flutterwave
- [ ] Flutterwave webhook: `POST /webhooks/payment/flutterwave`
- [ ] On success: create `sms_credit_purchases`, credit `sms_wallets.credits_balance`
- [ ] Dashboard: current balance, usage history, purchase history

### Transactional SMS Templates (Non-Optional)

| Event | English | Swahili |
|-------|---------|---------|
| Points earned | "You earned {points} {points_name}. Balance: {balance}. - {merchant}" | "Umepata {points} {points_name}. Salio: {balance}. - {merchant}" |
| Redemption confirmed | "Your {reward} at {merchant} is confirmed. {points_used} {points_name} used. Remaining: {balance}" | "Tuzo ya {reward} imethibitishwa..." |
| Welcome | "Welcome to {programme}! Reply STOP to opt out. Terms: {url}" | "Karibu {programme}! ..." |

---

## 1.8 Campaign SMS Builder

- [ ] Create campaign: name, message body (with placeholders), recipient segment, save draft, send now or schedule
- [ ] Credit cost preview: characters → credits per message × estimated recipients
- [ ] Dispatch flow: reserve credits → `202` → `DispatchCampaignJob` → `cursor()` → `Bus::batch()` → chunks of 100 → `SendSmsBatchJob`
- [ ] Delivery webhook reconciles: delivered → deduct, failed → refund
- [ ] Batch complete callback: `campaigns.status = 'completed'`, unused credits refunded
- [ ] Campaign list with delivery report: sent, delivered, failed, delivery rate %
- [ ] Cancel scheduled campaign: refund reserved credits

---

## 1.9 Analytics (Basic)

| Metric | Source |
|--------|--------|
| Total / new customers | `customers` count |
| Active customers (30 days) | `customers.last_visit_at` |
| Points issued / redeemed | `point_transactions` sum |
| Redemption rate | redeemed / issued |
| SMS credits used / remaining | `sms_wallets` |
| SMS delivery rate | `sms_logs` aggregate |

---

## 1.10 Fraud Basics

- [ ] Daily award cap: before every award, check `cashiers.total_awarded_today_kes + amount ≤ cap`
- [ ] `ResetCashierDailyCapJob`: daily 00:00 EAT, resets all cashier daily totals
- [ ] `FraudFlagService::flag()`: creates `fraud_flags` record — called when cashier awards >3× their average per shift, or transaction is >3× tenant average
- [ ] Merchant fraud panel: list open `fraud_flags`, mark investigating/dismissed
- [ ] Super admin fraud panel: platform-wide open flags

---

## 1.11 Scheduled Jobs

| Job | Queue | Schedule | Purpose |
|-----|-------|----------|---------|
| `ExpireStalePointsJob` | low | Daily 02:00 EAT | Write `expire`-type `point_transactions` for past-validity points |
| `ResetCashierDailyCapJob` | low | Daily 00:00 EAT | Reset `cashiers.total_awarded_today_kes = 0` |
| `ReconcileSmsCreditsJob` | low | Hourly | Fix credit/delivery mismatches |
| `PurgeExpiredDataJob` | low | Daily 03:00 EAT | Hard purge per retention policy |

---

## 1.12 KDPA Compliance

- [ ] Consent recorded on every enrolment path (`customer_consents`)
- [ ] `STOP` inbound handler: revoke consent, stop all future SMS to that number
- [ ] Merchant DSAR export: CSV/PDF of all data for a specific customer
- [ ] Customer anonymisation (not hard delete): hash phone, null name, soft delete

---

## 1.13 Tests Required

| Test File | What It Covers |
|-----------|---------------|
| `CustomerEnrolmentTest` | SMS keyword, dashboard, duplicate rejection, consent created, STOP handler |
| `PointsEngineTest` | Visit rule, spend rule, stacking, idempotency, daily cap |
| `PointsAwardTest` | Full award → job → SMS → balance updated |
| `RedemptionTest` | Create, confirm, reject, refund on rejection |
| `SmsWalletTest` | Top-up, reserve, deduct, refund, low balance alert |
| `CampaignDispatchTest` | Credits reserved, batch dispatched, delivery reconciles, failed sends refunded |
| `FraudFlagTest` | Daily cap enforcement, cap reset, flag created on velocity breach |
| `AuditLogTest` | All auditable actions produce correct before/after snapshots |
| `TenantIsolationTest` | Cross-tenant access blocked on all endpoints and models |

---

## Phase 1 Exit Checklist

- [ ] ≥10 pilot merchants provisioned
- [ ] ≥1 merchant has enrolled ≥10 customers and awarded points ≥5 times
- [ ] ≥1 merchant has sent a campaign successfully
- [ ] SMS delivery rate ≥95% over 7 days
- [ ] Zero cross-tenant data leaks in production
- [ ] Zero permanently failed award jobs in 14 days
- [ ] All Phase 1 tests green

---

*Phase 1 proves the loop works. Phase 2 makes it scale.*
