# LoyaltyOS Africa — Planning Index

> Source truth for everything we are building. No code lives here — only decisions, schemas, and roadmaps.

---

## Documents

| File | Description |
|------|-------------|
| [phase-0-foundation.md](./phase-0-foundation.md) | Project scaffolding, multi-tenancy core, auth, database, roles |
| [phase-1-mvp.md](./phase-1-mvp.md) | First paying merchants live — web dashboard + SMS only |
| [phase-2-launch.md](./phase-2-launch.md) | Mobile app APIs, Public API, USSD, full billing, advanced analytics |
| [phase-3-scale.md](./phase-3-scale.md) | Coalition, multi-country, WhatsApp, branded app, advanced fraud |
| [packages.md](./packages.md) | All third-party Laravel / PHP packages required across all phases |

## Source Documents

| File | Description |
|------|-------------|
| [../loyalty-platform-plan-v2.md](../loyalty-platform-plan-v2.md) | Original product & technical plan |
| [../loyalty-platform-schema.md](../loyalty-platform-schema.md) | Original database schema document |

---

## Phase Overview

```
Phase 0 — Foundation        Weeks 1–3
  Project setup, auth, multi-tenancy, all 28+ migrations,
  model traits, roles/permissions, Horizon, Sentry, CI

Phase 1 — MVP               Months 1–4
  Merchant dashboard, points engine (visit + spend),
  customer enrolment (SMS + dashboard), transactional SMS,
  SMS wallet (top-up via M-Pesa), campaign builder,
  basic analytics, fraud basics → first paying merchants

Phase 2 — Launch            Months 5–9
  Customer app API surface, scanner app API surface,
  Public API (v1) + webhooks, USSD channel,
  full points engine (all rule types), expiry engine,
  full billing (subscription auto-charge), full analytics,
  automated merchant health interventions

Phase 3 — Scale             Months 10–18
  Coalition loyalty, POS bridge auto-award,
  WhatsApp Business API, advanced fraud (ML-assisted),
  multi-country (Uganda, Tanzania), branded customer app,
  dashboard i18n (Swahili)
```

---

*Built for Kenya. Designed for Africa.*
