# CommerceFlow for WooCommerce

A WooCommerce operations & automation platform: a single modern React admin dashboard to observe your store and automate the order lifecycle — analytics, event-driven rules, custom fulfillment workflows, and rule-based shipping — without writing code.

> **Status:** pre-release / in development. Features are listed here only once they ship. See [`ROADMAP.md`](ROADMAP.md) for what's planned and [`CHANGELOG.md`](CHANGELOG.md) for what's landed.

## Requirements

- PHP 8.1+
- WordPress (latest stable)
- WooCommerce (latest stable) with **HPOS** (High-Performance Order Storage) enabled

## What it does

The differentiator is the **Automation Rules Engine**: merchants define event-driven rules (*trigger → condition → action*) that are evaluated asynchronously via Action Scheduler and interact with WooCommerce orders through the CRUD layer, so everything stays fully HPOS-compatible.

Shipping in vertical slices — each release is independently installable:

- **v0.1** — React admin shell + WooCommerce-native operations dashboard (orders today, revenue today, pending orders, failed payments, 30-day revenue chart, top products) + settings.
- **v0.2** — Automation Rules Engine (the centerpiece).
- **v0.3** — Order workflow & per-order timeline.
- **v0.4** — Shipping Rules Engine.

Only shipped slices appear in the dashboard and in this README.

## Development

The build toolchain is scaffolded as slices need it:

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
