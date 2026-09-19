# Task: Infra Queue Worker & Artisan Execution Endpoints with Webhooks

**Priority:** 007
**Sequence:** 01
**Title:** Infra Queue Worker & Artisan Execution Endpoints with Webhooks
**Version:** v1
**Status:** 🟢 Completed
**Started At:** 2026-09-19T11:25:00-03:00
**Completed At:** 2026-09-19T11:28:30-03:00

---

## 🎯 Context & Business Objective
Provide operational endpoints under `/api/infra` and `/infra` for serverless and restricted environments where CLI commands (`queue:work`, `artisan ...`) cannot be triggered directly:
1. **Queue Processor Endpoint (`POST /api/infra/queue` and `POST /infra/queue`)**:
   - Alternative to `queue:work` via CLI.
   - Accepts arguments (connection, queue) and options (`stop_when_empty`, `max_jobs`, `max_time`, `tries`, `rest`, `timeout`, `memory`).
   - Defaults to single-execution action (`stop_when_empty: true`, `max_jobs: 1`) to terminate cleanly.
2. **Artisan Command Endpoint (`POST /api/infra/artisan` and `POST /infra/artisan`)**:
   - Executes arbitrary Artisan commands with user-provided arguments and parameters.
3. **Common Authentication & Authorization**:
   - Same security pattern as `/api/infra/scheduler` (static token via header/bearer OR super-admin user / permission; strict 404 response on unauthorized).
4. **Resilient Webhook Dispatching (`success`, `error`, `final`)**:
   - Optional `webhooks` payload supporting `url`, `method`, `headers`, `body`.
   - Dispatched at appropriate lifecycle stages: success, error, final.
   - 5-second maximum timeout (`Http::timeout(5)`).
   - Non-blocking error handling: Webhook connection/timeout failure NEVER breaks the job or main execution; logs system warnings only.
5. **Demonstration Requests**:
   - Update `apps/api/dev-contents/demo-requests/infra-demo.http`.
6. **Automated Testing**:
   - Comprehensive unit and feature tests covering happy path and sad path.

---

## 📋 Checklist
- [x] Update `config/app_rules.php` with queue and artisan infra options.
- [x] Implement `InfraAuthService` domain service for unified infra authorization.
- [x] Implement `InfraWebhookService` domain service for robust webhook validation and dispatching with 5s timeout and failure isolation.
- [x] Implement `InfraQueueService` domain service to invoke `queue:work` with parameters.
- [x] Implement `InfraArtisanService` domain service to invoke `Artisan::call` with parameters.
- [x] Create `QueueController` and `ArtisanController` under `App\Http\Controllers\Infra\`.
- [x] Register routes in `routes/infra.php`.
- [x] Update `apps/api/dev-contents/demo-requests/infra-demo.http`.
- [x] Add comprehensive Unit and Feature tests for Queue, Artisan, and Webhook dispatching (happy path & sad path).
- [x] Verify 100% test pass rate (`pnpm test:php`, `pnpm test` - 132 tests passing: 107 PHP + 25 Vitest).
- [x] Lint with Laravel Pint (`pnpm lint:php`).
- [x] Move task to `tasks/done/` and commit.

---

## 📊 Verification & Test Summary
- `pnpm test:php`: 107 passed (306 assertions)
- `pnpm test:ui`: 25 passed (25 tests)
- Total monorepo tests: 132 passed, 0 failed
- `pnpm lint:php`: Laravel Pint passed with 0 issues
