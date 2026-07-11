# Roadmap

CommerceFlow ships in **vertical slices**. Each release is independently installable and shipped to its full Definition of Done (see [`PRD.md`](PRD.md) §7.6). Nothing is advertised here or in the UI until it exists in the repo with passing CI.

## v0.1 — Walking skeleton + Dashboard (MVP)

- Plugin foundation: bootstrap, Composer/PSR-4, DI container, module loader, activation/deactivation/uninstall, logger.
- HPOS declared compatible; one clean CRUD-based order read path.
- REST: `GET /dashboard`, `GET/PUT /settings`.
- React/TS admin SPA shell: Dashboard + Settings, routing, loading/error/toast states.
- Dashboard shows WooCommerce-native metrics only, cached with defined TTL + invalidation.
- Green CI (PHPCS + PHPStan + ESLint + PHPUnit); README with dashboard screenshot.

## v0.2 — Automation Rules Engine (centerpiece) — shipped

- Dedicated rules + rule-log tables; model _trigger → condition(s) → action(s)_, evaluated async via Action Scheduler.
- React rule builder (create/edit/enable/disable/delete/test-dry-run); REST `/automation` CRUD + `/automation/{id}/dry-run` + `/automation/logs`.
- Dashboard gains an Automation Queue card.
- Hard requirements: loop prevention (RecursionGuard), idempotency under retry (IdempotencyStore), partial-failure handling (ExecutionPolicy continue/stop), dry-run (DryRunReporter).

## v0.3 — Order Workflow & Timeline — shipped

- Custom HPOS-stored order statuses (Packing, Ready to Ship, Shipped) with guarded transitions (`TransitionGuard`) — invalid moves rejected, never persisted.
- Per-order timeline / activity log merging status changes and automation actions; REST `/orders` (list, guarded transition, timeline) + `/logs`.
- Dashboard gains a Fulfillment card.

## v0.4 — Shipping Rules Engine — shipped

- Rule-based shipping (country/state/postcode/weight/subtotal/category/class/coupon), priority-ordered (first match wins), with a preview tool.
- REST `/shipping` CRUD + `/shipping/preview`; live rates injected via `woocommerce_package_rates`. Dashboard gains a Shipping card.

## Deferred (not scheduled)

Checkout Builder · Import Engine · Coupon Engine · WP-CLI · Stripe gateway — each only if it earns its place against then-current priorities.

## Out of scope

Payment gateway in MVP · multisite · multi-vendor · GraphQL · AI assistant · PDF invoices · WPML · Shopify/Magento/BigCommerce · WPGraphQL.
