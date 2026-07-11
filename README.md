# CommerceFlow for WooCommerce

A WooCommerce operations & automation platform: a single modern React admin dashboard to observe your store and automate the order lifecycle — analytics, event-driven rules, custom fulfillment workflows, and rule-based shipping — without writing code.

> **Status:** [v0.3 released](CHANGELOG.md) — Order workflow & timeline. See [`ROADMAP.md`](ROADMAP.md) for what's planned.

![CommerceFlow Dashboard](.github/screenshot-dashboard.png)
_A screenshot of the CommerceFlow dashboard will be added here once a test store is available._

## Requirements

- PHP 8.1+
- WordPress (latest stable)
- WooCommerce (latest stable) with **HPOS** (High-Performance Order Storage) enabled

## Features (shipped)

### v0.1 — Walking skeleton + Dashboard

- **Plugin foundation:** PSR-4 autoloading, DI container, module loader, logger, HPOS compatibility declaration.
- **REST API:** `GET /commerceflow/v1/dashboard` (cached analytics), `GET|PUT /commerceflow/v1/settings` (persisted config).
- **Dashboard:** Orders today, revenue today, pending orders, failed payments, 30-day revenue chart, top products — all read via WooCommerce CRUD layer only.
- **Settings page:** Cache toggle and cache TTL control with toast notifications on save.
- **CI gate:** PHPCS, PHPStan, ESLint, PHPUnit — all pass on `main` via GitHub Actions.

### v0.2 — Automation Rules Engine

- **Rules engine:** _trigger → condition(s) → action(s)_ stored in dedicated tables, evaluated asynchronously via Action Scheduler.
- **Hard guarantees:** loop prevention, idempotency under retry, partial-failure policy, and dry-run (no side effects).
- **REST API:** `/commerceflow/v1/automation` CRUD, `/automation/{id}/dry-run`, `/automation/logs`.
- **React rule builder** and a dashboard **Automation Queue** card.

### v0.3 — Order Workflow & Timeline

- **Custom fulfillment statuses** (Packing, Ready to Ship, Shipped) stored HPOS-compatibly, with **guarded transitions** — invalid moves are rejected, never persisted.
- **Per-order timeline:** every status change and automation action, with actor and timestamp.
- **REST API:** `/commerceflow/v1/orders` (list, guarded transition, timeline) and `/logs` (merged activity feed).
- **React Orders page** with one-click transitions and a timeline modal, plus a dashboard **Fulfillment** card.

## Development

```bash
composer install       # PHP deps + dev tooling (PHPCS, PHPStan, PHPUnit)
npm install            # front-end deps
npm run build          # build the React admin (@wordpress/scripts)
```

Quality gate (must pass before a slice ships):

```bash
composer phpcs         # WordPress coding standards
composer phpstan       # static analysis
npm run lint           # ESLint
composer test          # PHPUnit
```

## License

GPL v2 or later.
