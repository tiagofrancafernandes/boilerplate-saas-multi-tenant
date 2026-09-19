# Task: Database Seeders for Super Admin and Demo Tenants (Valid, Pending, Expired)

**Related Plan:** Database Seeding & Initial Environments  
**Status:** ✅ Concluído  
**Iniciado em:** 2026-09-19 11:05  
**Concluído em:** 2026-09-19 11:07  
**Responsável:** Antigravity / Gemini AI  
**Resultado:** Seeders implementados com `updateOrCreate` para idempotência total. Criados roles/permissions, Super Admin (`admin@mail.com` / `power@123`), Tenant Demo em trial (`demo_tenant`), Tenant pagante ativo (`paid_tenant`), Tenant inadimplente em período de tolerância (`pending_tenant`) e Tenant expirado bloqueado em somente leitura (`expired_tenant`), acompanhados de testes automatizados (`DatabaseSeederTest.php`).

---

## Context
Provide robust, idempotent database seeders for the application using `updateOrCreate` to safely seed and re-seed data without duplication:
- **Roles & Permissions:** `super-admin` role with `run-scheduler` permission.
- **Super Admin:** `admin@mail.com` / `power@123` with role `super-admin`.
- **Tenants:**
  - Demo / Trial Tenant (`demo_tenant`, active trial, `demo@mail.com`).
  - Active Paid Tenant (`paid_tenant`, `paid_until` in future, `paid@mail.com`).
  - Pending Payment Tenant (`pending_tenant`, `paid_until` expired, within grace period, `pending@mail.com`).
  - Read-Only Expired Tenant (`expired_tenant`, grace period elapsed, read-only mode, `expired@mail.com`).

## Work Scope
- [x] Implement `RolesAndPermissionsSeeder` using `updateOrCreate`.
- [x] Implement `AdminUserSeeder` creating `admin@mail.com` with `power@123`.
- [x] Implement `TenantSeeder` creating demo, paid, pending, and expired tenants and users using `updateOrCreate`.
- [x] Update `DatabaseSeeder` to call all seeders sequentially.
- [x] Create automated Feature test `DatabaseSeederTest.php` testing both first execution and repeated execution (idempotency), role assignments, and subscription state validations.
- [x] Verify Pint (`pnpm lint:php`) and all tests passing (`pnpm test` - 89 tests passing).

## Acceptance Criteria
- [x] Running `php artisan db:seed` succeeds without errors.
- [x] Running `php artisan db:seed` multiple times does not produce duplicate records (`updateOrCreate`).
- [x] Super admin user authenticates with `admin@mail.com` and `power@123`.
- [x] Demo, paid, pending, and expired tenants correctly reflect their subscription states.
- [x] 100% automated tests passing.
