# Changelog

All notable changes to CommerceFlow are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/); the project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Repository foundation: PRD, `CLAUDE.md`, Claude Code agents, `.claude/settings.json` (permissions + hooks), README, ROADMAP.

## [0.2.0] — 2026-07-09

### Added

- Automation Rules Engine (centerpiece): dedicated `commerceflow_rules` + `commerceflow_rule_logs` tables created on activation via `dbDelta`; cleaned up on uninstall.
- Rule model: _trigger → condition(s) → action(s)_ — pure `Rule` DTO and `RuleValidator` with allowed triggers (`order_created`, `order_paid`, `order_failed`, `order_status_changed`), action types (`change_status`, `add_order_note`, `generate_coupon`, `call_webhook`), and condition operators (`eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `in`).
- Pure engine layer (unit-tested, no WordPress dependency): `ConditionMatcher`, `ActionPlanner` / `PlannedAction`, `RuleEvaluator` (priority-ordered matching), `DryRunReporter`, `RecursionGuard` (loop prevention), `IdempotencyStore`, `ExecutionPolicy` / `ExecutionResult`.
- Hard requirements: loop prevention (FR-AUTO-4), idempotency under Action Scheduler retry (FR-AUTO-5), partial-failure handling with `continue`/`stop` policy (FR-AUTO-6), dry-run with no side effects (FR-AUTO-7).
- Async execution via Action Scheduler (FR-AUTO-3): trigger listeners schedule `commerceflow_execute_rule` jobs; the callback enforces the recursion guard, idempotency store, and execution policy, then logs the result.
- `$wpdb`-backed `RuleRepository` and `RuleLogRepository` with JSON-encoded config/conditions/actions columns.
- `ActionExecutor` applies planned actions to orders through the WooCommerce CRUD layer (status change, order note, coupon creation, signed-safe webhook).
- REST API: `/commerceflow/v1/automation` CRUD (list, get, create, update, delete), `/automation/{id}/dry-run`, and `/automation/logs` — all capability-gated (`manage_woocommerce` read, `manage_options` write).
- React/TypeScript Automation page: rule builder (create/edit/enable/disable/delete/test-dry-run) with modal editor, per-action-type config fields, and condition rows.
- Dashboard gains an **Automation Queue** card showing recent automation run statuses (FR-DASH-3).
- PHPUnit unit tests for the full pure engine layer (RuleValidator, ConditionMatcher, ActionPlanner, DryRunReporter, RuleEvaluator, RecursionGuard, IdempotencyStore, ExecutionPolicy) plus AutomationModule construction; feature test stubs for the `/automation` REST routes.

### Fixed

- PHPStan `excludePaths` `node_modules/` entry marked optional with `(?)` so the PHP lint CI job (no `npm install`) no longer fails.

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
