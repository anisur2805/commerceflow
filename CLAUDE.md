# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Current state

This repository is **pre-implementation**. The only file present is `PRD.md` (the product requirements document). There is no source code, `composer.json`, `package.json`, build config, tests, or git history yet. When implementing, scaffold the toolchain described below — do not assume it already exists.

CommerceFlow is a WooCommerce operations & automation plugin. It has a dual purpose that shapes every decision: (1) a genuinely usable WooCommerce ops layer, and (2) a senior-engineering demonstration of production-grade WooCommerce depth (HPOS-correct data work, background processing, a React admin, an event-driven automation engine). Anything that only re-proves "modern WordPress plugin" is out of scope.

## Planned toolchain (build these when scaffolding)

- **PHP 8.1+**, WordPress latest stable, **WooCommerce latest stable with HPOS**.
- **Composer** — PSR-4 autoloading + dev tooling (PHPCS WordPress standards, PHPStan, PHPUnit).
- **@wordpress/scripts** — React/TypeScript build for the admin SPA.
- **Node** — ESLint, Playwright E2E (flagship flows only), front-end build.
- **CI gate** (must be green on `main`): PHPCS + PHPStan + ESLint + PHPUnit. Playwright on one or two flagship flows only (dashboard load, rule create) to avoid flaky-test drag.

Exact versions/config are meant to live in `docs/03-coding-standards.md` (not yet written).

## Architecture (non-negotiable constraints)

These come from the PRD and hold across all slices:

- **HPOS-first, CRUD-only.** All order reads/writes go through the WooCommerce CRUD layer. Never assume postmeta storage. Declare HPOS compatibility.
- **REST-first.** All admin data flows through REST endpoints — never inline PHP-echoed state. Planned endpoints: `GET /dashboard`, `GET/PUT /settings`, `/automation`, `/orders`, `/logs`, `/shipping`.
- **DI-based modular design.** DI container + module loader; dedicated DB tables instead of postmeta abuse.
- **Background processing via Action Scheduler** (bundled with WooCommerce). Automation execution and future imports run in the background, never in the web request.
- **Security:** every REST endpoint enforces capability/permission checks (none public by default); sanitize on input, escape on output, nonces per WP standards.
- **Caching:** expensive analytics use transient/object cache with explicit invalidation on relevant order events (dashboard warm read target < 300 ms).
- **i18n + a11y:** all user-facing strings textdomained; admin UI targets WCAG 2.1 AA.

## The centerpiece: Automation Rules Engine (v0.2)

Model is **trigger → condition(s) → action(s)**, stored in dedicated rules + rule-log tables, evaluated **asynchronously** via Action Scheduler. Four hard requirements — treat as first-class, they gate shipping (FR-AUTO-4..7):

1. **Loop prevention** — an action mutating an order must not re-trigger its own rule; enforce a configurable recursion/depth guard and log suppressed re-entries.
2. **Idempotency** — retried Action Scheduler jobs must not double-apply (no duplicate coupon/note/status write).
3. **Partial failure** — if action *n* of *m* fails, order state stays uncorrupt; log with context; apply documented continue/stop policy.
4. **Dry-run** — test a rule and report what *would* happen, writing no order changes.

## Release plan (vertical slices — ship each complete)

Build horizontal concerns (REST, DB, tests, CI) incrementally, only as far as the current slice needs. **Never advertise a module in README or UI before it ships.**

- **v0.1 (MVP)** — Plugin foundation (bootstrap, PSR-4, DI, module loader, activation/deactivation/uninstall, logger). One clean CRUD order read path. `GET /dashboard`, `GET/PUT /settings`. React SPA shell: Dashboard + Settings pages, routing, loading/error/toast states. Dashboard shows **WooCommerce-native metrics only** (orders today, revenue today, pending orders, failed payments, 30-day revenue chart, top products) — no cards for unbuilt modules. Cached dashboard query. Green CI. README with dashboard screenshot.
- **v0.2** — Automation Rules Engine (see above) + React rule builder + Automation Queue dashboard card.
- **v0.3** — Custom HPOS-stored order statuses with guarded transitions, per-order timeline/activity log, `/orders` + `/logs`, workflow card.
- **v0.4** — Shipping Rules Engine (priority-ordered conditions), test/preview tool, `/shipping`, Shipping Queue card.
- **Deferred (not scheduled):** Checkout Builder, Import Engine, Coupon Engine, WP-CLI, Stripe gateway. **Explicitly out of scope:** payment gateway in MVP, multisite, multi-vendor, GraphQL, AI assistant, PDF invoices, WPML, non-Woo platforms.

**Dashboard rule (FR-DASH-3):** each slice adds exactly one card for its own module; show no card for a module that isn't shipped.

## Definition of Done (every slice)

Feature works · REST endpoints where applicable · HPOS-compatible · sanitized + escaped · translation-ready · PHPUnit for core logic (analytics query layer, REST permission checks, automation evaluation path — real tests, not smoke tests) · Playwright where designated · PHPCS/PHPStan/ESLint pass · docs updated · `CHANGELOG.md` updated · GitHub release tagged.

## Git workflow

- **Commit after each meaningful task completes** — a working slice, a passing feature, a green-gate fix. Small, self-contained commits, not one giant dump. Don't commit broken/red-CI state on `main`.
- **Conventional Commits** subject (`feat:`, `fix:`, `test:`, `docs:`, `chore:`), imperative, ≤50 chars; body only when the "why" isn't obvious.
- **Do NOT add a Claude / AI co-author trailer** to commits. Author is the human owner only.
- Work on a branch off `main` for a slice; `main` stays installable. Never `git push --force` (blocked by hook).

## Docs

`PRD.md` is doc 00. **Present now:** `README.md`, `CHANGELOG.md`, `ROADMAP.md` (required by the Definition of Done). **Deferred by design:** the deep companion docs (`docs/01-system-architecture.md` … `13-github-actions.md`, ADRs under `docs/adr/`) are written *alongside the slice that implements them*, not upfront — stub architecture docs describing unbuilt code would overclaim, which PRD §0 forbids. Create each doc when its slice lands, and update `CHANGELOG.md` + `ROADMAP.md` as part of that slice's DoD.
