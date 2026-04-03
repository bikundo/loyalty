# LoyaltyOS Africa — Product & Technical Plan
### White-Label SMS / Web / App Loyalty Platform for Kenya & Africa

> Last updated: reflects all decisions made during planning sessions including gap analysis review

---

## 1. Vision & Positioning

**What you're building:** A multi-tenant, white-label loyalty platform that lets any small business in Kenya/Africa run a branded loyalty programme — accessible via SMS (any phone), USSD (any phone), a web dashboard (merchants), and a consumer mobile app.

**Core insight:** Most Kenyan SMEs can't afford bespoke loyalty tech, and global tools don't fit the local context (M-Pesa, SMS-first customers, informal retail). You become the infrastructure layer they plug into.

**Who you sell to (B2B):** Retail shops, pharmacies, salons, restaurants, fuel stations, supermarkets, SACCOs.

**Who uses the product (B2C):** Their customers — ordinary Kenyans on any phone.

---

## 2. Monetisation Model

Four distinct revenue streams:

### 2.1 SaaS Subscription (Monthly)

| Tier | Target Merchant | Price (KES/mo) | Key Features |
|------|----------------|----------------|--------------|
| Starter | Solo trader, kiosk | 2,000–3,500 | SMS loyalty, web dashboard, up to 500 customers |
| Growth | SME with 1–3 locations | 6,000–12,000 | + Mobile app (branded), campaigns, analytics |
| Business | Chain / franchise | 18,000–35,000 | + Multi-location, coalition points, API access |
| Enterprise | Large retail / FMCG | Custom | Full white-label, dedicated instance, M-Pesa integration |

### 2.2 SMS Credits (Unified Prepaid Wallet — All Platform-Originated SMS)

Merchants purchase credits in advance. **All SMS sent by the platform — transactional and campaign — debits from the same prepaid wallet at the same per-SMS rate.** There is no "free" SMS category. Every message costs credits.

| Bundle | SMS Credits | Price (KES) | Per SMS |
|--------|-------------|-------------|---------|
| Starter | 500 | 650 | 1.30 |
| Growth | 2,000 | 2,400 | 1.20 |
| Business | 10,000 | 10,500 | 1.05 |
| Enterprise | 50,000+ | Custom | Negotiated |

SMS margin: buy from Africa's Talking at ~KES 0.60–0.80/SMS, sell at KES 1.05–1.30/SMS = ~40–60% gross margin on every message.

**SMS categories (for analytics tracking, not billing):**

| Type | Wallet Debit | Examples |
|------|-------------|---------|
| Transactional | Yes — debits SMS wallet | "You earned 50 points!", redemption confirmation |
| Campaign | Yes — debits SMS wallet | Bulk promotions, win-back messages, flash sales |

**Transactional SMS controls:**

- **Non-optional (always fire):** Points award confirmations, redemption confirmations. These are the minimum viable loyalty experience — merchants cannot disable them. Framed as "operational SMS" in the dashboard.
- **Optional (merchant-controlled):** Birthday bonuses, milestone alerts, inactivity reminders, reward expiry warnings. Merchants can enable or disable these to manage SMS spend.

**ROI visibility:** The analytics dashboard surfaces SMS spend alongside loyalty outcomes (repeat visits driven, redemptions completed) so the merchant sees the cost as an investment, not a drain.

Credits are reserved at dispatch and deducted on delivery confirmation from Africa's Talking webhook. Failed jobs automatically refund reserved credits.

### 2.3 Revenue Share (Phase 2+)
- In-app voucher redemptions
- Coalition programme transaction fees

### 2.4 Customer-Initiated Premium Interactions (Phase 2+)

Micro-revenue from customer-paid SMS and USSD balance check interactions. Customers who use SMS or USSD to check their loyalty balance pay via their own airtime (premium-rate SMS) or subscriber-billed USSD sessions. Revenue share from these interactions flows back to the platform. Not material early on, but compounds at scale across hundreds of merchants and tens of thousands of customers.

---

## 3. Core Feature Set

### 3.1 Merchant Web Dashboard
- Onboard and manage customers
- Configure loyalty rules (visit-based, spend-based, or both — see Section 5)
- Set rewards catalogue
- SMS campaign builder: bulk send to all or segmented customers
- SMS wallet: top up credits, view usage, set low-balance alerts
- Analytics: active members, redemption rate, revenue influenced, SMS delivery rates, SMS spend vs loyalty ROI
- Multi-location management
- White-label config: logo, brand colours, programme name, SMS sender ID

### 3.2 SMS Channel (Any Phone)
- Customer enrols via SMS keyword (e.g. "JOIN MAMA" to 20880)
- Points issued via SMS after purchase (merchant triggers via dashboard)
- Redemption confirmed via merchant dashboard
- Automated transactional SMS: point award confirmations, milestone alerts, birthday bonuses (debited from merchant's SMS wallet)
- Supports Safaricom, Airtel, Telkom

### 3.3 USSD Channel (Any Phone — Phase 2, Premium Add-On)

Customer-facing USSD menu for feature phone users. Activated per merchant as a premium add-on. All session costs are subscriber-billed — the customer pays from their own airtime, the merchant pays nothing.

- Balance check: customer dials USSD code → sees current points balance
- Transaction history: last 5 point transactions
- Available rewards: browse redeemable rewards
- Enrolment: join a merchant's loyalty programme via USSD menu

USSD is provided via Africa's Talking's USSD API with subscriber-billed sessions. Requires USSD code registration (add to infrastructure checklist).

### 3.4 Customer Balance Check — Channel Economics

| Channel | Cost to Merchant | Cost to Customer | Cost to Platform |
|---------|-----------------|-----------------|-----------------|
| **Customer app** | Free | Free (needs data/WiFi) | Near-zero (API call to own backend) |
| **SMS** ("BAL" to shortcode) | Free | Customer pays airtime for inbound; outbound reply via premium-rate SMS (KES 2–5) | Cost-neutral or profitable via premium SMS revenue share |
| **USSD** (dial *XXX#) | Free | Session cost borne by customer's airtime (subscriber-billed) | Cost-neutral via Africa's Talking subscriber-billed USSD |

The app is the default, free path. SMS and USSD are premium channels that the merchant activates for their feature-phone customer base. Merchants have zero cost exposure for customer-initiated balance checks regardless of channel.

### 3.5 Customer App — Unified (Android-first, then iOS)

The primary consumer-facing product. One app, all loyalty cards in one place — the customer never needs to download a separate app per merchant.

- All enrolled loyalty cards visible in one dashboard
- Each merchant card shows: current points balance, available rewards, transaction history
- Each merchant card displays a unique QR code — an identity token only, used by the merchant scanner to identify the customer. The customer does not initiate or control point awards
- QR encodes customer_id + tenant_id, signed and short-lived — refreshes every 60–90 seconds to prevent screenshot reuse
- Works offline for QR display — no connectivity needed to show the code
- Push notifications for point awards, campaign offers, reward expiry alerts
- Merchant discovery — find nearby businesses on the platform
- Enrolment: scan a merchant's enrolment QR or enter a join code — SMS keyword not required if app is installed

### 3.6 Customer App — Branded (Premium Add-On, Business Tier+)

Identical functionality to the unified app but fully white-labelled per merchant:

- Merchant's own logo, colours, and app name
- Single-merchant experience — only shows that merchant's loyalty card
- Listed on Play Store / App Store under the merchant's account
- Built from the same React Native codebase as the unified app — theming via config, not a separate codebase
- Suitable for larger chains, franchises, and established brands who want full brand control

### 3.7 Merchant Scanner App (Android-first)

A lightweight, purpose-built app for staff at the point of sale. Separate from the web dashboard — designed for speed and simplicity at the counter.

**The customer QR is an identity token only.** The merchant side owns the transaction entirely — staff enter what actually happened (amount spent, visit confirmation) and the API computes the points. Customers cannot self-award or influence what is posted.

Award flow:
1. Staff opens scanner app — camera launches immediately
2. Customer opens their app, taps their card for this merchant, shows QR
3. Staff scans QR — customer name and current balance appear instantly
4. Staff enters transaction details: amount spent in KES, visit checkbox, optional note
5. App shows a real-time points preview (rules calculated before posting)
6. Staff confirms — transaction posted to API
7. Scanner shows success; customer app balance updates; transactional SMS queued (debited from merchant wallet)

Key features:
- Staff login via PIN or biometric — no full merchant credentials needed
- Role-based access: staff can only scan, award, and confirm redemptions — no access to settings, campaign tools, or customer data
- Real-time points preview before confirmation — staff and customer both see what will be awarded
- Redemption flow: customer selects reward in their app → presents QR → staff scans and confirms → points deducted
- Offline-capable: if connectivity drops, the scan and transaction details queue locally and sync automatically on reconnect — critical for Kenyan SME context
- One scanner app login per staff member; tenant_id is baked into the staff session
- Entire award flow completable in under 5 taps

### 3.8 Merchant Web Dashboard

Full-featured browser-based control panel for the business owner. Not for daily counter use — for setup, management, and strategy.

- Configure loyalty rules (visit-based, spend-based, or hybrid — see Section 5)
- Manage rewards catalogue
- SMS campaign builder with credit cost preview and queued dispatch
- SMS wallet: top up credits, view usage history, set low-balance alerts
- Customer management: view enrolments, points history, activity
- Analytics: active members, points issued, redemption rate, SMS delivery rates, revenue influenced
- Staff management: create PIN-based scanner app logins, assign to locations
- Multi-location management
- White-label config: logo, colours, programme name, SMS sender ID
- Responsive — accessible on mobile browser

### 3.9 White-Label Layer
- Each merchant gets: custom programme name, colours, logo
- Branded SMS sender ID (e.g. "NAKUSTORE") — uses shared platform sender ID ("LOYALTYOS") by default until custom ID is approved by the telco (2–4 week lead time)
- Merchant-facing dashboard on subdomain (e.g. nakustore.loyaltyos.co.ke)
- Branded customer app (Business tier and above) — same React Native codebase, merchant config-driven

---

## 4. Technical Stack

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| Backend | Laravel (PHP) | Team's core strength; excellent queue/job ecosystem |
| Database | MySQL (primary) + Redis (cache, queues, sessions) | Laravel-native, multi-tenant friendly |
| Queue | Laravel Horizon (Redis-backed) | Handles all heavy async work elegantly |
| SMS Gateway | Africa's Talking (primary), Infobip (fallback) | Kenya-native, reliable, M-Pesa integrable |
| USSD Gateway | Africa's Talking | Subscriber-billed USSD sessions for customer balance checks |
| Mobile Apps | React Native (shared codebase) | Three apps: customer unified, customer branded, merchant scanner |
| Web Dashboard | React + Tailwind | Fast to build, easy to white-label via theming |
| Auth | Laravel Sanctum + OAuth2 | Merchant SSO + consumer auth |
| Push Notifications | Firebase FCM | Android + iOS push |
| Hosting | AWS af-south-1 (Cape Town) or Azure Africa | Low latency, data residency |
| Storage | AWS S3 or Cloudinary | Merchant logos, assets |
| Billing | Flutterwave (via M-Pesa STK Push) | KES billing, M-Pesa support for top-ups |

### 4.1 Multi-Tenancy Model

**Approach: Single database, shared schema.**

One MySQL database serves all tenants. Every table that holds tenant-specific data carries a `tenant_id` foreign key. Laravel's Eloquent global scopes automatically inject `WHERE tenant_id = ?` into every query, so tenant data never bleeds across merchants. The `stancl/tenancy` package manages context switching — middleware identifies the tenant on each request (via subdomain or API key) and sets the tenant context for the entire request lifecycle.

Key design rules:
- Every tenant-scoped model uses the `BelongsToTenant` trait — enforced at the ORM level, not just application logic
- All tenant-scoped tables are indexed on `tenant_id` — non-negotiable for query performance at scale
- Unique constraints are scoped per tenant (e.g. phone numbers unique within a tenant, not globally)
- The `tenants` table lives in the same database as a central registry — no separate "landlord" database needed in the single-DB model
- Validation rules (`unique`, `exists`) are manually scoped to `tenant_id` to prevent cross-tenant data leaks
- Super admin queries explicitly bypass tenant scoping via a dedicated admin context

**Why this approach:**
- New merchant onboarding = one row inserted into `tenants` — zero infrastructure provisioning
- Schema migrations run once and apply to all tenants instantly
- Cross-tenant analytics for the super admin dashboard is trivial — all data is in one place
- Lowest operational overhead — no per-tenant backup complexity, no connection pool multiplication
- Right-sized for the target market: hundreds of Kenyan SMEs paying KES 2,000–35,000/month

**Noisy neighbour mitigation:**
- Composite indexes on `(tenant_id, created_at)` and `(tenant_id, customer_id)` on high-traffic tables
- Horizon queue rate limits per tenant to prevent one large merchant's campaign from starving others
- Query timeouts enforced at the application layer
- Slow query monitoring via Laravel Telescope to catch unbounded queries early

**Future escape hatch:**
If an Enterprise merchant grows to a scale where they genuinely need physical isolation (e.g. a national supermarket chain with 500,000 members), the `stancl/tenancy` package supports moving a single tenant to a dedicated database connection without any application code changes. This is a migration operation, not an architectural rebuild. All other tenants remain on the shared database.

### 4.2 Observability & Incident Response

**Error tracking & APM:**
- Sentry for error tracking and alerting in production (Laravel, React Native, and React dashboard)
- Laravel Pulse for production performance monitoring (response times, slow queries, throughput)
- Laravel Telescope for local development and debugging only — not used in production

**Business metric alerts:**
- SMS delivery rate drops below 95% → Slack alert
- Merchant dashboard login frequency drops (leading churn indicator) → flag in super admin dashboard
- Abnormal points issuance pattern (cashier awards 3× average) → fraud flag (see Section 14.5)
- Enrolment rate sudden drop → Slack alert

**Uptime monitoring:**
- External uptime checks on API, dashboard, and Africa's Talking webhook endpoints
- Webhook endpoint failures are critical — missed delivery receipts mean SMS credits don't reconcile

**Incident response:**
- Slack channel for production alerts (Sentry, Horizon failures, uptime monitors)
- Defined on-call rotation once team grows beyond solo developer
- Runbook for critical scenarios: Horizon down, Africa's Talking outage, database failover

---

## 5. Points Rules Engine

Each merchant configures their own earning rules. Multiple rules can be active simultaneously. The PointsEngine service evaluates all active rules on each transaction event and awards points accordingly. Merchants have full control over which model(s) they use.

### 5.1 Supported Rule Types

| Rule Type | How It Works | Best For |
|-----------|-------------|---------|
| visit | X points per check-in, regardless of spend | Salons, gyms, car washes |
| spend | X points per KES Y spent | Supermarkets, pharmacies |
| product | X points for purchasing a specific item | FMCG promotions |
| milestone | Bonus points on Nth visit or purchase | Any — drives frequency |
| birthday | Points multiplier during birthday week | Any — drives loyalty |
| referral | X points for bringing a new customer | Any — drives acquisition |
| bonus | Time-limited multiplier (e.g. 2x on weekends) | Campaign-driven merchants |

### 5.2 Rule Configuration
- Merchant selects rule type — dashboard shows only the relevant fields for that type
- stack_with_others flag: rules can fire together or be mutually exclusive
- priority field: determines evaluation order when rules conflict
- is_active toggle: enable/disable rules without deleting them

### 5.3 Core Database Schema

```
tenants
  id | name | subdomain | brand_color | logo_url | sms_sender_id | plan | status

loyalty_programs
  id | tenant_id | name | description | points_currency_name | is_active

loyalty_rules
  id | program_id | type (enum) | points_awarded
  min_spend | multiplier | milestone_target
  stack_with_others | priority | is_active

customers
  id | tenant_id | phone | name | email | enrolled_at | total_points | lifetime_spend

point_transactions
  id | customer_id | rule_id | type (earn|redeem|expire)
  points | balance_after | reference | triggered_by | created_at

rewards
  id | program_id | name | description | points_required
  type (discount|freebie|cashback|custom) | expires_at | stock_limit

redemptions
  id | customer_id | reward_id | points_used | status | confirmed_at
```

### 5.4 Points Lifecycle & Expiry Policy

**Expiry window:** Configurable per merchant. Default: 12 months from date of earn. Minimum configurable: 3 months. No maximum — merchants can set points to never expire.

**Expiry notification flow:**
1. 30 days before expiry: warning SMS/push notification ("You have 150 points expiring on [date] at [StoreName]. Visit us to redeem!")
2. 7 days before expiry: final reminder
3. Expiry date: `ExpireStalePointsJob` runs daily at 02:00 EAT, expires eligible points, records `point_transaction` with type `expire`

**Transactional SMS for expiry warnings** debits from the merchant's SMS wallet. Merchants can disable expiry warnings (optional transactional SMS) but cannot disable the expiry itself once configured.

**Points liability:**
- Merchant dashboard displays total outstanding points value in KES (points × redemption value)
- Industry breakage rate is 20–30% (points never redeemed) — this means the effective cost of the loyalty programme is lower than the face value of points issued. Surface this insight in merchant analytics.

**Merchant churn and points:** When a merchant cancels their subscription, enrolled customers retain their points balance for 90 days. During this grace period, the merchant can reactivate. After 90 days, unredeemed points expire and customers are notified. Customer data is retained per the data retention policy (see Section 16).

### 5.5 Coalition Loyalty — Design Principles (Phase 3)

High-level design for cross-merchant point redemption. Implementation is Phase 3 but the settlement model must be defined before build begins.

**Model:** Platform-managed exchange. All coalition merchants' points convert to a common "LoyaltyOS coin" at a platform-set exchange rate. Earn at Merchant A in their points → redeem at Merchant B using the common rate.

**Settlement questions to resolve before Phase 3 build:**
- Exchange rate: platform-set based on each merchant's point-to-KES ratio, or negotiated bilaterally?
- Inter-merchant settlement: if Customer earns at A and redeems at B, does A pay B the KES value? Monthly net settlement? Platform holds a float?
- Eligibility: coalition opt-in per merchant? Minimum tier required (Business+)?
- Liability: who bears the cost of unredeemed coalition points?

---

## 6. SMS Architecture & Queuing

Core principle: nothing heavy runs in the foreground. All SMS sending, point processing, campaign dispatching, and analytics generation is handled asynchronously via Laravel Horizon. HTTP responses return immediately (202 Accepted) and the work happens behind the scenes.

### 6.1 Queue Structure

| Queue | Workers | Jobs | Behaviour |
|-------|---------|------|-----------|
| high | 2 | Transactional SMS, point awards | Immediate; retries=3; backoff 5/15/30s |
| default | 4 | Campaign batches, wallet top-ups | Chunked in 100s via Laravel Batch API |
| low | 1 | Analytics, reports, expiry sweeps | Cron-scheduled, lower urgency |

### 6.2 SMS Wallet Schema

```
sms_wallets (one per tenant)
  id | tenant_id | credits_balance | credits_used_total

sms_credit_purchases (every top-up)
  id | tenant_id | credits_purchased | amount_paid | cost_per_sms | payment_reference | status

sms_logs (every SMS sent)
  id | tenant_id | campaign_id (nullable)
  recipient_phone | message | credits_used
  type (campaign|transactional|balance_check|redemption)
  status (queued|sent|delivered|failed)
  gateway_message_id | delivered_at
```

Note: The `type` field is for analytics and reporting only. All SMS types debit from the same wallet at the same rate.

### 6.3 Campaign Dispatch Flow

```
Merchant sends campaign (dashboard)
        ↓
Controller: validate credits → reserve credits → return 202 immediately
        ↓
DispatchCampaignJob (default queue)
        ↓
Load recipients via cursor() — memory efficient, no OOM risk
        ↓
Bus::batch() → chunks of 100 → SendSmsBatchJob × N
        ↓
Africa's Talking API (rate-limited, batched outbound)
        ↓
Delivery receipt webhook → update sms_logs → deduct credits
        ↓
Batch callbacks: completed | partial failure | reconcile credits
```

### 6.4 Transactional SMS Flow

```
Point award triggered (dashboard or scanner app or POS API)
        ↓
AwardPointsJob dispatched → high queue
        ↓
DB::transaction: PointsEngine evaluates rules → writes point_transaction
        ↓
Check merchant SMS wallet balance → sufficient credits?
  → Yes: SmsService::sendTransactional() → reserve credit → Africa's Talking → deduct on delivery
  → No: Skip SMS, log as sms_skipped, store notification for in-app display
        ↓
Customer receives: "You earned 50 pts. Balance: 320 pts. - StoreName"
```

**Transactional SMS retry policy:** If delivery fails, retry 2× over 30 minutes. If still undelivered after retries, refund the reserved credit to the merchant's wallet and store the notification for in-app display when the customer next opens the app.

**Low wallet balance:** When a merchant's SMS wallet drops below a configurable threshold (default: 50 credits), an automated alert is sent to the merchant via email and push notification. The dashboard displays a prominent low-balance warning.

### 6.5 Scheduled Background Jobs

| Job | Queue | Schedule | Purpose |
|-----|-------|----------|---------|
| ExpireStalePointsJob | low | Daily 02:00 | Expire points past validity window |
| TriggerBirthdayBonusesJob | high | Daily 08:00 | Fire birthday rule for eligible customers |
| TriggerWinBackCampaignsJob | default | Weekly | Re-engage customers inactive 30+ days |
| ReconcileSmsCreditsJob | low | Hourly | Fix any credit/delivery mismatches |

### 6.6 Failed Job Handling
- All jobs retry with exponential backoff before permanently failing
- Failed campaign batches automatically refund reserved SMS credits
- Failed transactional SMS refund reserved credits after retry exhaustion
- All permanent failures alert via Slack webhook
- Laravel Horizon dashboard (admin-only) provides full queue and throughput visibility
- All jobs are written to be idempotent — safe to retry without double-awarding points or double-sending SMS

---

## 7. Public API (POS Integration)

Available on Business and Enterprise plans only. Activated and deactivated per tenant by the super admin. Allows larger merchants to integrate their existing POS systems directly with the platform's rules engine — awarding and redeeming points without using the scanner app.

**Future POS bridge opportunity (Phase 3+):** Merchants whose POS already processes M-Pesa payments can send the transaction amount to `/v1/points/award` automatically after payment confirmation on their side. LoyaltyOS never touches the money — the POS does the bridging. This is the path to frictionless auto-award without becoming a payments company.

### 7.1 Super Admin Controls
- Toggle API access per tenant (`api_access_enabled`) — Business/Enterprise plans only
- Generate and revoke API keys per tenant, named per integration (e.g. "Main POS", "Branch 2 Till")
- View per-tenant API usage logs and request history
- Configure rate limits per tier

### 7.2 Authentication — Two-Track

| Method | Use Case | How It Works |
|--------|----------|-------------|
| API Key | Simple POS, single integration | `X-API-Key: sk_live_...` header. Hashed key stored against tenant. |
| OAuth2 Client Credentials | Enterprise, multi-system | Client exchanges client_id + client_secret for a bearer token at `POST /oauth/token`. Token used on subsequent requests. |

Both resolve to the same tenant context internally. The rest of the request pipeline is identical regardless of auth method. Start with API keys — OAuth2 added for enterprise without changing core API logic.

Auth middleware checks on every request:
1. Key/token valid and not revoked
2. Tenant has `api_access_enabled = true`
3. Tenant plan is Business or Enterprise
4. Request is within rate limit for the day

**API key rotation:** When a merchant needs to rotate a key, a new key is issued with a configurable grace period (default: 48 hours) during which both old and new keys are valid. After the grace period, the old key is automatically revoked.

### 7.3 Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/v1/customers/enrol` | Enrol a new customer by phone number |
| GET | `/v1/customers/{phone}/balance` | Fetch current points balance |
| POST | `/v1/points/award` | Award points — pass amount_spent, visit flag, optional note |
| POST | `/v1/points/redeem` | Redeem a reward by reward_id |
| GET | `/v1/customers/{phone}/history` | Paginated transaction history |
| GET | `/v1/rewards` | List active rewards for the merchant |
| POST | `/v1/webhooks/configure` | Register a webhook URL to receive platform events |

All award requests pass through the same `PointsEngine` as the scanner app — rules configured in the merchant dashboard apply equally to POS-originated transactions. Customers receive the same transactional SMS regardless of whether points came from the scanner app or the POS API (debited from merchant's SMS wallet).

### 7.4 Webhooks (Outbound)
For enterprise merchants whose POS needs to be notified of platform events:
- `points.awarded` — fired after a successful award
- `points.redeemed` — fired after a redemption is confirmed
- `customer.enrolled` — fired when a new customer joins
- `points.expired` — fired when points expire

Webhook payloads are signed with HMAC-SHA256 using a per-tenant secret. Merchant verifies the signature before trusting the payload. Undelivered webhooks retry with exponential backoff (queued via Horizon).

### 7.5 Rate Limits by Plan

| Plan | Daily Request Limit | Notes |
|------|-------------------|-------|
| Business | 500 requests/day | Suitable for single or small multi-location |
| Enterprise | Unlimited (fair use) | Monitored for abuse |

Rate limit responses return `429 Too Many Requests` with a `Retry-After` header.

### 7.6 Schema Additions

```
tenant_api_keys
  id | tenant_id (FK) | name
  key_prefix (first 8 chars — display only)
  key_hash (bcrypt — plaintext never stored)
  type: api_key | oauth_client
  client_id | client_secret_hash (OAuth2 only)
  last_used_at | is_active
  created_by (super admin user id)

tenant_api_settings
  tenant_id (FK) | api_access_enabled
  rate_limit_per_day | webhook_url | webhook_secret

api_request_logs
  id | tenant_id | key_id (FK)
  endpoint | method | status_code
  ip_address | user_agent | created_at
  (purge after 90 days)
```

### 7.7 API Versioning
All endpoints are prefixed `/v1/`. Breaking changes increment to `/v2/` — old versions supported for a minimum of 12 months with deprecation notices sent via email and API response headers.

---

## 8. Roles & Permissions

Implemented using `spatie/laravel-permission`. Fixed platform-wide roles — no merchant-defined custom roles. Six distinct actors, each with a carefully scoped set of permissions.

### 8.1 Roles

| Role | Scope | Authentication | Primary Interface |
|------|-------|---------------|-------------------|
| `super_admin` | Platform-wide, all tenants | Email + password + 2FA | Admin panel |
| `merchant_owner` | Own tenant only | Email + password | Web dashboard |
| `merchant_manager` | Own tenant only | Email + password | Web dashboard |
| `cashier` | Own tenant only | PIN or biometric | Scanner app only |
| `customer` | Own data only | Phone OTP or social | Customer app |
| `api_client` | Own tenant, transactional only | API key or OAuth2 bearer | Public API |

### 8.2 Permission Groups

**Platform administration** — super admin only:
- `platform.tenants.manage` — view, activate, deactivate all tenants
- `platform.plans.manage` — change tenant subscription plans
- `platform.api.enable` — enable/disable public API per tenant
- `platform.analytics.view` — platform-wide reporting
- `platform.admins.manage` — manage super admin user accounts
- `platform.sms_logs.view` — view all SMS logs across tenants
- `platform.horizon.view` — access Horizon queue dashboard

**Merchant account & billing** — owner only (super admin inherits):
- `tenant.billing.manage` — subscription and payment management
- `tenant.sms_wallet.topup` — purchase SMS credit bundles
- `tenant.settings.manage` — white-label config, sender ID, subdomain
- `tenant.api_keys.manage` — generate and revoke API keys

**Staff management** — owner only (super admin inherits):
- `tenant.staff.invite` — invite and remove staff members
- `tenant.staff.roles.assign` — assign merchant_manager or cashier role
- `tenant.staff.pins.manage` — create and reset cashier PIN logins
- `tenant.staff.activity.view` — view staff activity audit log

**Loyalty programme configuration:**
- `tenant.rules.manage` — create, edit, delete loyalty rules (owner only)
- `tenant.rules.toggle` — activate/deactivate rules (owner + manager)
- `tenant.rewards.manage` — create, edit, delete rewards (owner + manager)

**Customer management:**
- `tenant.customers.view` — view customer list and profiles (owner + manager)
- `tenant.customers.enrol` — enrol new customer (owner + manager + cashier + api_client)
- `tenant.customers.points.view` — view any customer's balance (owner + manager + cashier + api_client)
- `tenant.customers.points.adjust` — manually adjust points (owner only)
- `tenant.customers.history.view` — view transaction history (owner + manager + api_client)

**Point transactions:**
- `tenant.points.award` — scan QR, enter amount, award points (owner + manager + cashier + api_client)
- `tenant.points.redeem.confirm` — confirm a redemption (owner + manager + cashier + api_client)
- `tenant.points.void` — reverse a transaction (owner only)

**Customer self-service (customer role only):**
- `customer.balance.view` — view own points balance
- `customer.history.view` — view own transaction history
- `customer.rewards.view` — view available rewards
- `customer.redemption.request` — initiate a reward redemption

**SMS campaigns:**
- `tenant.campaigns.manage` — build and send campaigns (owner + manager)
- `tenant.campaigns.view` — view campaign history and delivery reports (owner + manager)
- `tenant.sms_wallet.view` — view wallet balance and usage (owner + manager)

**Analytics:**
- `tenant.analytics.view` — merchant analytics dashboard (owner + manager)
- `tenant.reports.export` — export data reports (owner + manager)

### 8.3 Implementation Notes

**Spatie role/permission seeding:** Roles and permissions are seeded in a dedicated `RolesAndPermissionsSeeder`. Never hardcode role or permission names in application logic — always reference constants from a central `Permission` and `Role` class to prevent typo-driven bugs.

**Tenant scoping with Spatie:** Spatie's `laravel-permission` is not multi-tenant aware by default. Use the `teams` feature (`permission_groups`) or a custom guard per tenant context to ensure a `merchant_owner` at Tenant A cannot act on Tenant B. All role assignments carry the `tenant_id` context.

**Guard separation:**
- `web` guard — super admins, merchant staff (web dashboard)
- `cashier` guard — cashier PIN login (scanner app)
- `customer` guard — customer app (OTP / social auth)
- `api` guard — public API clients (API key / OAuth2 bearer token)

**Super admin bypass:** Super admin uses a `Gate::before()` check that bypasses all permission checks — they can do everything without needing every permission explicitly assigned. Avoids permission sprawl for the platform operator.

**Cashier role restrictions:** Cashiers authenticate via PIN only and are restricted to scanner app routes. If a cashier's credentials are somehow used to hit dashboard routes, middleware rejects the request regardless of permissions. Authentication method enforced at the route level, not just the permission level.

**Audit logging:** All permission-sensitive actions (point adjustments, voids, staff changes, billing changes, rule modifications) write to an `audit_logs` table with `user_id`, `role`, `action`, `tenant_id`, `before`, `after`, and `ip_address`. Immutable — no update or delete on audit records.

### 8.4 Schema Additions

```
audit_logs
  id | tenant_id | user_id | user_role
  action | entity_type | entity_id
  before (JSON) | after (JSON)
  ip_address | user_agent | created_at
  (no updated_at — immutable by design)
```

---

## 9. Key Integrations

### Phase 1
- Africa's Talking — SMS sending, inbound keyword handling, delivery receipts, premium-rate SMS for customer balance checks
- Firebase FCM — push notifications for mobile app
- Flutterwave — merchant subscription billing + SMS credit top-ups (M-Pesa STK Push as primary payment method)
- AWS S3 / Cloudinary — merchant asset storage

### Phase 2
- Africa's Talking USSD — subscriber-billed USSD sessions for customer balance checks and enrolment (requires USSD code registration)
- Public API — POS integrations for Business/Enterprise merchants (iKhokha, custom POS, any system)
- WhatsApp Business API — loyalty interactions via WhatsApp

### Phase 3+
- POS bridge for M-Pesa auto-award — merchant's POS sends transaction amount to `/v1/points/award` after processing M-Pesa payment on their side. LoyaltyOS never touches money; the POS does the bridging.

---

## 10. Go-To-Market Strategy

### Phase 1 — Validate (Months 1–4)
- Launch MVP: web dashboard + SMS only (no app yet)
- Target 10–20 pilot merchants in Nairobi (salons, pharmacies, small supermarkets)
- Free or heavily discounted for 3 months in exchange for feedback
- Metrics to validate: enrolment rate, SMS engagement rate, merchant retention, credit wallet usage

### Phase 2 — Launch (Months 5–9)
- Launch unified customer app (Android) and merchant scanner app
- Launch USSD balance check channel (premium add-on)
- Formalise pricing tiers and SMS credit bundles
- Partner with business association or SACCO (e.g. Kenya National Chamber of Commerce)
- Hire 2 merchant success / sales reps in Nairobi

### Phase 3 — Scale (Months 10–18)
- Expand to Mombasa, Kisumu, Nakuru
- Launch coalition loyalty (points redeemable across merchants)
- POS bridge integrations for auto-award via Public API
- Explore Uganda, Tanzania expansion

### 10.1 Merchant Onboarding Playbook

**What the merchant provides to go live:**
1. Business name, category, and contact details
2. Logo and brand colours (or use defaults)
3. Initial loyalty rule configuration (guided wizard in dashboard)
4. SMS wallet initial top-up (minimum bundle purchase)

**Sender ID handling:**
- All merchants launch with the shared platform sender ID ("LOYALTYOS") immediately — no delay
- Custom sender ID (e.g. "NAKUSTORE") is applied as an upgrade once telco approval completes (2–4 week lead time with Safaricom)
- Merchant is notified when their custom sender ID goes live

**Onboarding collateral (provided by LoyaltyOS):**
- Printable QR code poster for the counter — customers scan to enrol
- Counter card explaining the loyalty programme to customers
- Staff training guide — how to use the scanner app in 5 steps
- "How to explain this to your customers" script for staff

**Activation definition:** A merchant is considered "activated" when they have enrolled ≥10 customers and awarded points ≥5 times within 14 days of going live. Track this metric in the super admin dashboard.

**Stall intervention:** If a merchant has not activated within 14 days, trigger automated nudges:
- Day 7: email with tips and link to training guide
- Day 10: SMS reminder to the merchant owner
- Day 14: flag for manual outreach by merchant success team

---

## 11. MVP Build Scope (First 90 Days)

### Must Have
- [ ] Merchant onboarding and auth (Laravel Sanctum)
- [ ] Multi-tenant architecture with white-label config
- [ ] Points rules engine — visit and spend rules as minimum
- [ ] Customer enrolment via SMS keyword (Africa's Talking inbound)
- [ ] Point award — manual via dashboard, queued via Horizon
- [ ] Transactional SMS — point confirmation, redemption confirmation (debited from wallet)
- [ ] Basic reward redemption (merchant confirms via dashboard)
- [ ] Unified SMS wallet — credit balance display, top-up via Flutterwave (M-Pesa STK Push)
- [ ] Campaign SMS builder with credit cost preview and queued dispatch
- [ ] Laravel Horizon setup — high / default / low queues with correct worker counts
- [ ] Failed job alerting via Slack
- [ ] Simple analytics: members, points issued, redemptions, SMS delivery rates, SMS spend
- [ ] Sentry error tracking integration
- [ ] Fraud basics: daily award caps per cashier, low-balance wallet alerts

### Post-MVP
- [ ] Remaining rule types: milestone, birthday, referral, bonus
- [ ] Unified customer app (Android, React Native) — QR card display, points balance, history, free balance check
- [ ] Merchant scanner app (Android, React Native) — QR scan, award, redeem, offline queue
- [ ] Branded customer app (config-driven from unified codebase)
- [ ] USSD channel — subscriber-billed balance check, enrolment (Africa's Talking)
- [ ] Premium-rate SMS balance check
- [ ] Automated birthday and win-back campaigns
- [ ] Points expiry engine with 30-day and 7-day warning notifications
- [ ] Public API — API key auth, all endpoints, rate limiting, request logging
- [ ] Public API — OAuth2 client credentials (Enterprise)
- [ ] Public API — outbound webhooks with HMAC signing
- [ ] Public API — key rotation with grace period
- [ ] Super admin — per-tenant API activation, key management, usage dashboard
- [ ] Super admin — merchant health scoring and churn risk flags
- [ ] Coalition points across merchants
- [ ] Fraud detection: anomaly alerts on award velocity, redemption velocity
- [ ] Localisation: Swahili SMS templates, dashboard language toggle

---

## 12. Team & Resources

| Role | Status | Priority |
|------|--------|----------|
| Full-stack Laravel developer | You have this | Ready |
| React / React Native developer | Assess team capacity | High |
| Product/UX designer | Hire or contract | High |
| Merchant success / sales | Hire Month 4+ | Medium |
| Africa's Talking account | Register immediately | Immediate |
| USSD code registration | Apply during Phase 1 | Before Phase 2 launch |
| Legal (Kenya Data Protection Act 2019) | Contract lawyer | Before launch |

---

## 13. Competitive Moat

| Advantage | How to Build It |
|-----------|----------------|
| SMS-first | Works on any phone — no smartphone needed for end customers |
| USSD balance checks | Free for merchants, airtime-billed for customers — works on every phone |
| Local pricing | KES-denominated, M-Pesa billing, affordable tiers |
| Flexible rules engine | Visit-based, spend-based, hybrid — merchant decides |
| Coalition network | Points redeemable across merchants = deep lock-in |
| Data | Merchants get customer insights unavailable elsewhere |
| Distribution | Telco partners, business associations, POS vendors |

---

## 14. Risk & Mitigation

| Risk | Mitigation |
|------|-----------|
| SMS costs eat margin | Unified wallet model — all SMS billed to merchant at 40–60% margin; no free SMS exposure |
| Merchants deplete wallet and miss confirmations | Low-balance alerts; non-optional core transactional SMS; ROI visibility in analytics |
| Campaign SMS failures | Credits reserved upfront; auto-refunded on batch failure |
| Transactional SMS delivery failure | Retry 2× over 30 minutes; refund credit on exhaustion; store notification for in-app display |
| Merchants don't activate customers | Onboarding playbook with collateral, activation metrics, and automated stall interventions |
| Low smartphone penetration | SMS-first architecture + USSD channel covers feature phone users |
| Copycat competition | Move fast; coalition network is hard to replicate |
| Kenya Data Protection Act | Explicit SMS opt-in, privacy policy, AF region data residency, consent tracking (see Section 16) |
| Queue worker downtime | Horizon supervisor + auto-restart; all jobs are idempotent; Sentry alerting |
| Noisy neighbour (shared DB) | Composite indexes on tenant_id; Horizon rate limits per tenant; query timeouts; slow query monitoring |
| Cross-tenant data leak | BelongsToTenant trait on all models; tenant-scoped validation rules; admin context separate from tenant context; automated tests asserting tenant isolation |

### 14.5 Fraud & Abuse Framework

**Cashier collusion / ghost transactions:**
- Daily award cap per cashier (configurable by merchant owner, default: KES 50,000 total awards/day)
- Anomaly detection: flag any cashier who awards >3× the average points per shift
- Mandatory manager approval for single transactions above a configurable KES threshold
- POS receipt-matching for API-integrated merchants (Business/Enterprise)

**Customer self-referral abuse:**
- Referral points only credited after the referred customer completes a qualifying purchase (not on enrolment alone)
- Cooldown period: maximum 5 referral rewards per customer per month
- Duplicate phone number detection across tenants (same person enrolling at same merchant with different numbers)

**Points laundering via redemptions:**
- Dual confirmation on high-value redemptions: cashier initiates, manager approves (configurable threshold)
- Redemption velocity alerts: flag if a single customer redeems more than X rewards per week
- All redemptions logged in audit trail with cashier ID and timestamp

**QR code security:**
- Server-side nonce validation — each QR nonce is single-use, rejected if replayed
- QR refresh every 60–90 seconds
- If customer app is offline >90 seconds, QR shows a visual "expired" indicator; customer must reconnect to refresh

**SMS keyword abuse:**
- Rate-limit enrolments: maximum 10 enrolments per shortcode keyword per minute
- Optional merchant-controlled enrolment approval (manual review before customer is added)

**Escalation tiers:**
1. **Automated flags** — anomalies surface in merchant dashboard and super admin panel
2. **Merchant review** — merchant owner investigates flagged transactions
3. **Platform intervention** — super admin can freeze a cashier account, reverse fraudulent transactions, or suspend a tenant

---

## 15. Financial Model

### 15.1 Revenue Projections (Month 12 Target)

| Revenue Stream | Assumption | Monthly (KES) |
|---------------|-----------|---------------|
| SaaS subscriptions | 100 merchants × avg KES 6,000 | 600,000 |
| SMS credit margin (all SMS) | 100 merchants × avg 800 total SMS/mo × KES 0.50 margin | 40,000 |
| Premium SMS/USSD revenue | Nominal in Year 1 | ~5,000 |
| Total MRR (Month 12 target) | | ~645,000 |
| Year 1 ARR target | | ~KES 7.7M (~$60K USD) |

Note: SMS credit margin now includes transactional SMS (previously bundled free). Average 800 SMS/merchant/month = ~500 transactional + ~300 campaign. Margin per SMS is consistent across both types.

SMS margin improves significantly as merchant volume grows and Africa's Talking bulk rates drop with higher usage.

### 15.2 Cost Structure (Month 12 Estimate)

| Cost Item | Assumption | Monthly (KES) |
|-----------|-----------|---------------|
| Hosting (AWS af-south-1) | Application servers, RDS, Redis, S3 | ~80,000 |
| Africa's Talking SMS costs | 100 merchants × 800 SMS × KES 0.70 avg | ~56,000 |
| Flutterwave transaction fees | ~3.5% on subscription collections | ~21,000 |
| Sentry / monitoring tools | | ~5,000 |
| Staff — sales reps (×2) | KES 80,000 each | 160,000 |
| Staff — customer support (×1) | | 60,000 |
| Total estimated costs | | ~382,000 |
| **Estimated gross margin** | | **~263,000 (41%)** |

### 15.3 Unit Economics

- **CAC:** 2 sales reps closing ~10 merchants/month = KES 16,000 per merchant
- **Average MRR per merchant:** KES 6,450 (subscription + SMS margin)
- **Payback period:** ~2.5 months (viable if monthly churn stays below ~10%)
- **Churn sensitivity:** Kenyan SME SaaS typically sees 5–10% monthly churn in Year 1. At 8% monthly churn, ~8 new merchants/month needed just to maintain 100.

---

## 16. Data Protection & Compliance (Kenya Data Protection Act 2019)

### 16.1 Roles
- **Data Controller:** Each merchant (they determine what customer data is collected and why)
- **Data Processor:** LoyaltyOS (processes customer data on behalf of merchants)
- **Data subjects:** End customers

### 16.2 Consent Collection Flow

**SMS enrolment:**
1. Customer texts "JOIN [KEYWORD]" to shortcode
2. Platform replies: "Welcome to [MerchantName] Rewards! By joining, you agree to receive loyalty updates and allow [MerchantName] to store your phone number for their rewards programme. Reply STOP at any time to opt out. Terms: [short URL]"
3. Customer's enrolment is recorded with timestamp and channel

**App enrolment:**
- In-app consent screen during enrolment with checkboxes for: loyalty programme participation, marketing communications (separate opt-in)
- Consent version tracked per customer

### 16.3 Data Retention Policy

| Data Type | Retention Period | Purge Method |
|-----------|-----------------|--------------|
| `point_transactions` | 24 months after creation | Soft delete, then hard purge at 36 months |
| `sms_logs` | 12 months | Hard purge |
| `api_request_logs` | 90 days | Hard purge |
| `audit_logs` | 5 years (regulatory) | Never purged |
| Customer PII | Until opt-out or merchant churn + 90 days | Anonymise on deletion request |

### 16.4 Data Subject Access Requests (DSARs)

- Customers can request their data via the app (self-service export) or by contacting the merchant
- Merchants can trigger a customer data export from the dashboard (PDF/CSV)
- Deletion requests: customer data is anonymised (phone hashed, name removed), not hard-deleted, to preserve aggregate analytics integrity
- DSAR response SLA: 30 days (KDPA requirement)

### 16.5 Cross-Border Data Processing

| Processor | Jurisdiction | Data Shared | DPA Required |
|-----------|-------------|-------------|-------------|
| Africa's Talking | Kenya | Phone numbers, SMS content | Yes |
| Flutterwave | Nigeria | Merchant billing details | Yes |
| Firebase (Google) | US | Device tokens, push content | Yes |
| AWS | South Africa (af-south-1) | All application data | Yes (standard AWS DPA) |

### 16.6 Security Controls for PII

- Phone numbers and customer names encrypted at rest using Laravel's encrypted casting
- Rate limiting on customer-facing API endpoints to prevent phone number enumeration
- All API responses exclude PII unless explicitly requested and authorised

---

## 17. Merchant Retention Strategy

### 17.1 Health Scoring

Each merchant receives a health score (0–100) in the super admin dashboard, composed of:
- Weekly dashboard login frequency (are they using it?)
- Customers enrolled this month (is the programme growing?)
- Points awarded this week (are staff using the scanner?)
- SMS campaigns sent this month (are they engaging customers?)
- SMS wallet balance (can they continue operating?)

### 17.2 Automated Interventions

| Health Signal | Trigger | Action |
|--------------|---------|--------|
| No login in 7 days | Score drops | Automated email: "Your loyalty programme update" with key stats |
| No points awarded in 14 days | Score drops | SMS to merchant owner: "Your staff haven't used [ProgramName] recently. Need help?" |
| Zero campaigns in 30 days | Score drops | Email with campaign templates and tips |
| Approaching zero wallet balance | Low balance | Push notification + email with top-up link |

### 17.3 ROI Visibility

Monthly automated email to each merchant showing:
- Total repeat visits attributed to the loyalty programme
- SMS spend vs estimated revenue influenced
- Top customers by lifetime value
- Comparison to platform average ("Your programme is in the top 20% for engagement")

### 17.4 Billing Flexibility

Kenyan SMEs have irregular cash flow. Reduce churn with:
- M-Pesa STK Push as primary billing method (familiar, low-friction)
- Grace period: 7 days after failed payment before deactivation (with daily retry)
- Easy downgrade path: merchants can downgrade tier mid-cycle rather than cancelling
- Reactivation: one-click reactivation within 30 days of cancellation, data preserved

---

## 18. Localisation

### 18.1 SMS Language (Phase 1 — Merchant-Configurable)

SMS template language is configurable per merchant from day one. Two options at launch:
- **English** (default): "You earned 50 points. Balance: 320 pts. - StoreName"
- **Swahili**: "Umepata pointi 50. Salio: 320. - StoreName"

Merchant selects preferred language in dashboard settings. All transactional and campaign SMS use the selected language.

### 18.2 Dashboard Language (Post-MVP)

Dashboard internationalisation (i18n) for Swahili support. Lower priority than SMS language since most business owners are comfortable with English interfaces, but important for expanding beyond Nairobi.

### 18.3 Future Consideration

Sheng (Nairobi urban slang) as a tone option for youth-oriented merchants — salons, fast food, fashion retail. This is a brand differentiation opportunity, not a translation task.

---

## 19. Testing Strategy

### 19.1 Tenant Isolation Tests (Critical — Run on Every Deployment)
- Create two test tenants
- Assert that Tenant A can never read, write, or modify Tenant B's data via any endpoint
- Assert that Eloquent global scopes are applied on all tenant-scoped models
- Assert that validation rules (unique, exists) are tenant-scoped

### 19.2 Points Engine Tests
- Rule stacking: multiple active rules fire correctly in priority order
- Edge cases: zero-spend transactions, negative adjustments, concurrent awards
- Expiry: points expire at correct time, notifications fire at 30-day and 7-day marks
- Idempotency: replayed award jobs don't double-award

### 19.3 SMS Integration Tests
- Africa's Talking sandbox: send and receive delivery receipts
- Wallet debit accuracy: credits reserved, deducted on delivery, refunded on failure
- Campaign dispatch: correct chunking, batch callbacks, credit reconciliation

### 19.4 Load Testing
- Simulate 100 concurrent campaign dispatches
- Validate Horizon queue throughput and identify bottlenecks
- Stress test the points engine under concurrent scan-and-award load

---

*Built for Kenya. Designed for Africa.*
