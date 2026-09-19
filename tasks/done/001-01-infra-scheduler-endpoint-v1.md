# Task: Secure Web-Triggered Scheduler Endpoint (`/api/infra/scheduler`)

**Related Plan:** Infrastructure & Automation  
**Status:** ✅ Concluído  
**Iniciado em:** 2026-09-19 10:35  
**Concluído em:** 2026-09-19 10:38  
**Responsável:** Antigravity / Gemini AI  
**Resultado:** Endpoint `/api/infra/scheduler` implementado com autenticação dual-mode, retorno 404 em caso de falha/desativação, 100% de testes automatizados passando (51 testes no total) e arquivo de demo HTTP criado.

---

## Context
In hosted or serverless environments where native cron daemon access (`crontab -e`) is unavailable, an external scheduler or ping monitor (e.g. UptimeRobot, CronJob.org, Cloud Scheduler) needs to trigger Laravel's schedule runner.

This endpoint (`/api/infra/scheduler`) triggers `Artisan::call('schedule:run')` with dual-mode authentication:
1. Static secret token passed via HTTP Header (`X-Infra-Key` or Bearer header).
2. Authenticated Super Admin user bearer token with permission to call scheduler.
3. For security and obscurity, if either the feature is disabled or authorization fails, the endpoint strictly returns `404 Not Found`.

## Work Scope
- [x] Create `apps/api/config/app_rules.php` with `'infra'` section (`scheduler_enabled`, `scheduler_auth_token`, `scheduler_header_name`, `scheduler_role`, `scheduler_permission`).
- [x] Add corresponding environment variable defaults in `apps/api/.env` and `apps/api/.env.example`.
- [x] Implement `App\Services\Domain\InfraSchedulerService` domain service with strict types, elseless, and max 1 level if nesting.
- [x] Implement `App\Http\Controllers\Infra\SchedulerController` invokable controller.
- [x] Register `api/infra` route prefix in `apps/api/bootstrap/app.php` and create `apps/api/routes/infra.php`.
- [x] Create `.http` demo file in `backend/dev-contents/demo-requests/infra-demo.http`.
- [x] Implement comprehensive Feature and Unit tests with happy and sad paths (`InfraSchedulerTest.php` and `InfraSchedulerServiceTest.php`).
- [x] Run `pnpm test`, `pnpm test:php`, `pnpm lint:php` to verify 100% compliance.

## Technical Decisions
- **404 on Auth Failure:** Uses `Symfony\Component\HttpKernel\Exception\NotFoundHttpException` to obscure endpoint existence from unauthorized clients.
- **Dual-Mode Auth:** Supports static token via `X-Infra-Key` or `Authorization: Bearer <static_token>` as well as authenticated Sanctum Super Admin users (`hasRole('super-admin')` or `can('run-scheduler')`).
- **Domain Service Pattern:** `InfraSchedulerService` is `final class` wrapped in `if (!class_exists(...))` with early returns and strict types.

## Acceptance Criteria
- [x] Strictly returns HTTP 404 when disabled or unauthenticated.
- [x] Dual-mode auth: static key or super-admin bearer token.
- [x] Calls `Artisan::call('schedule:run')` on success.
- [x] Comprehensive happy and sad path automated tests passing (51/51 tests).
- [x] Follows PSR-12, Pint, strict types, elseless architecture.
