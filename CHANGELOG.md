# Changelog

All notable changes to CommerceFlow are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/); the project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Repository foundation: PRD, `CLAUDE.md`, Claude Code agents, `.claude/settings.json` (permissions + hooks), README, ROADMAP.

## [0.1.0] — 2026-07-09

### Added

- Plugin bootstrap: Composer PSR-4 autoload, DI container, module loader, activator/deactivator/uninstaller, logger.
- HPOS compatibility declaration through `before_woocommerce_init` / `custom_order_tables`.
- Analytics query layer — 6 WooCommerce-native dashboard metrics read via CRUD (orders today, revenue today, pending orders, failed payments, 30-day revenue series, top products).
- Transient-based dashboard caching with configurable TTL and invalidation on order events.
- REST API: `GET /commerceflow/v1/dashboard` (cached analytics) and `GET/PUT /commerceflow/v1/settings` (persisted plugin config).
- React/TypeScript admin SPA built with `@wordpress/scripts`: Dashboard page (metric cards, revenue chart bar, top products table) and Settings page (cache toggle via `@wordpress/components`), with `react-router-dom` client-side routing.
- Quality gates: PHPCS (WordPress-Extra + PHPCompatibilityWP), PHPStan (level 1), ESLint, PHPUnit.
- PHPUnit test suite: 13 unit tests covering Container, ModuleLoader, Bootstrap, Logger.
- GitHub Actions CI: PHPCS + PHPStan + ESLint + PHPUnit on push/PR to `main`; Playwright E2E fixture for dashboard load.
- Playwright E2E test spec for dashboard SPA shell, metric cards, and settings navigation.
