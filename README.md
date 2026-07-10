# CommerceFlow for WooCommerce

A WooCommerce operations & automation platform: a single modern React admin dashboard to observe your store and automate the order lifecycle — analytics, event-driven rules, custom fulfillment workflows, and rule-based shipping — without writing code.

> **Status:** [v0.1 released](CHANGELOG.md#010--2026-07-09) — Walking skeleton + dashboard. See [`ROADMAP.md`](ROADMAP.md) for what's planned.

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
