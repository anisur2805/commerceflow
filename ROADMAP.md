# Roadmap

CommerceFlow ships in **vertical slices**. Each release is independently installable and shipped to its full Definition of Done (see [`PRD.md`](PRD.md) §7.6). Nothing is advertised here or in the UI until it exists in the repo with passing CI.

## v0.1 — Walking skeleton + Dashboard (MVP)
- Plugin foundation: bootstrap, Composer/PSR-4, DI container, module loader, activation/deactivation/uninstall, logger.
- HPOS declared compatible; one clean CRUD-based order read path.
- REST: `GET /dashboard`, `GET/PUT /settings`.
- React/TS admin SPA shell: Dashboard + Settings, routing, loading/error/toast states.
- Dashboard shows WooCommerce-native metrics only, cached with defined TTL + invalidation.
- Green CI (PHPCS + PHPStan + ESLint + PHPUnit); README with dashboard screenshot.

## v0.2 — Automation Rules Engine (centerpiece)
- Dedicated rules + rule-log tables; model *trigger → condition(s) → action(s)*, evaluated async via Action Scheduler.
- React rule builder (create/edit/enable/disable/priority/test); REST `/automation` CRUD.
- Dashboard gains an Automation Queue card.
- Hard requirements: loop prevention, idempotency under retry, partial-failure handling, dry-run.

## v0.3 — Order Workflow & Timeline
- Custom HPOS-stored order statuses with guarded transitions.
- Per-order timeline / activity log; REST `/orders`, `/logs`.
- Dashboard gains a workflow/fulfillment card.

## v0.4 — Shipping Rules Engine
- Rule-based shipping (country/state/zip/weight/subtotal/category/class/coupon), priority-ordered, with test/preview.
- REST `/shipping` CRUD; dashboard gains a Shipping Queue card.

## Deferred (not scheduled)
Checkout Builder · Import Engine · Coupon Engine · WP-CLI · Stripe gateway — each only if it earns its place against then-current priorities.

## Out of scope
Payment gateway in MVP · multisite · multi-vendor · GraphQL · AI assistant · PDF invoices · WPML · Shopify/Magento/BigCommerce · WPGraphQL.
