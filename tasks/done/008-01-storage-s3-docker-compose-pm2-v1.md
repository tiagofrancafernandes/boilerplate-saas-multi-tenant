# Task: S3 Default Storage, Docker Compose Example & PM2 Ecosystem Setup

**Priority:** 008
**Sequence:** 01
**Title:** S3 Default Storage, Docker Compose Example & PM2 Ecosystem Setup
**Version:** v1
**Status:** 🟢 Completed
**Started At:** 2026-09-19T11:29:00-03:00
**Completed At:** 2026-09-19T11:32:00-03:00

---

## 🎯 Context & Business Objective
Standardize backend storage to S3 by default, provide a ready-to-use Docker Compose configuration for local dependencies, and provide a PM2 ecosystem file to run all frontend apps, Laravel API, queue worker, and scheduler concurrently:
1. **Default Storage to S3**:
   - Update `apps/api/config/filesystems.php` default disk fallback from `local` to `s3` (`env('FILESYSTEM_DISK', 's3')`).
   - Ensure strict typing (`declare(strict_types=1);`) in `filesystems.php`.
2. **Docker Compose & Gitignore**:
   - Ignore `compose.y*ml` and `docker-compose.y*ml` in `.gitignore`.
   - Allow tracking of `compose.example.yml` (`!compose.example.yml`).
   - Create `compose.example.yml` configured for local development:
     - PostgreSQL 16+ on port `1010` (DB: `dev_boilerplate_saas`, user: `postgres`, pass: `postgres`).
     - Redis 7+ Alpine on port `1020`.
     - MinIO (S3 compatible) on port `9001` (API) & `9002` (Console) with bucket initialization (`generic`).
     - Mailpit SMTP (`1025`) & Web (`8025`) commented out.
3. **PM2 Ecosystem Configuration**:
   - Create `ecosystem.config.cjs` configured for:
     - `@saas/site` on port `3000`
     - `@saas/web` on port `3001`
     - `@saas/admin` on port `3002`
     - `api-server` (`php artisan serve --port=8000`)
     - `api-worker` (`php artisan queue:work --sleep=3 --tries=3`)
     - `api-scheduler` (`php artisan schedule:work`)
   - Add PM2 helper scripts in `package.json`.
4. **Verification & Tests**:
   - Run unit and feature tests to ensure zero regressions (`pnpm test:php`, `pnpm test`).
   - Lint with Laravel Pint (`pnpm lint:php`).
   - Move task to `tasks/done/` and commit.

---

## 📋 Checklist
- [x] Set default filesystem disk to `s3` in `apps/api/config/filesystems.php` with `declare(strict_types=1);`.
- [x] Update `.gitignore` to ignore `compose.y*ml` and `docker-compose.y*ml` while keeping `compose.example.yml`.
- [x] Create `compose.example.yml` with PostgreSQL, Redis, MinIO (+ bucket init), and commented Mailpit.
- [x] Create `ecosystem.config.cjs` for PM2 to orchestrate frontends, Laravel API, queue worker, and scheduler.
- [x] Add PM2 management commands to root `package.json`.
- [x] Run automated tests and linting (132 tests passing: 107 PHP + 25 Vitest).
- [x] Move task to `tasks/done/` and commit.

---

## 📊 Verification & Test Summary
- `pnpm test:php`: 107 passed (306 assertions)
- `pnpm test:ui`: 25 passed (25 tests)
- Total monorepo tests: 132 passed, 0 failed
- `pnpm lint:php`: Laravel Pint passed with 0 issues
- `compose.example.yml`: verified via `docker compose config`
- `ecosystem.config.cjs`: verified via Node.js evaluation (6 applications loaded)
