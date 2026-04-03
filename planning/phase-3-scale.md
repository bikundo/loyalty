# Phase 3 — Scale
### LoyaltyOS Africa · Coalition, Multi-Country, Advanced Features

> **Timeline:** Months 10–18
> **Goal:** Coalition loyalty network live, expand beyond Nairobi, multi-country support, advanced fraud detection, branded white-label app, WhatsApp channel.
> **Entry requirement:** Phase 2 fully stable. ≥100 paying merchants. Subscription billing reliable. Unit economics confirmed.

---

## 3.1 Coalition Loyalty Network

> [!IMPORTANT]
> The inter-merchant settlement model and exchange rate mechanism **must be fully designed and approved before any code is written**. The schema will need new tables, and the settlement model has legal and financial implications.

### Concept
Customers earn points at Merchant A, redeem at Merchant B. All coalition points convert to a common "LoyaltyOS Coin" at a platform-set exchange rate.

### Settlement Model (Decision Required Before Phase 3 Build)
| Question | Decision Needed |
|----------|----------------|
| Exchange rate | Platform-set daily rate vs. bilateral negotiation → **Recommendation: platform-set** |
| Inter-merchant payment | When Customer redeems at B having earned at A: does A pay B? Monthly net settlement? Platform holds float? |
| Eligibility | Coalition opt-in per merchant? Minimum plan required (Business+)? |
| Liability | Who bears unredeemed coalition points liability? |

### Proposed New Tables
- `coalition_network` — the platform coalition programme (one global or multiple?)
- `coalition_memberships` — which tenants have opted in
- `coalition_exchange_rates` — daily rates per tenant (points_to_coin_ratio)
- `coalition_settlements` — monthly inter-merchant settlement records
- `coalition_transactions` — cross-merchant earn/redeem events

### Points Engine Changes
- New rule type `coalition` — evaluates when customer is enrolled in a coalition merchant
- `PointsEngine` must be aware of coalition context (which network, what exchange rate)
- Redemption at a coalition merchant deducts "coins" not tenant-specific points

### Coalition Dashboard (Super Admin)
- Manage coalition members (approve/reject opt-ins)
- Set daily exchange rates
- View cross-merchant activity
- Run monthly settlement report

### Coalition Merchant Dashboard
- Toggle coalition participation on/off
- View coalition-earned liability (how many coins have been earned at this merchant)
- View monthly settlement report

---

## 3.2 POS Bridge Auto-Award

The "zero-friction" path — merchant's existing POS automatically calls `/v1/points/award` after processing a payment, without any staff interaction.

- No new API endpoints — uses the existing `POST /v1/points/award`
- POS integration documentation and SDK: PHP and JavaScript sample code
- M-Pesa bridge: merchant POS sends M-Pesa transaction amount + customer phone to LoyaltyOS after M-Pesa payment confirmation on their side
- LoyaltyOS **never touches money** — the POS does the bridging
- Webhook callback `points.awarded` fires back to POS to confirm award

### Integration Guide (Documentation)
- Auth: API key setup
- Phone number matching: how POS passes customer phone
- Idempotency: POS must send `idempotency_key` (use M-Pesa receipt number)
- Testing: sandbox environment with test API keys

---

## 3.3 WhatsApp Business API Channel

Alternative to SMS for customer interactions — richer, media-enabled, but requires customer WhatsApp opt-in.

### What It Replaces / Augments
- Transactional confirmations (points earned, redemption confirmed)
- Campaign messages (with images, CTA buttons)
- Balance check via WhatsApp chat ("BAL" sent to merchant WhatsApp number)

### Technical Approach
- Integration via **Meta WhatsApp Cloud API** (no third-party BSP required for scale)
- Message templates pre-approved by Meta for transactional messages
- `SmsService` abstraction extended: new `WhatsAppService` with same interface
- `sms_logs.gateway` enum extended: add `'whatsapp'`
- Credit model: WhatsApp messages also debit from SMS wallet (same rate as SMS, or separate WhatsApp rate — TBD)

### Customer Opt-In
- Customer must explicitly opt in to WhatsApp notifications (separate `customer_consents.consent_type = 'whatsapp'`)
- Opt-in collected via the customer app or by replying to a WhatsApp outreach

---

## 3.4 Advanced Fraud Detection

Phase 1/2 had rule-based fraud flags. Phase 3 adds pattern detection.

### Award Velocity Detection
- Time-series analysis of `point_transactions` per cashier
- Flag when a cashier's award frequency deviates >2 standard deviations from their own 30-day rolling average
- Implemented as a daily `AnalyseFraudPatternsJob` that writes `fraud_flags` (severity: medium/high based on deviation magnitude)

### Network-Level Abuse Detection (Coalition)
- Customer earns at many coalition merchants in the same day → review flag
- Redemption at a coalition merchant immediately after a large earn at another → flag

### Escalation Improvements
- `fraud_flags` notification to merchant owner via push + email when flag is `high` or `critical`
- Super admin bulk-action: freeze multiple cashiers across a tenant at once
- Transaction reversal: super admin can initiate a reverse `void` on fraudulent transactions across a date range

---

## 3.5 Multi-Country Expansion

### Target Markets (Phase 3 Q3)
- Uganda (UGX) — Africa's Talking supported, M-Pesa via Airtel Money
- Tanzania (TZS) — Africa's Talking supported, M-Pesa via Vodacom

### Schema Changes
- `tenants.preferred_currency` already exists — add UGX, TZS to allowed values
- `tenants.timezone` already flexible (`Africa/Kampala`, `Africa/Dar_es_Salaam`)
- `plans.price_kes` → rename to `plans.price_amount` + add `plans.currency`
- `sms_credit_purchases.amount_paid_kes` → rename to `amount_paid` + `currency` column
- Exchange rate service for cross-currency reporting in super admin

### Gateway Changes
- Flutterwave supports UGX and TZS — no additional billing gateway needed
- Africa's Talking supports Uganda and Tanzania — SMS delivery same integration, different shortcode registration per country
- Country-specific USSD code registration required

### Legal & Compliance
- Uganda Data Protection and Privacy Act (DPPA) 2019 — similar consent requirements to KDPA
- Tanzania Personal Data Protection Act (PDPA) 2022 — additional data localisation requirements
- Separate data processing agreements per country

### Localisation
- Additional languages: Luganda (Uganda), Swahili (Tanzania, already partially supported)
- Currency formatting in dashboard and SMS templates
- Country-specific shortcode and sender ID management in super admin

---

## 3.6 Branded Customer App

Already designed in the codebase — this is the **publishing and provisioning** work:

- Same React Native codebase as the unified customer app
- Theme config driven: logo, colours, app name, merchant UUID baked in at build time
- Published to Play Store and App Store under the merchant's developer account
- Merchant provides: developer account credentials, logo, screenshots, app description
- LoyaltyOS manages: build pipeline, code signing, Push Kit configuration
- Billing: part of Business tier subscription (or separate add-on)

### Technical Provisioning
- `tenants.slug` used as unique build identifier
- Merchant config JSON file per build: `{ merchantUuid, logo, brandColor, appName }`
- CI/CD: GitHub Actions + EAS Build (Expo) — one workflow per merchant build
- App Store Connect + Google Play API for automated submission

---

## 3.7 Dashboard i18n — Swahili

- [ ] Laravel i18n (`lang/` files) — all dashboard strings extracted to translation files
- [ ] Swahili translation file (`lang/sw/`)
- [ ] Language toggle in dashboard header — persisted per user in `users` table (add `dashboard_language` column)
- [ ] Blade/Livewire templates use `__('key')` throughout — no raw English strings
- [ ] SMS templates already support Swahili (Phase 1) — dashboard i18n is purely the admin interface

---

## 3.8 Infrastructure Scaling (Phase 3 Baseline)

Expected scale at Phase 3 entry: 100+ merchants, 50,000+ customers, 500,000+ point_transactions.

### Database
- Read replicas for analytics queries (separate DB connection `mysql_read` in `config/database.php`)
- Partition `point_transactions` and `sms_logs` by `created_at` (monthly partitions) for purge performance
- Analyse slow queries via Pulse — target <100ms for all dashboard queries

### Queue
- Horizon auto-scaling: add workers dynamically when queue depth exceeds threshold
- Separate Redis instance for queues (not shared with cache/sessions)
- Dead letter queue monitoring: alerts when >10 jobs in failed state

### Caching Strategy
- Cache active `loyalty_rules` per tenant: Redis, invalidated on rule change (5-minute TTL as safety net)
- Cache `sms_wallets.credits_balance` per tenant: Redis, invalidated on every deduction (prevents hot-row contention)
- Cache `plans` table: low churn, safe to cache for 1 hour
- Cache merchant home dashboard metrics: per-tenant, 5-minute TTL

### API Performance
- API response target: `POST /v1/points/award` ≤ 300ms p99
- `GET /v1/customers/{phone}/balance` ≤ 100ms p99 (served from Redis cache of `customers.total_points`)
- Rate limit header: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset` on every API response

---

## 3.9 Tests Added in Phase 3

| Test File | Covers |
|-----------|--------|
| `CoalitionEnrolmentTest` | Opt-in, exchange rate applied, earn at A redeemable at B |
| `CoalitionSettlementTest` | Monthly settlement calculation correct |
| `WhatsAppServiceTest` | Message dispatch, opt-in enforcement, template matching |
| `FraudPatternDetectionTest` | Velocity deviation detection, network-level coalition abuse |
| `MultiCurrencyBillingTest` | UGX/TZS plan billing, correct currency on invoice |
| `CacheInvalidationTest` | Rules cache cleared on rule change, balance cache cleared on award |
| `ApiPerformanceTest` | p99 latency assertions on critical endpoints |

---

## Phase 3 Exit Checklist

- [ ] Coalition live for ≥5 merchants with ≥1 successful cross-merchant redemption
- [ ] Uganda or Tanzania: ≥5 paying merchants
- [ ] WhatsApp channel live for ≥3 merchants
- [ ] Branded app live for ≥1 Business-tier merchant in Play Store
- [ ] Dashboard available in Swahili
- [ ] API p99 latency targets met under load (100 concurrent campaign dispatches)

---

## Open Decisions (Must Resolve Before Phase 3 Build Starts)

| Decision | Who Decides | Deadline |
|----------|------------|---------|
| Coalition settlement model (platform float vs. bilateral settlement) | Product + Legal | Before Phase 3 schema design |
| Coalition exchange rate mechanism (platform-set daily vs. negotiated) | Product | Before Phase 3 |
| WhatsApp credit rate (same as SMS wallet or separate rate?) | Product | Before WhatsApp build |
| Branded app provisioning fee (included in Business or separate?) | Commercial | Before Phase 2 exit |
| Uganda/Tanzania legal entity structure | Legal | Before Phase 3 country launch |

---

*Phase 3 turns a product into a network effect. Coalition lock-in is the moat.*
