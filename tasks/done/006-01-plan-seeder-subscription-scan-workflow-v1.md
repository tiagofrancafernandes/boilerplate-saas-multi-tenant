# Task: Plan Seeder, Demo User Guard, and Subscription Cycle Sync Workflow

**Priority:** 006
**Sequence:** 01
**Title:** Plan Seeder, Demo User Guard, and Subscription Cycle Sync Workflow
**Version:** v1
**Status:** 🟢 Completed
**Started At:** 2026-09-19T11:10:00-03:00
**Completed At:** 2026-09-19T11:13:00-03:00

---

## 🎯 Context & Business Objective
Ensure comprehensive support for SaaS plans and automated tenant subscription lifecycle management:
1. Provide a `plans` table migration, `Plan` Eloquent model, and `PlanSeeder` with default pricing tiers (Starter, Professional, Enterprise) using `updateOrCreate`.
2. Guard demo user and tenant seeding with an environment/config check (`app.env !== 'production'` or `config('app_rules.seed_demo_users', false)`).
3. Ensure expired subscription users/tenants are clearly seeded.
4. Implement a domain service `SubscriptionCycleSyncService` and Artisan command `subscriptions:check-cycles` that scans tenants in the database and updates status (`active`, `past_due`, `suspended`) based on trial, paid periods, and grace periods.
5. Register the command in `routes/console.php` for Laravel Scheduler compatibility.
6. Provide comprehensive automated Unit and Feature tests with happy path and sad path coverage.

---

## 📋 Checklist
- [x] Add `seed_demo_users` configuration to `config/app_rules.php` and `.env.example`.
- [x] Create migration for `plans` table and `Plan` model with strict types and enum casting.
- [x] Create `PlanSeeder` with `updateOrCreate` for Starter, Professional, and Enterprise plans.
- [x] Update `TenantSeeder` to respect the `seed_demo_users` / non-production guard and ensure expired, pending, and paid tenants and users are seeded.
- [x] Register `PlanSeeder` in `DatabaseSeeder`.
- [x] Create `App\Services\Domain\SubscriptionCycleSyncService` following elseless domain service architecture.
- [x] Create Artisan command `App\Console\Commands\CheckSubscriptionCyclesCommand` (`subscriptions:check-cycles`).
- [x] Register daily schedule in `routes/console.php`.
- [x] Add Feature and Unit tests for `Plan`, `PlanSeeder`, `SubscriptionCycleSyncService`, and `CheckSubscriptionCyclesCommand` covering both happy and sad paths.
- [x] Verify 100% pass rate across test suite (`pnpm test:php`, `pnpm test` - 101 tests passed: 76 PHP + 25 Vitest).
- [x] Run Laravel Pint formatting and verification (`pnpm format:php`, `pnpm lint:php` - clean).
- [x] Move task to `tasks/done/` and commit changes.

---

## 📊 Verification & Test Summary
- `pnpm test:php`: 76 passed (236 assertions)
- `pnpm test:ui`: 25 passed (25 tests)
- Total monorepo tests: 101 passed, 0 failed
- `pnpm lint:php`: Laravel Pint passed with 0 issues
- `pnpm build`: 3 Nuxt applications compiled successfully
