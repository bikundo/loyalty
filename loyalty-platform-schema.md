# LoyaltyOS Africa — Database Schema & Models

> Complete schema design covering all entities referenced in the Product & Technical Plan v2.
> Designed for Laravel Eloquent with a single-database, shared-schema multi-tenancy model.

---

## Design Principles

1. **Every tenant-scoped table carries `tenant_id`** — enforced via `BelongsToTenant` Eloquent trait with global scope
2. **All unique constraints are tenant-scoped** — phone numbers, emails, slugs are unique within a tenant, not globally
3. **Composite indexes on `(tenant_id, ...)` on every high-traffic table** — non-negotiable for query performance
4. **Soft deletes on business-critical tables** — customers, rewards, loyalty rules are soft-deleted to preserve referential integrity
5. **Timestamps on everything** — `created_at` and `updated_at` on all tables unless explicitly noted
6. **UUIDs for customer-facing identifiers** — IDs exposed in QR codes, API responses, and URLs use UUIDs. Internal foreign keys use auto-incrementing bigint for join performance
7. **PII encrypted at rest** — phone numbers and customer names use Laravel's `encrypted` cast

---

## Entity Relationship Overview

```
Platform Level (no tenant_id)
├── users (super admins, merchant owners, merchant managers)
├── plans
└── platform_settings

Tenant Level
├── tenants
│   ├── tenant_locations
│   ├── tenant_settings
│   ├── tenant_api_settings
│   ├── tenant_api_keys
│   └── sms_wallets
│       └── sms_credit_purchases
│
├── staff
│   └── cashiers (PIN-based auth)
│
├── loyalty_programs
│   ├── loyalty_rules
│   └── rewards
│
├── customers
│   ├── customer_consents
│   ├── point_transactions
│   ├── redemptions
│   └── customer_referrals
│
├── campaigns
│   └── campaign_recipients
│
├── sms_logs
├── audit_logs
└── api_request_logs
```

---

## Tables

### 1. `users`

Platform users who log in via email + password. Covers super admins, merchant owners, and merchant managers. **Not** cashiers (PIN auth) or customers (OTP auth).

```
users
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  tenant_id           BIGINT UNSIGNED NULL (FK → tenants.id) — NULL for super admins
  name                VARCHAR(255) NOT NULL
  email               VARCHAR(255) NOT NULL
  email_verified_at   TIMESTAMP NULL
  password            VARCHAR(255) NOT NULL
  two_factor_secret   TEXT NULL — for super admin 2FA
  two_factor_confirmed_at TIMESTAMP NULL
  is_active           BOOLEAN DEFAULT TRUE
  last_login_at       TIMESTAMP NULL
  last_login_ip       VARCHAR(45) NULL
  remember_token      VARCHAR(100) NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  deleted_at          TIMESTAMP NULL — soft delete

  UNIQUE INDEX idx_users_email (email)
  INDEX idx_users_tenant (tenant_id)
```

**Roles (via Spatie):** `super_admin`, `merchant_owner`, `merchant_manager`

**Notes:**
- Email is globally unique (a person can only have one platform account)
- `tenant_id` is NULL for super admins who operate across all tenants
- Merchant owners and managers are scoped to their tenant

---

### 2. `plans`

Subscription tiers available on the platform. Managed by super admin.

```
plans
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  name                VARCHAR(100) NOT NULL — e.g. "Starter", "Growth", "Business", "Enterprise"
  slug                VARCHAR(100) UNIQUE NOT NULL
  price_kes           DECIMAL(10,2) NOT NULL — monthly price in KES
  max_customers       INT UNSIGNED NULL — NULL = unlimited
  max_locations       INT UNSIGNED DEFAULT 1
  has_app_access      BOOLEAN DEFAULT FALSE
  has_branded_app     BOOLEAN DEFAULT FALSE
  has_api_access      BOOLEAN DEFAULT FALSE
  has_coalition       BOOLEAN DEFAULT FALSE
  sms_rate_per_credit DECIMAL(5,2) NOT NULL — KES per SMS credit for this tier
  description         TEXT NULL
  is_active           BOOLEAN DEFAULT TRUE
  sort_order          INT UNSIGNED DEFAULT 0
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
```

---

### 3. `tenants`

The core multi-tenancy table. One row per merchant business.

```
tenants
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  plan_id             BIGINT UNSIGNED NOT NULL (FK → plans.id)
  owner_user_id       BIGINT UNSIGNED NOT NULL (FK → users.id)
  name                VARCHAR(255) NOT NULL — business name
  slug                VARCHAR(100) UNIQUE NOT NULL — used for subdomain
  subdomain           VARCHAR(100) UNIQUE NOT NULL — e.g. "nakustore"
  business_category   VARCHAR(100) NULL — e.g. "pharmacy", "salon", "supermarket"
  phone               VARCHAR(20) NULL — business contact number
  email               VARCHAR(255) NULL — business contact email
  status              ENUM('pending','active','suspended','churned') DEFAULT 'pending'
  activated_at        TIMESTAMP NULL — when first activation criteria met
  churned_at          TIMESTAMP NULL
  trial_ends_at       TIMESTAMP NULL
  sms_language        ENUM('en','sw') DEFAULT 'en' — English or Swahili
  preferred_currency  VARCHAR(3) DEFAULT 'KES'
  timezone            VARCHAR(50) DEFAULT 'Africa/Nairobi'
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  deleted_at          TIMESTAMP NULL — soft delete

  INDEX idx_tenants_status (status)
  INDEX idx_tenants_plan (plan_id)
```

---

### 4. `tenant_settings`

White-label and branding configuration per tenant. Separated from `tenants` to keep the core table lean.

```
tenant_settings
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED UNIQUE NOT NULL (FK → tenants.id)
  programme_name      VARCHAR(255) NULL — e.g. "Naku Rewards"
  brand_color_primary VARCHAR(7) NULL — hex, e.g. "#FF5722"
  brand_color_secondary VARCHAR(7) NULL
  logo_url            VARCHAR(500) NULL — S3/Cloudinary URL
  favicon_url         VARCHAR(500) NULL
  sms_sender_id       VARCHAR(11) NULL — custom sender ID once approved, e.g. "NAKUSTORE"
  sms_sender_id_status ENUM('pending','approved','rejected','not_requested') DEFAULT 'not_requested'
  sms_sender_id_default VARCHAR(11) DEFAULT 'LOYALTYOS' — platform default until custom approved
  join_keyword        VARCHAR(20) NULL — SMS keyword, e.g. "MAMA"
  join_code           VARCHAR(10) NULL — short alphanumeric for app enrolment
  welcome_sms_template TEXT NULL — customisable welcome message
  points_name         VARCHAR(50) DEFAULT 'points' — e.g. "stars", "stamps"
  low_wallet_alert_threshold INT UNSIGNED DEFAULT 50 — credits
  enable_birthday_sms BOOLEAN DEFAULT TRUE
  enable_milestone_sms BOOLEAN DEFAULT TRUE
  enable_expiry_warning_sms BOOLEAN DEFAULT TRUE
  enable_winback_sms  BOOLEAN DEFAULT FALSE
  enable_ussd_channel BOOLEAN DEFAULT FALSE — premium add-on
  enable_sms_balance_check BOOLEAN DEFAULT FALSE — premium add-on
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
```

---

### 5. `tenant_locations`

Physical locations/branches for a merchant business. Every tenant has at least one default location.

```
tenant_locations
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  name                VARCHAR(255) NOT NULL — e.g. "Westlands Branch", "Main Store"
  address             VARCHAR(500) NULL
  city                VARCHAR(100) NULL
  latitude            DECIMAL(10,8) NULL
  longitude           DECIMAL(11,8) NULL
  phone               VARCHAR(20) NULL
  is_default          BOOLEAN DEFAULT FALSE — one default per tenant
  is_active           BOOLEAN DEFAULT TRUE
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  deleted_at          TIMESTAMP NULL — soft delete

  INDEX idx_locations_tenant (tenant_id)
  INDEX idx_locations_active (tenant_id, is_active)
  UNIQUE INDEX idx_locations_default (tenant_id, is_default) WHERE is_default = TRUE
```

**Multi-location design:**
- Every tenant gets one default location on creation (even single-location merchants)
- Loyalty rules, rewards, and customer points are **tenant-wide** — not per-location
- Locations are a **reporting and staff assignment layer**: every transaction is tagged with a location, enabling per-location analytics
- Cashiers are assigned to a location — their scanner app session carries the `location_id`
- Customers earn and redeem across all locations seamlessly; they see one loyalty card per merchant

---

### 6. `cashiers`

Separate from `users` — cashiers authenticate via PIN only, not email/password. Lightweight accounts for counter staff.

```
cashiers
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  location_id         BIGINT UNSIGNED NOT NULL (FK → tenant_locations.id)
  name                VARCHAR(255) NOT NULL
  pin_hash            VARCHAR(255) NOT NULL — bcrypt hashed 4-6 digit PIN
  is_active           BOOLEAN DEFAULT TRUE
  daily_award_cap_kes DECIMAL(12,2) DEFAULT 50000.00 — fraud control
  total_awarded_today_kes DECIMAL(12,2) DEFAULT 0.00 — reset daily by scheduled job
  last_active_at      TIMESTAMP NULL
  created_by          BIGINT UNSIGNED NOT NULL (FK → users.id) — owner/manager who created
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  deleted_at          TIMESTAMP NULL — soft delete

  INDEX idx_cashiers_tenant (tenant_id)
  INDEX idx_cashiers_location (tenant_id, location_id)
```

---

### 7. `customers`

End consumers enrolled in one or more merchant loyalty programmes. A customer is scoped per tenant — the same phone number can exist under multiple tenants.

```
customers
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL — used in QR codes and API
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  phone               VARCHAR(20) NOT NULL — encrypted at rest
  name                VARCHAR(255) NULL — encrypted at rest
  email               VARCHAR(255) NULL
  date_of_birth       DATE NULL — for birthday rule
  gender              ENUM('male','female','other','unspecified') DEFAULT 'unspecified'
  preferred_language  ENUM('en','sw') DEFAULT 'en'
  status              ENUM('active','inactive','blocked') DEFAULT 'active'
  enrolled_via        ENUM('sms','app','ussd','api','dashboard','referral') NOT NULL
  enrolled_at         TIMESTAMP NOT NULL
  total_points        INT DEFAULT 0 — denormalised running balance
  lifetime_points_earned INT DEFAULT 0
  lifetime_points_redeemed INT DEFAULT 0
  lifetime_spend_kes  DECIMAL(12,2) DEFAULT 0.00
  total_visits        INT UNSIGNED DEFAULT 0
  last_visit_at       TIMESTAMP NULL
  last_points_earned_at TIMESTAMP NULL
  referred_by_customer_id BIGINT UNSIGNED NULL (FK → customers.id)
  fcm_token           VARCHAR(500) NULL — Firebase push token
  app_installed       BOOLEAN DEFAULT FALSE
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  deleted_at          TIMESTAMP NULL — soft delete (anonymise on DSAR deletion)

  UNIQUE INDEX idx_customers_tenant_phone (tenant_id, phone)
  INDEX idx_customers_tenant (tenant_id)
  INDEX idx_customers_status (tenant_id, status)
  INDEX idx_customers_birthday (tenant_id, date_of_birth)
  INDEX idx_customers_last_visit (tenant_id, last_visit_at)
  INDEX idx_customers_referrer (referred_by_customer_id)
```

**Notes:**
- `total_points` is a denormalised cache. Source of truth is the sum of `point_transactions`. Reconciled periodically.
- `phone` and `name` use Laravel's `encrypted` cast — stored as ciphertext, decrypted at application layer.
- Phone is unique within a tenant but not globally — the same person can be enrolled at multiple merchants.

---

### 8. `customer_consents`

KDPA compliance: tracks what each customer has consented to, when, and via which channel.

```
customer_consents
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  customer_id         BIGINT UNSIGNED NOT NULL (FK → customers.id)
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  consent_type        ENUM('enrolment','marketing','data_sharing') NOT NULL
  granted_at          TIMESTAMP NOT NULL
  revoked_at          TIMESTAMP NULL
  channel             ENUM('sms','app','ussd','web','api') NOT NULL
  consent_version     VARCHAR(20) NOT NULL — e.g. "1.0", "1.1"
  ip_address          VARCHAR(45) NULL — if granted via web/app
  created_at          TIMESTAMP

  INDEX idx_consents_customer (customer_id)
  INDEX idx_consents_tenant (tenant_id, consent_type)
```

---

### 9. `loyalty_programs`

Each tenant has one loyalty programme (for now). The table supports multiple per tenant for future coalition features.

```
loyalty_programs
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  name                VARCHAR(255) NOT NULL — e.g. "Naku Rewards"
  description         TEXT NULL
  points_currency_name VARCHAR(50) DEFAULT 'points' — e.g. "stars", "stamps"
  points_to_kes_ratio DECIMAL(8,4) DEFAULT 1.0000 — 1 point = X KES (for liability reporting)
  points_expiry_days  INT UNSIGNED DEFAULT 365 — 0 = never expire
  expiry_warning_days INT UNSIGNED DEFAULT 30 — notification sent X days before expiry
  is_active           BOOLEAN DEFAULT TRUE
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  deleted_at          TIMESTAMP NULL

  INDEX idx_programs_tenant (tenant_id)
```

---

### 10. `loyalty_rules`

Earning rules attached to a loyalty programme. Multiple rules can be active simultaneously.

```
loyalty_rules
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  program_id          BIGINT UNSIGNED NOT NULL (FK → loyalty_programs.id)
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id) — denormalised for query performance
  type                ENUM('visit','spend','product','milestone','birthday','referral','bonus') NOT NULL
  name                VARCHAR(255) NOT NULL — merchant-facing label, e.g. "Earn 10 pts per visit"
  description         TEXT NULL

  -- Type-specific fields (nullable, used per type)
  points_awarded      INT UNSIGNED NULL — flat points (visit, product, referral)
  min_spend_kes       DECIMAL(10,2) NULL — minimum spend to qualify (spend type)
  points_per_kes      DECIMAL(8,4) NULL — e.g. 1 point per KES 100 (spend type)
  multiplier          DECIMAL(5,2) NULL — e.g. 2.0 for double points (bonus, birthday)
  milestone_target    INT UNSIGNED NULL — Nth visit/purchase triggers bonus (milestone)
  milestone_bonus_points INT UNSIGNED NULL — bonus points on milestone
  product_sku         VARCHAR(100) NULL — specific product (product type)
  referral_qualifying_spend_kes DECIMAL(10,2) NULL — min spend by referred customer before referral points credit

  -- Scheduling & limits
  start_date          DATE NULL — rule active from (NULL = immediate)
  end_date            DATE NULL — rule active until (NULL = indefinite)
  active_days_of_week JSON NULL — e.g. ["saturday","sunday"] for weekend-only bonus
  max_points_per_customer_per_day INT UNSIGNED NULL — daily cap per customer
  max_points_per_day  INT UNSIGNED NULL — daily cap across all customers (for this rule)

  -- Rule interaction
  stack_with_others   BOOLEAN DEFAULT TRUE — can fire alongside other rules
  priority            INT UNSIGNED DEFAULT 0 — evaluation order (lower = first)
  is_active           BOOLEAN DEFAULT TRUE

  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  deleted_at          TIMESTAMP NULL

  INDEX idx_rules_program (program_id)
  INDEX idx_rules_tenant_active (tenant_id, is_active)
  INDEX idx_rules_type (tenant_id, type, is_active)
```

---

### 11. `point_transactions`

Immutable ledger of all point movements. Source of truth for customer balances.

```
point_transactions
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  customer_id         BIGINT UNSIGNED NOT NULL (FK → customers.id)
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id) — denormalised
  location_id         BIGINT UNSIGNED NULL (FK → tenant_locations.id) — where the transaction happened
  rule_id             BIGINT UNSIGNED NULL (FK → loyalty_rules.id) — NULL for manual adjustments, voids, expiry
  type                ENUM('earn','redeem','expire','adjust','void') NOT NULL
  points              INT NOT NULL — positive for earn, negative for redeem/expire/void
  balance_after       INT NOT NULL — customer's balance after this transaction
  amount_spent_kes    DECIMAL(10,2) NULL — purchase amount that triggered this (spend-based rules)
  reference           VARCHAR(255) NULL — free text, e.g. "Receipt #1234"
  note                TEXT NULL — staff note or system note
  triggered_by        ENUM('cashier','dashboard','api','system','customer') NOT NULL
  triggered_by_user_id BIGINT UNSIGNED NULL — FK to users.id or cashiers.id depending on context
  idempotency_key     VARCHAR(100) NULL — prevents double-processing on retries
  voided_at           TIMESTAMP NULL — if this transaction was voided
  voided_by_user_id   BIGINT UNSIGNED NULL
  void_reason         VARCHAR(255) NULL
  created_at          TIMESTAMP
  -- no updated_at — ledger entries are immutable (voids create new entries)

  INDEX idx_pt_customer (customer_id, created_at)
  INDEX idx_pt_tenant (tenant_id, created_at)
  INDEX idx_pt_location (tenant_id, location_id, created_at)
  INDEX idx_pt_type (tenant_id, type, created_at)
  INDEX idx_pt_idempotency (idempotency_key)
  INDEX idx_pt_rule (rule_id)
```

---

### 12. `rewards`

Redeemable rewards catalogue per loyalty programme.

```
rewards
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  program_id          BIGINT UNSIGNED NOT NULL (FK → loyalty_programs.id)
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id) — denormalised
  name                VARCHAR(255) NOT NULL — e.g. "Free Coffee"
  description         TEXT NULL
  image_url           VARCHAR(500) NULL — for display in customer app
  type                ENUM('discount','freebie','cashback','custom') NOT NULL
  points_required     INT UNSIGNED NOT NULL
  discount_value_kes  DECIMAL(10,2) NULL — for discount/cashback types
  discount_percentage DECIMAL(5,2) NULL — alternative to fixed value
  max_redemptions_per_customer INT UNSIGNED NULL — NULL = unlimited
  max_redemptions_total INT UNSIGNED NULL — global stock limit
  total_redeemed      INT UNSIGNED DEFAULT 0 — counter
  terms_and_conditions TEXT NULL
  is_active           BOOLEAN DEFAULT TRUE
  starts_at           TIMESTAMP NULL
  expires_at          TIMESTAMP NULL
  sort_order          INT UNSIGNED DEFAULT 0
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  deleted_at          TIMESTAMP NULL

  INDEX idx_rewards_program (program_id)
  INDEX idx_rewards_tenant_active (tenant_id, is_active)
```

---

### 13. `redemptions`

Records of reward redemptions by customers.

```
redemptions
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  customer_id         BIGINT UNSIGNED NOT NULL (FK → customers.id)
  reward_id           BIGINT UNSIGNED NOT NULL (FK → rewards.id)
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id) — denormalised
  location_id         BIGINT UNSIGNED NULL (FK → tenant_locations.id)
  point_transaction_id BIGINT UNSIGNED NULL (FK → point_transactions.id) — the debit entry
  points_used         INT UNSIGNED NOT NULL
  status              ENUM('pending','confirmed','rejected','voided') DEFAULT 'pending'
  requested_at        TIMESTAMP NOT NULL — when customer initiated
  confirmed_at        TIMESTAMP NULL — when cashier/manager confirmed
  confirmed_by        BIGINT UNSIGNED NULL — cashier_id or user_id
  rejected_at         TIMESTAMP NULL
  rejection_reason    VARCHAR(255) NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  INDEX idx_redemptions_customer (customer_id, created_at)
  INDEX idx_redemptions_tenant (tenant_id, created_at)
  INDEX idx_redemptions_status (tenant_id, status)
  INDEX idx_redemptions_reward (reward_id)
```

---

### 14. `customer_referrals`

Tracks referral relationships and qualification status.

```
customer_referrals
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  referrer_customer_id BIGINT UNSIGNED NOT NULL (FK → customers.id)
  referred_customer_id BIGINT UNSIGNED NOT NULL (FK → customers.id)
  status              ENUM('pending','qualified','credited','expired') DEFAULT 'pending'
  qualifying_spend_kes DECIMAL(10,2) DEFAULT 0.00 — running total of referred customer's spend
  referral_points_credited BOOLEAN DEFAULT FALSE
  credited_at         TIMESTAMP NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  UNIQUE INDEX idx_referral_pair (tenant_id, referrer_customer_id, referred_customer_id)
  INDEX idx_referrals_referrer (referrer_customer_id)
  INDEX idx_referrals_status (tenant_id, status)
```

**Notes:**
- Referral points are only credited once `status` changes to `qualified` (referred customer meets minimum spend)
- Maximum 5 credited referrals per customer per month (enforced at application layer)

---

### 15. `sms_wallets`

One wallet per tenant. Single balance covering all SMS types.

```
sms_wallets
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED UNIQUE NOT NULL (FK → tenants.id)
  credits_balance     INT NOT NULL DEFAULT 0 — current available credits
  credits_reserved    INT NOT NULL DEFAULT 0 — reserved for in-flight campaigns/sends
  credits_used_total  INT NOT NULL DEFAULT 0 — lifetime usage counter
  low_balance_alerted_at TIMESTAMP NULL — last time low-balance alert was sent
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  INDEX idx_wallet_tenant (tenant_id)
```

---

### 16. `sms_credit_purchases`

Every top-up transaction for a tenant's SMS wallet.

```
sms_credit_purchases
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  wallet_id           BIGINT UNSIGNED NOT NULL (FK → sms_wallets.id)
  credits_purchased   INT UNSIGNED NOT NULL
  amount_paid_kes     DECIMAL(10,2) NOT NULL
  cost_per_sms_kes    DECIMAL(5,2) NOT NULL — rate at time of purchase
  payment_method      ENUM('mpesa_stk','card','bank_transfer','manual') NOT NULL
  payment_reference   VARCHAR(255) NULL — Flutterwave transaction ref
  flutterwave_tx_id   VARCHAR(100) NULL
  status              ENUM('pending','completed','failed','refunded') DEFAULT 'pending'
  completed_at        TIMESTAMP NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  INDEX idx_purchases_tenant (tenant_id, created_at)
  INDEX idx_purchases_status (status)
```

---

### 17. `campaigns`

SMS campaign definitions created by merchants.

```
campaigns
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  uuid                CHAR(36) UNIQUE NOT NULL
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  created_by_user_id  BIGINT UNSIGNED NOT NULL (FK → users.id)
  name                VARCHAR(255) NOT NULL — internal label
  message             TEXT NOT NULL — SMS body (with placeholder support)
  segment             JSON NULL — targeting criteria, e.g. {"min_points": 100, "inactive_days": 30}
  total_recipients    INT UNSIGNED DEFAULT 0
  credits_reserved    INT UNSIGNED DEFAULT 0
  credits_used        INT UNSIGNED DEFAULT 0
  credits_refunded    INT UNSIGNED DEFAULT 0
  status              ENUM('draft','scheduled','dispatching','completed','partial_failure','failed','cancelled') DEFAULT 'draft'
  scheduled_at        TIMESTAMP NULL — for scheduled sends
  dispatched_at       TIMESTAMP NULL
  completed_at        TIMESTAMP NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  INDEX idx_campaigns_tenant (tenant_id, created_at)
  INDEX idx_campaigns_status (tenant_id, status)
```

---

### 18. `campaign_recipients`

Per-recipient tracking for a campaign. Created in bulk when campaign dispatches.

```
campaign_recipients
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  campaign_id         BIGINT UNSIGNED NOT NULL (FK → campaigns.id)
  customer_id         BIGINT UNSIGNED NOT NULL (FK → customers.id)
  sms_log_id          BIGINT UNSIGNED NULL (FK → sms_logs.id) — linked once SMS is queued
  status              ENUM('pending','sent','delivered','failed','skipped') DEFAULT 'pending'
  created_at          TIMESTAMP

  INDEX idx_cr_campaign (campaign_id, status)
  INDEX idx_cr_customer (customer_id)
```

---

### 19. `sms_logs`

Every SMS sent by the platform, regardless of type.

```
sms_logs
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  campaign_id         BIGINT UNSIGNED NULL (FK → campaigns.id) — NULL for non-campaign SMS
  customer_id         BIGINT UNSIGNED NULL (FK → customers.id)
  recipient_phone     VARCHAR(20) NOT NULL
  message             TEXT NOT NULL
  credits_used        INT UNSIGNED DEFAULT 1
  type                ENUM('campaign','transactional','balance_check','redemption','expiry_warning','birthday','milestone') NOT NULL
  direction           ENUM('outbound','inbound') DEFAULT 'outbound'
  status              ENUM('queued','reserved','sent','delivered','failed','refunded') DEFAULT 'queued'
  gateway             ENUM('africastalking','infobip') DEFAULT 'africastalking'
  gateway_message_id  VARCHAR(255) NULL — AT message ID for delivery tracking
  gateway_cost        DECIMAL(5,2) NULL — actual cost from provider
  sent_at             TIMESTAMP NULL
  delivered_at        TIMESTAMP NULL
  failed_at           TIMESTAMP NULL
  failure_reason      VARCHAR(255) NULL
  retry_count         TINYINT UNSIGNED DEFAULT 0
  created_at          TIMESTAMP

  INDEX idx_sms_tenant (tenant_id, created_at)
  INDEX idx_sms_campaign (campaign_id)
  INDEX idx_sms_status (tenant_id, status, type)
  INDEX idx_sms_gateway_id (gateway_message_id)
  INDEX idx_sms_customer (customer_id, created_at)
```

**Note:** `type` is for analytics and reporting only. All types debit from the same wallet at the same rate.

**Retention:** 12 months, then hard purge.

---

### 20. `tenant_api_keys`

API keys and OAuth2 client credentials for POS integrations.

```
tenant_api_keys
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  name                VARCHAR(255) NOT NULL — e.g. "Main POS", "Branch 2 Till"
  type                ENUM('api_key','oauth_client') DEFAULT 'api_key'
  key_prefix          VARCHAR(8) NOT NULL — first 8 chars for display
  key_hash            VARCHAR(255) NOT NULL — bcrypt hash, plaintext never stored
  client_id           VARCHAR(100) NULL — OAuth2 only
  client_secret_hash  VARCHAR(255) NULL — OAuth2 only
  is_active           BOOLEAN DEFAULT TRUE
  is_rotating         BOOLEAN DEFAULT FALSE — TRUE during key rotation grace period
  rotation_expires_at TIMESTAMP NULL — old key valid until this time
  last_used_at        TIMESTAMP NULL
  created_by          BIGINT UNSIGNED NOT NULL (FK → users.id) — super admin
  revoked_at          TIMESTAMP NULL
  revoked_by          BIGINT UNSIGNED NULL (FK → users.id)
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  INDEX idx_api_keys_tenant (tenant_id)
  INDEX idx_api_keys_prefix (key_prefix)
```

---

### 21. `tenant_api_settings`

Per-tenant API configuration.

```
tenant_api_settings
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED UNIQUE NOT NULL (FK → tenants.id)
  api_access_enabled  BOOLEAN DEFAULT FALSE
  rate_limit_per_day  INT UNSIGNED DEFAULT 500
  webhook_url         VARCHAR(500) NULL
  webhook_secret      VARCHAR(255) NULL — HMAC-SHA256 signing secret
  webhook_events      JSON NULL — e.g. ["points.awarded","points.redeemed","customer.enrolled"]
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
```

---

### 22. `api_request_logs`

Per-request logging for the Public API.

```
api_request_logs
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  api_key_id          BIGINT UNSIGNED NULL (FK → tenant_api_keys.id)
  endpoint            VARCHAR(255) NOT NULL
  method              VARCHAR(10) NOT NULL — GET, POST, etc.
  status_code         SMALLINT UNSIGNED NOT NULL
  request_body        JSON NULL — sanitised (no PII)
  response_time_ms    INT UNSIGNED NULL
  ip_address          VARCHAR(45) NOT NULL
  user_agent          VARCHAR(500) NULL
  created_at          TIMESTAMP

  INDEX idx_api_logs_tenant (tenant_id, created_at)
  INDEX idx_api_logs_key (api_key_id, created_at)
```

**Retention:** 90 days, then hard purge.

---

### 23. `audit_logs`

Immutable audit trail for all permission-sensitive actions.

```
audit_logs
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NULL (FK → tenants.id) — NULL for platform-level actions
  user_id             BIGINT UNSIGNED NULL — FK to users.id or cashiers.id
  user_type           VARCHAR(50) NOT NULL — 'user', 'cashier', 'system', 'api_client'
  user_role           VARCHAR(50) NOT NULL — role at time of action
  action              VARCHAR(100) NOT NULL — e.g. "points.void", "rule.create", "staff.invite"
  entity_type         VARCHAR(100) NOT NULL — e.g. "PointTransaction", "LoyaltyRule", "Cashier"
  entity_id           BIGINT UNSIGNED NOT NULL
  before              JSON NULL — state before change
  after               JSON NULL — state after change
  ip_address          VARCHAR(45) NULL
  user_agent          VARCHAR(500) NULL
  created_at          TIMESTAMP
  -- no updated_at — immutable by design
  -- no soft delete — never deleted

  INDEX idx_audit_tenant (tenant_id, created_at)
  INDEX idx_audit_user (user_id, created_at)
  INDEX idx_audit_entity (entity_type, entity_id)
  INDEX idx_audit_action (action, created_at)
```

**Retention:** 5 years (regulatory).

---

### 24. `subscriptions`

Merchant subscription billing records.

```
subscriptions
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  plan_id             BIGINT UNSIGNED NOT NULL (FK → plans.id)
  status              ENUM('active','past_due','grace_period','suspended','cancelled') DEFAULT 'active'
  amount_kes          DECIMAL(10,2) NOT NULL — billed amount
  billing_cycle_day   TINYINT UNSIGNED NOT NULL — day of month billing occurs
  current_period_start TIMESTAMP NOT NULL
  current_period_end  TIMESTAMP NOT NULL
  grace_period_ends_at TIMESTAMP NULL — 7 days after failed payment
  last_payment_at     TIMESTAMP NULL
  last_payment_ref    VARCHAR(255) NULL — Flutterwave reference
  next_billing_at     TIMESTAMP NOT NULL
  failed_payment_count TINYINT UNSIGNED DEFAULT 0
  cancelled_at        TIMESTAMP NULL
  cancellation_reason VARCHAR(255) NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  INDEX idx_subs_tenant (tenant_id)
  INDEX idx_subs_status (status)
  INDEX idx_subs_next_billing (next_billing_at, status)
```

---

### 25. `payment_transactions`

All payment transactions (subscriptions and SMS top-ups).

```
payment_transactions
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  type                ENUM('subscription','sms_topup','refund') NOT NULL
  amount_kes          DECIMAL(10,2) NOT NULL
  payment_method      ENUM('mpesa_stk','card','bank_transfer') NOT NULL
  gateway             ENUM('flutterwave') DEFAULT 'flutterwave'
  gateway_tx_id       VARCHAR(100) NULL
  gateway_reference   VARCHAR(255) NULL
  mpesa_receipt_number VARCHAR(50) NULL — for M-Pesa payments
  status              ENUM('pending','processing','completed','failed','refunded') DEFAULT 'pending'
  metadata            JSON NULL — gateway-specific data
  completed_at        TIMESTAMP NULL
  failed_at           TIMESTAMP NULL
  failure_reason      VARCHAR(255) NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  INDEX idx_payments_tenant (tenant_id, created_at)
  INDEX idx_payments_status (status)
  INDEX idx_payments_gateway (gateway_tx_id)
```

---

### 26. `fraud_flags`

Automated and manual fraud detection flags.

```
fraud_flags
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  flag_type           ENUM('cashier_velocity','redemption_velocity','self_referral','unusual_amount','manual') NOT NULL
  entity_type         VARCHAR(50) NOT NULL — 'cashier', 'customer', 'point_transaction'
  entity_id           BIGINT UNSIGNED NOT NULL
  severity            ENUM('low','medium','high','critical') NOT NULL
  description         TEXT NOT NULL — human-readable explanation
  evidence            JSON NULL — supporting data (e.g. transaction IDs, amounts, timestamps)
  status              ENUM('open','investigating','resolved','dismissed') DEFAULT 'open'
  resolved_by         BIGINT UNSIGNED NULL (FK → users.id)
  resolved_at         TIMESTAMP NULL
  resolution_note     TEXT NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP

  INDEX idx_fraud_tenant (tenant_id, status)
  INDEX idx_fraud_entity (entity_type, entity_id)
  INDEX idx_fraud_severity (severity, status)
```

---

### 27. `notification_queue`

In-app notifications stored for delivery when customer next opens the app. Used as fallback when SMS delivery fails or wallet has insufficient credits.

```
notification_queue
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  customer_id         BIGINT UNSIGNED NOT NULL (FK → customers.id)
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  type                ENUM('points_earned','points_expired','reward_available','redemption_confirmed','campaign','system') NOT NULL
  title               VARCHAR(255) NOT NULL
  body                TEXT NOT NULL
  data                JSON NULL — structured payload for app rendering
  read_at             TIMESTAMP NULL
  push_sent           BOOLEAN DEFAULT FALSE — whether FCM push was attempted
  push_sent_at        TIMESTAMP NULL
  created_at          TIMESTAMP

  INDEX idx_notif_customer (customer_id, read_at)
  INDEX idx_notif_tenant (tenant_id, created_at)
```

---

### 28. `merchant_health_scores`

Daily snapshot of merchant health for churn prediction and intervention.

```
merchant_health_scores
  id                  BIGINT UNSIGNED AUTO_INCREMENT PK
  tenant_id           BIGINT UNSIGNED NOT NULL (FK → tenants.id)
  score               TINYINT UNSIGNED NOT NULL — 0-100
  logins_last_7_days  INT UNSIGNED DEFAULT 0
  customers_enrolled_last_30_days INT UNSIGNED DEFAULT 0
  points_awarded_last_7_days INT UNSIGNED DEFAULT 0
  campaigns_sent_last_30_days INT UNSIGNED DEFAULT 0
  wallet_balance      INT UNSIGNED DEFAULT 0
  calculated_at       TIMESTAMP NOT NULL
  created_at          TIMESTAMP

  INDEX idx_health_tenant (tenant_id, calculated_at)
  INDEX idx_health_score (score, calculated_at)
```

**Note:** One row per tenant per day. Calculated by a daily scheduled job. Used by the super admin dashboard and automated intervention triggers.

---

## Laravel Model Traits

### `BelongsToTenant`

Applied to every tenant-scoped model. Automatically injects `tenant_id` filter on all queries.

```php
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if ($tenantId = app('tenant.context')->id()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (!$model->tenant_id && $tenantId = app('tenant.context')->id()) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

### `HasUuid`

Applied to models that expose IDs externally (customers, rewards, transactions, etc.).

```php
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
```

### `Auditable`

Applied to models where changes must be logged.

```php
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::updated(function ($model) {
            AuditLog::record($model, 'updated');
        });

        static::deleted(function ($model) {
            AuditLog::record($model, 'deleted');
        });
    }
}
```

---

## Index Strategy Summary

**High-traffic query patterns and their supporting indexes:**

| Query Pattern | Table | Index |
|---------------|-------|-------|
| "All customers for this tenant" | customers | `(tenant_id)` |
| "Customer by phone within tenant" | customers | `UNIQUE (tenant_id, phone)` |
| "Active rules for this tenant" | loyalty_rules | `(tenant_id, is_active)` |
| "Point history for a customer" | point_transactions | `(customer_id, created_at)` |
| "Points by location this month" | point_transactions | `(tenant_id, location_id, created_at)` |
| "SMS delivery status for campaign" | sms_logs | `(campaign_id)` |
| "SMS by gateway message ID" (webhook) | sms_logs | `(gateway_message_id)` |
| "Pending redemptions for tenant" | redemptions | `(tenant_id, status)` |
| "Customers with birthday this week" | customers | `(tenant_id, date_of_birth)` |
| "Inactive customers for win-back" | customers | `(tenant_id, last_visit_at)` |
| "Audit trail for an entity" | audit_logs | `(entity_type, entity_id)` |
| "Fraud flags needing review" | fraud_flags | `(tenant_id, status)` |
| "Subscriptions due for billing" | subscriptions | `(next_billing_at, status)` |

---

## Migration Order

Migrations should be created in dependency order:

1. `plans`
2. `users` (no FK to tenants yet)
3. `tenants` (FK → plans, users)
4. Add `tenant_id` FK to `users`
5. `tenant_settings` (FK → tenants)
6. `tenant_locations` (FK → tenants)
7. `cashiers` (FK → tenants, tenant_locations, users)
8. `loyalty_programs` (FK → tenants)
9. `loyalty_rules` (FK → loyalty_programs, tenants)
10. `customers` (FK → tenants)
11. `customer_consents` (FK → customers, tenants)
12. `customer_referrals` (FK → tenants, customers)
13. `rewards` (FK → loyalty_programs, tenants)
14. `point_transactions` (FK → customers, tenants, tenant_locations, loyalty_rules)
15. `redemptions` (FK → customers, rewards, tenants, tenant_locations, point_transactions)
16. `sms_wallets` (FK → tenants)
17. `sms_credit_purchases` (FK → tenants, sms_wallets)
18. `campaigns` (FK → tenants, users)
19. `campaign_recipients` (FK → campaigns, customers)
20. `sms_logs` (FK → tenants, campaigns, customers)
21. `tenant_api_settings` (FK → tenants)
22. `tenant_api_keys` (FK → tenants, users)
23. `api_request_logs` (FK → tenants, tenant_api_keys)
24. `audit_logs` (FK → tenants)
25. `subscriptions` (FK → tenants, plans)
26. `payment_transactions` (FK → tenants)
27. `fraud_flags` (FK → tenants, users)
28. `notification_queue` (FK → customers, tenants)
29. `merchant_health_scores` (FK → tenants)
30. Spatie permission tables (roles, permissions, model_has_roles, model_has_permissions, role_has_permissions)

---

## Data Retention Summary

| Table | Retention | Action |
|-------|-----------|--------|
| `point_transactions` | 24 months active, 36 months total | Soft delete → hard purge |
| `sms_logs` | 12 months | Hard purge |
| `api_request_logs` | 90 days | Hard purge |
| `audit_logs` | 5 years | Never purged |
| `campaign_recipients` | 12 months (follows sms_logs) | Hard purge |
| `notification_queue` | 90 days after read, 6 months if unread | Hard purge |
| `merchant_health_scores` | 12 months | Hard purge (keep monthly snapshots) |
| `fraud_flags` | 24 months | Archive to cold storage |
| All other tables | Retained while tenant is active + 90 day grace | Anonymise or delete per KDPA |

---

*28 tables. One database. Every tenant scoped. Built for Laravel.*
