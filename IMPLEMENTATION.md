# Implementation Tracker

Ordered, checkable build log for CommerceFlow. **Source of truth for "what's next."**
Product scope lives in [`PRD.md`](PRD.md); slice summary in [`ROADMAP.md`](ROADMAP.md); shipped history in [`CHANGELOG.md`](CHANGELOG.md).

## How to use

- Work **top to bottom**. Do one step, verify it, tick it, commit, move on.
- Each step: `[ ] <task>` — **agent:** who does it — **verify:** the check that proves it's done (Karpathy: no step is done without its verify passing).
- **Commit after each meaningful step** (Conventional Commits, no AI co-author — see [`CLAUDE.md`](CLAUDE.md)).
- Only granular for the **current slice**. Detail a later slice's steps when you start it — don't pre-plan unbuilt code.
- A slice is shippable only when every box under it _and_ its Definition of Done ([`PRD.md`](PRD.md) §7.6) is met.

---

## v0.1 — Walking skeleton + Dashboard (MVP) — shipped 0.1.0

### A. Toolchain & foundation

- [x] `composer.json` — PSR-4 autoload (`CommerceFlow\` → `src/`), PHP 8.1 platform, dev deps (PHPCS+WPCS, PHPStan, PHPUnit) — **agent:** qa-test/woo-backend — **verify:** `composer install` clean; `composer dump-autoload` resolves namespace.
- [x] Lint/analysis config — `phpcs.xml` (WP standards), `phpstan.neon` (level pinned, PHP 8.1) — **agent:** qa-test — **verify:** `composer phpcs` and `composer phpstan` run (0 errors on empty `src/`).
- [x] `package.json` + `@wordpress/scripts` — build/start/lint scripts, ESLint config — **agent:** react-admin — **verify:** `npm install` clean; `npm run build` produces an asset.
- [x] Main plugin file `commerceflow.php` — header, guards, Composer autoload require, bootstrap entrypoint — **agent:** woo-backend — **verify:** plugin activates on WP+Woo with no fatal/notice.
- [x] DI container + module loader; activation / deactivation / uninstall hooks; logger; constants/helpers — **agent:** woo-backend — **verify:** activate→deactivate→uninstall leaves no orphan options/tables; container resolves a test service.
- [x] Declare **HPOS compatibility** (`before_woocommerce_init` → `custom_order_tables`) — **agent:** woo-backend — **verify:** WooCommerce → Features shows CommerceFlow HPOS-compatible with HPOS on.

### B. Backend — data & REST

- [x] Analytics query layer — WooCommerce-native metrics (orders today, revenue today, pending orders, failed payments, 30-day revenue series, top products) via **CRUD only** — **agent:** woo-backend — **verify:** figures reconcile with real Woo order data for same period.
- [x] Caching wrapper — transient/object cache with defined TTL + invalidation on relevant order events (`woocommerce_new_order`, status changes) — **agent:** woo-backend — **verify:** warm read < 300 ms; changed order invalidates affected metric.
- [x] REST `GET /dashboard` — `permission_callback` + capability check — **agent:** woo-backend — **verify:** authorized 200 with real data; unauthenticated 401/403.
- [x] REST `GET/PUT /settings` — sanitize in, escape out, server-side validation — **agent:** woo-backend — **verify:** saved settings survive reload; invalid input rejected with clear error.

### C. Frontend — admin SPA

- [x] Single wp-admin menu mounts the React SPA; app shell + client-side routing — **agent:** react-admin — **verify:** SPA loads in wp-admin; route change, no full reload.
- [x] Settings page — form bound to `GET/PUT /settings`, loading/error/toast states — **agent:** react-admin — **verify:** edit→save→reload persists; error path shows toast.
- [x] Dashboard page — WooCommerce-native metric cards + 30-day revenue chart + top products, from `GET /dashboard`; **no cards for unshipped modules** — **agent:** react-admin — **verify:** renders from real REST data; loading + error states render; keyboard nav / focus OK (WCAG AA).

### D. Tests, gate, ship

- [x] PHPUnit — analytics query layer + REST permission checks (real tests, not smoke) — **agent:** qa-test — **verify:** tests fail without impl, pass with it; `composer test` green.
- [x] Playwright — one flagship flow: dashboard load — **agent:** qa-test — **verify:** headless run passes.
- [x] GitHub Actions CI — PHPCS + PHPStan + ESLint + PHPUnit on push/PR to `main` — **agent:** qa-test — **verify:** workflow green on a PR.
- [x] `code-reviewer` audit against DoD — **agent:** code-reviewer — **verify:** no unresolved high-severity finding.
- [x] README dashboard screenshot; update `CHANGELOG.md` + `ROADMAP.md`; tag `v0.1.0` — **agent:** woo-backend — **verify:** clean install on WP+Woo (HPOS on), dashboard renders, settings persist, CI green.

**Slice done when:** installs clean on WP+Woo (HPOS on) · dashboard renders cached Woo-native metrics · settings persist via REST · CI green.

---

## v0.2 — Automation Rules Engine (centerpiece) — shipped 0.2.0

Shipped 2026-07-09 (see `CHANGELOG.md` `[0.2.0]`). Step-level ticks collapsed on ship; all work below is present in `src/Automation/*` and covered by unit tests.

- [x] Dedicated `commerceflow_rules` + `commerceflow_rule_logs` tables (migration on activation via `dbDelta`) — **agent:** woo-backend
- [x] Engine core: _trigger → condition(s) → action(s)_, async via Action Scheduler — **agent:** automation-engine
- [x] Initial triggers (created/paid/failed/status-changed) + actions (change status/add note/generate coupon/webhook) — **agent:** automation-engine
- [x] **Hard requirements w/ tests:** loop prevention · idempotency under retry · partial-failure policy · dry-run — **agent:** automation-engine + qa-test
- [x] REST `/automation` CRUD + `/automation/{id}/dry-run` + `/automation/logs` — **agent:** woo-backend
- [x] React rule builder (create/edit/enable/disable/priority/test-dry-run) — **agent:** react-admin
- [x] Dashboard Automation Queue card — **agent:** react-admin
- [x] Tests + gate + review + tag `v0.2.0`

## v0.3 — Order Workflow & Timeline — shipped 0.3.0

Shipped 2026-07-11 (see `CHANGELOG.md` `[0.3.0]`). All work below present in `src/Workflow/*` and covered by unit tests.

- [x] Custom HPOS-stored order statuses (`cf-packing`, `cf-ready`, `cf-shipped`) + guarded transitions (`TransitionGuard`) — **agent:** woo-backend
- [x] Per-order timeline / activity log (`commerceflow_order_events` + `OrderEventRepository`) — **agent:** woo-backend
- [x] REST `/orders` (workflow actions), `/logs` — **agent:** woo-backend
- [x] Dashboard Fulfillment card — **agent:** react-admin
- [x] Tests + gate + review + tag `v0.3.0`

## v0.4 — Shipping Rules Engine — shipped 0.4.0

Shipped 2026-07-11 (see `CHANGELOG.md` `[0.4.0]`). All work below present in `src/Shipping/*` and covered by unit tests.

- [x] Rule-based shipping (country/state/postcode/weight/subtotal/category/class/coupon), priority-ordered (first match wins) — **agent:** woo-backend
- [x] Test/preview tool (which rule wins) — **agent:** woo-backend
- [x] REST `/shipping` CRUD + `/shipping/preview` + live rate injection — **agent:** woo-backend
- [x] Dashboard Shipping card — **agent:** react-admin
- [x] Tests + gate + review + tag `v0.4.0`

---

## Deferred (do not start without a decision)

Checkout Builder · Import Engine · Coupon Engine · WP-CLI · Stripe gateway — see [`PRD.md`](PRD.md) §5.5 / NG1–NG4.
