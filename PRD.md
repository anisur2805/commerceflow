# CommerceFlow for WooCommerce — Product Requirements Document (PRD)

|                  |                                      |
| ---------------- | ------------------------------------ |
| **Document**     | `PRD.md` (doc 00 of the `docs/` set) |
| **Status**       | Draft — v0.1 + v0.2 scopes shipped |
| **Owner**        | Anisur Rahman (ClanDevs)             |
| **Doc version**  | 1.0                                  |
| **Last updated** | 2026-07-09                           |
| **License**      | GPL v2+                              |
| **Repository**   | Public GitHub                        |

> **Scope of this document.** This PRD defines _what_ CommerceFlow is, _who_ it's for, _why_ it exists, and _what "done" means_ for each release. It deliberately does **not** contain architecture, folder structure, database DDL, REST request/response schemas, or component design. Those live in the companion docs listed in [§12](#12-related-documents). Keep this file at the requirements layer so it stays valid as the implementation evolves.

---

## 0. Project context & intent (read first)

CommerceFlow has two honest purposes, and both shape the requirements below:

1. **A genuinely usable product** — a WooCommerce operations layer a real store could install and benefit from.
2. **A senior-engineering demonstration** — a public repository that answers one specific interview question: _"Can this engineer build production-grade WooCommerce systems?"_

This dual intent drives three decisions that run through the whole PRD:

- **It complements ProofPulse, not duplicates it.** ProofPulse already demonstrates modern WordPress tooling (TypeScript, Gutenberg blocks, Vite, PSR-4, testing). CommerceFlow's job is _WooCommerce depth_ — HPOS-correct data work, background processing, a React admin, and an event-driven automation engine. Anything that only re-proves "modern WordPress plugin" is out of scope.
- **Ship v0.1 small and complete, then extend.** A finished, green-CI plugin outperforms a half-built platform. The MVP ([§5](#5-scope--release-plan)) is intentionally one to two weeks of work, not the full feature set. Later slices are additive and each must ship _complete_.
- **Never overclaim.** No capability is described as present until it exists in the public repo with passing CI. The completeness bar rises with every feature claimed.

**Primary target audience for the demonstration:** WordPress _product_ companies (e.g. plugin/product teams). This PRD is optimized for that signal. It is a weaker fit for cross-platform agency work (WPML / Shopify / Magento / WPGraphQL / PSD-to-pixel); if that becomes the priority audience, the release plan should be re-weighted before building.

---

## 1. Overview

CommerceFlow is a WooCommerce operations and automation platform. It gives store owners a single modern React dashboard to observe their store and to automate the order lifecycle — from analytics, to event-driven rules, to custom fulfillment workflows and shipping logic — without writing code.

The differentiator is the **Automation Rules Engine**: merchants define event-driven rules (_trigger → condition → action_) that are evaluated asynchronously, stored in dedicated tables, executed through Action Scheduler, and interact with WooCommerce orders through the CRUD layer so they remain fully HPOS-compatible.

---

## 2. Goals & non-goals

### 2.1 Goals

- **G1** — Give store owners at-a-glance operational visibility (orders, revenue, queues, failures) in one React dashboard.
- **G2** — Let merchants automate order-lifecycle actions through configurable event-driven rules, no code required.
- **G3** — Provide custom fulfillment workflow and rule-based shipping as first-class, HPOS-correct WooCommerce features.
- **G4** — Be architecturally exemplary: modular, DI-based, REST-first, tested, and CI-gated from the first commit.
- **G5** — Be safe by default: correct sanitization, escaping, capability checks, and idempotent background processing.

### 2.2 Non-goals (for the MVP and near-term)

- **NG1** — **Not** a payment gateway in the MVP. WooCommerce ships an official Stripe gateway; reinventing it is low signal and high surface area. Deferred to a late slice, only if a specific target role requires it.
- **NG2** — **Not** a full re-implementation of every WooCommerce subsystem. Import/export, coupons, and checkout customization are deferred (see release plan).
- **NG3** — No multisite, multi-vendor, GraphQL, AI assistant, PDF invoices, or email-campaign integration in scope. Tracked as "future / maybe" only.
- **NG4** — **Not** an agency-breadth demonstration. WPML, Shopify/Magento/BigCommerce, WPGraphQL, and PSD-to-pixel work are explicitly out of scope for this product.

---

## 3. Target users

| Persona                                | Description                                | Primary needs                                                                                                        |
| -------------------------------------- | ------------------------------------------ | -------------------------------------------------------------------------------------------------------------------- |
| **Store Owner** (primary)              | Runs a WooCommerce store; not a developer. | See store health at a glance; automate repetitive order handling; configure rules and shipping without code.         |
| **Store Staff / Fulfillment**          | Processes orders day-to-day.               | A clear fulfillment workflow with statuses, a per-order timeline, and reliable status transitions.                   |
| **Developer / Integrator** (secondary) | Extends or audits the plugin.              | Clean modular architecture, REST endpoints, documented extension points, dedicated tables instead of postmeta abuse. |

---

## 4. Success metrics

**Product**

- **PM1** — A merchant can create and activate an automation rule end-to-end with zero code.
- **PM2** — Dashboard first meaningful paint renders cached metrics quickly (target: dashboard REST read < 300 ms warm cache on a representative dataset).
- **PM3** — A merchant can define a custom fulfillment status and move an order through the workflow, with every transition logged.
- **PM4** — A rule that fails partway does not corrupt order state and is visible in logs.

**Engineering / repository**

- **EM1** — CI is green on `main`: PHPCS, PHPStan (target level defined in `03-coding-standards.md`), ESLint, and PHPUnit all pass.
- **EM2** — Core logic is covered by real tests — at minimum the analytics query layer, REST permission checks, and the automation engine's evaluation path — not smoke tests.
- **EM3** — Each tagged release is installable from a clean WordPress + WooCommerce (HPOS enabled) with no fatal errors and no PHP notices.

---

## 5. Scope & release plan

Development proceeds as **vertical slices**. Each release is independently installable and shipped to its Definition of Done ([§7.6](#76-definition-of-done-every-release)). Horizontal concerns (REST, DB, tests, CI) are built incrementally, only as far as the current slice needs.

### 5.1 v0.1 — Walking skeleton + Dashboard _(MVP — target 1–2 weeks)_

The smallest end-to-end product that already shows the target signal.

- Plugin foundation: bootstrap, Composer/PSR-4, namespaces, DI container, module loader, activation/deactivation, uninstall, logger, constants, helpers.
- HPOS declared compatible; one clean CRUD-based order read path.
- REST: `GET /dashboard` (read), `GET/PUT /settings`.
- React/TypeScript admin SPA shell with **Dashboard** and **Settings** pages, routing, loading/error states, toast notifications.
- Dashboard shows **WooCommerce-native metrics only** — orders today, revenue today, pending orders, failed payments, 30-day revenue chart, top products. **No cards for modules that don't exist yet** (see [Risk R3](#10-risks--open-questions)).
- Caching on the dashboard query (transient/object cache) with defined TTL and invalidation on relevant order events.
- Green CI: PHPCS + PHPStan + ESLint + a focused PHPUnit suite (analytics query layer + REST permission checks).
- `README.md` with the dashboard screenshot.

**Done when:** installs clean on WP + Woo (HPOS on), dashboard renders cached Woo-native metrics, settings persist via REST, CI green.

### 5.2 v0.2 — Automation Rules Engine _(the centerpiece)_

- Dedicated tables for rules + rule logs.
- Rule model: **trigger → condition(s) → action(s)**, evaluated asynchronously via Action Scheduler.
- React rule builder (create / edit / enable / disable / priority / test).
- REST: `/automation` CRUD.
- Dashboard gains an **Automation Queue** card (added with this slice, not before).
- **Hard requirements** (see FR-AUTO acceptance criteria): loop prevention, idempotency under retry, partial-failure handling, ordering, dry-run.

### 5.3 v0.3 — Order Workflow & Timeline

- Custom, HPOS-stored order statuses and guarded transitions.
- Per-order timeline / activity log.
- REST: `/orders` (workflow actions), `/logs`.
- Dashboard gains a workflow/fulfillment card.

### 5.4 v0.4 — Shipping Rules Engine

- Rule-based shipping (country/state/zip/weight/subtotal/category/class/coupon/etc.), priority-ordered, with a test/preview tool.
- REST: `/shipping` CRUD.
- Dashboard gains a **Shipping Queue** card.

### 5.5 Later / deferred (not scheduled)

Block-based **Checkout Builder** (Store API / Additional Checkout Fields), **Import Engine** (batched, resumable, Action Scheduler), **Coupon Engine**, **WP-CLI** commands, and finally **Stripe gateway** — each only if it earns its place against then-current priorities. See [NG1–NG4](#22-non-goals-for-the-mvp-and-near-term).

---

## 6. Functional requirements

Requirements use stable IDs. Acceptance criteria are testable. Implementation detail is intentionally absent — see companion docs.

### 6.1 Admin SPA & Settings _(v0.1)_

- **FR-ADMIN-1** — The plugin registers a single admin menu that loads a React SPA built with `@wordpress/scripts`.
  - _Accept:_ SPA loads inside wp-admin; client-side routing between pages works with no full page reload; loading and error states render for every async view.
- **FR-ADMIN-2** — All admin data flows through REST endpoints, never inline PHP-echoed state.
- **FR-SET-1** — Merchant can view and update plugin settings, persisted via `GET/PUT /settings`.
  - _Accept:_ saved settings survive reload; invalid input is rejected server-side with a clear error; success/failure surfaces as a toast.

### 6.2 Dashboard & Analytics _(v0.1, extended per slice)_

- **FR-DASH-1** — Dashboard displays WooCommerce-native operational metrics: orders today, revenue today, pending orders, failed payments, 30-day revenue chart, top products.
  - _Accept:_ figures reconcile with WooCommerce order data for the same period; chart renders from real data.
- **FR-DASH-2** — Metrics are cached with a defined TTL and invalidated when relevant order events occur.
  - _Accept:_ warm-cache dashboard read meets PM2; a new/changed order invalidates the affected metric within the defined window.
- **FR-DASH-3** — Each later slice may add exactly one card for its own module; **no card is shown for a module that is not yet shipped.**

### 6.3 Automation Rules Engine _(v0.2)_

- **FR-AUTO-1** — Merchant can create a rule as _trigger → condition(s) → action(s)_ and enable/disable/prioritize it via the React builder and `/automation` REST.
- **FR-AUTO-2** — Supported triggers (initial set): order created, order paid, order failed, order status changed. Supported actions (initial set): change status, add order note, generate coupon, call webhook. (Full lists live in `07-automation-engine.md`.)
- **FR-AUTO-3** — Rules are evaluated **asynchronously** via Action Scheduler, not inline in the request that fired the trigger.
- **FR-AUTO-4 (loop prevention)** — An action that mutates an order MUST NOT cause the same rule to re-trigger itself; the engine enforces a configurable recursion/depth guard and records suppressed re-entries.
  - _Accept:_ a rule whose action would re-satisfy its own trigger runs once, not unbounded; suppression is logged.
- **FR-AUTO-5 (idempotency)** — Rule execution MUST be idempotent under Action Scheduler retries; a retried job does not double-apply its actions.
  - _Accept:_ forcing a retry of a completed action produces no duplicate coupon / duplicate note / duplicate status write.
- **FR-AUTO-6 (partial failure)** — If action _n_ of _m_ fails, order state is not left corrupt; the failure is logged with context and the engine's continue/stop policy is applied and documented.
  - _Accept:_ a deliberately failing middle action leaves earlier actions intact, later actions handled per policy, and a clear log entry written.
- **FR-AUTO-7 (dry-run)** — Merchant can test a rule without applying its actions.
  - _Accept:_ dry-run reports what _would_ happen and writes no order changes.

### 6.4 Order Workflow & Timeline _(v0.3)_

- **FR-FLOW-1** — Plugin registers custom order statuses stored HPOS-compatibly, with guarded transitions.
- **FR-FLOW-2** — Each order has a timeline/activity log of status changes and automation actions, readable via `/orders` and `/logs`.
  - _Accept:_ every transition and automated action appears in the timeline with actor and timestamp.

### 6.5 Shipping Rules Engine _(v0.4)_

- **FR-SHIP-1** — Merchant can define priority-ordered shipping rules across the supported conditions and enable/disable them via `/shipping` REST and the React UI.
- **FR-SHIP-2** — A test/preview tool shows which rule wins for a given basket/destination.
  - _Accept:_ preview output matches actual applied shipping at checkout for the same inputs.

### 6.6 Deferred feature requirements

Block **Checkout Builder**, **Import Engine**, **Coupon Engine**, **WP-CLI**, and **Stripe gateway** requirements are stubbed as future FRs and specified only when scheduled. They MUST NOT be advertised in README or UI until shipped.

---

## 7. Non-functional requirements

### 7.1 Compatibility

| Target            | Requirement                                                          |
| ----------------- | -------------------------------------------------------------------- |
| PHP               | 8.1+                                                                 |
| WordPress         | Latest stable                                                        |
| WooCommerce       | Latest stable, **HPOS-first**                                        |
| Order data access | Via WooCommerce **CRUD** layer only — no direct postmeta assumptions |
| Browsers          | Current evergreen browsers; responsive admin                         |

- **NFR-COMPAT-1** — The plugin declares HPOS compatibility and performs all order reads/writes through CRUD so it works with custom order tables enabled.

### 7.2 Security

- **NFR-SEC-1** — Every REST endpoint enforces capability/permission checks; no endpoint is public by default.
- **NFR-SEC-2** — All input is sanitized on the way in; all output is escaped on the way out; nonces/permissions used per WordPress standards.
- **NFR-SEC-3** — Webhook actions verify signatures where applicable (detailed in the relevant module doc).

### 7.3 Performance

- **NFR-PERF-1** — Expensive analytics use caching (transients/object cache) with explicit invalidation.
- **NFR-PERF-2** — Bulk and long-running work (automation execution, future imports) runs in the background via Action Scheduler, never in the web request.
- **NFR-PERF-3** — Queries are bounded and paginated; REST list endpoints paginate.

### 7.4 Accessibility & i18n

- **NFR-A11Y-1** — Admin UI targets WCAG 2.1 AA: keyboard navigation, ARIA, visible focus, sufficient contrast, respects reduced-motion.
- **NFR-I18N-1** — All user-facing strings are translation-ready (properly textdomained).

### 7.5 Code quality gates

- **NFR-QA-1** — CI runs and must pass PHPCS (WordPress standards), PHPStan (level per `03-coding-standards.md`), ESLint, and PHPUnit on every push/PR to `main`.
- **NFR-QA-2** — Playwright E2E is applied to **one or two flagship flows only** (e.g. dashboard load, rule create) — not every slice — to avoid flaky-test drag.

### 7.6 Definition of Done (every release)

A slice is done only when: the feature works · REST endpoints added where applicable · HPOS-compatible · sanitized + escaped · translation-ready · PHPUnit tests for core logic · Playwright where designated · PHPCS/PHPStan/ESLint pass · docs updated · `CHANGELOG.md` updated · GitHub release tagged.

---

## 8. Constraints & assumptions

- **C1** — Solo developer; effort is bounded. The MVP is scoped to be finishable in 1–2 weeks alongside other commitments.
- **C2** — Public GPL repository; nothing proprietary or client-derived is included (personal project kept strictly separate from any client work).
- **C3** — The audience assumption is WordPress _product_ companies; a shift to agency-breadth targeting invalidates parts of the release plan (see R4).
- **A1** — Action Scheduler is available (bundled with WooCommerce).
- **A2** — Test/dev environments run WooCommerce with HPOS enabled.

---

## 9. Dependencies

- **WooCommerce** (latest) — orders, CRUD, HPOS, Action Scheduler.
- **@wordpress/scripts** — React/TS build tooling for the admin SPA.
- **Composer** — PSR-4 autoloading, dev tooling (PHPCS, PHPStan, PHPUnit).
- **Node toolchain** — ESLint, Playwright, front-end build.

Exact versions and configuration are pinned in `03-coding-standards.md` and the build config, not here.

---

## 10. Risks & open questions

| ID     | Risk / question                                                                                                                          | Impact                                                                         | Mitigation                                                                                                           |
| ------ | ---------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------- |
| **R1** | Automation engine is framed as "less work than Stripe" but has comparable difficulty (loops, idempotency, partial failure, concurrency). | Under-estimation; the centerpiece becomes a liability if incomplete.           | Treated as first-class hard requirements (FR-AUTO-4..7) with explicit acceptance tests; not shipped until they pass. |
| **R2** | Scope creep back toward the full 17-phase platform.                                                                                      | Never ships; competes with active job search and near-term deliverables.       | v0.1 is fixed small; later slices are additive and optional; non-goals enumerated.                                   |
| **R3** | Dashboard-first shows empty cards for unbuilt modules, killing the "looks like SaaS" effect.                                             | Weak first impression.                                                         | v0.1 dashboard shows Woo-native metrics only; each slice adds its own card (FR-DASH-3).                              |
| **R4** | Product-company targeting vs. agency-breadth targeting.                                                                                  | If the nearest live pipeline is agency work, this product barely speaks to it. | Audience assumption stated (C3); re-weight release plan before building if that changes.                             |
| **R5** | Building an elaborate plugin can substitute for the higher-value work of applying/interviewing.                                          | Feels productive while delaying outcomes.                                      | Ship v0.1, put it into applications, extend only if still searching.                                                 |

---

## 11. Out of scope (explicit)

Payment gateway in MVP · multisite · multi-vendor · GraphQL · AI assistant · PDF invoices · inventory forecasting · customer segmentation · email-campaign integration · WPML · Shopify/Magento/BigCommerce · WPGraphQL · PSD-to-pixel front-end work.

---

## 12. Related documents

This PRD is doc 00 of the engineering set. Detailed _how_ lives in focused companion docs so each stays current independently:

```
docs/
├── PRD.md                    ← this document (product & requirements)
├── 01-system-architecture.md
├── 02-folder-structure.md
├── 03-coding-standards.md
├── 04-database-design.md
├── 05-rest-api.md
├── 06-react-architecture.md
├── 07-automation-engine.md
├── 08-stripe-gateway.md      (deferred)
├── 09-shipping-engine.md
├── 10-checkout-builder.md    (deferred)
├── 11-import-engine.md       (deferred)
├── 12-testing-strategy.md
├── 13-github-actions.md
├── ROADMAP.md
└── CHANGELOG.md
```

Architecture Decision Records (ADRs) capture individual decisions (e.g. "HPOS-first via CRUD", "block checkout over classic", "defer Stripe") under `docs/adr/` as they're made.

---

## 13. Document changelog

| Version | Date       | Change                                                                                                                                                                                                                            |
| ------- | ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1.0     | 2026-07-09 | Initial PRD. v0.1 (walking skeleton + Woo-native dashboard) scope approved; automation engine established as centerpiece with loop/idempotency/partial-failure as hard requirements; Stripe/import/coupons/checkout/CLI deferred. |
| 1.1     | 2026-07-09 | v0.2 (Automation Rules Engine) shipped: rules + rule-log tables, trigger→condition→action model, async Action Scheduler execution, REST `/automation` CRUD + dry-run + logs, React rule builder, dashboard Automation Queue card. FR-AUTO-4..7 hard requirements met with passing tests. |
