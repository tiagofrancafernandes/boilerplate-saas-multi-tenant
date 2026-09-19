# 🤖 AI Agent Guidelines & Operating Procedures

This document defines mandatory operational instructions, coding standards, and workflow protocols for all AI coding agents working on the **Multi-Tenant SaaS Boilerplate** monorepo.

---

## 📋 Task Management System (`tasks/README.md`)

All development tasks, plans, and epics **MUST** strictly adhere to the task lifecycle and folder hierarchy defined in [tasks/README.md](tasks/README.md).

### 1. Folder Structure & Workflow States
```text
tasks/
├── plans/              # Validated implementation plans awaiting execution
├── doing/              # Actively executing tasks (ONLY ONE active task at a time)
├── done/               # Completed and verified tasks
├── paused/             # Blocked or interrupted tasks (with stated pause reason)
├── drafts/             # Early stage ideas and drafts
└── .ignore/            # Untracked files and artifacts
```

### 2. How to Search for New Tasks
When prompted to find, pick up, or work on the next task, follow this exact discovery procedure:
1. **Check `tasks/doing/` first:**
   - Inspect if any task is already in progress. If found, resume or verify if it should be completed or paused before starting another.
2. **Search `tasks/plans/` in priority order:**
   - Files in `tasks/plans/` are prefixed with a 3-digit execution priority (`NNN-sequencia-titulo-MMM.md`).
   - Pick the plan with the lowest number / highest priority (e.g., `001-...` before `002-...`).
3. **Move to `tasks/doing/` upon starting:**
   - Move the chosen plan file from `tasks/plans/` to `tasks/doing/`.
   - Update the task header with start metadata (timestamp, agent name, status: `🟡 In Progress`).

### 3. Creating New Tasks & Plans
When asked to create new tasks or plans:
- **File Naming Convention:**
  ```text
  tasks/plans/NNN-sequence-title-MMM.md
  ```
  - `NNN`: 3-digit priority order (e.g., `001`, `002`, `003`).
  - `sequence`: 2-digit sequence within the topic (e.g., `01`, `02`).
  - `title`: Short kebab-case title (up to 5 words).
  - `MMM`: Unique revision or version identifier (e.g., `001`, `v1`).
- **Minimum Required Content:**
  - Title and related epic/plan.
  - Context and business objective.
  - Detailed checklist of work items (`- [ ] Subtask`).
  - Technical notes, architectural trade-offs, and file links.
  - Explicit acceptance criteria.

### 4. Completing a Task
Before marking a task as finished and moving it to `tasks/done/`:
- [ ] Run automated tests (`pnpm test:php` and `pnpm test`).
- [ ] Validate code style with Laravel Pint (`pnpm lint:php` or `vendor/bin/pint --test`).
- [ ] Validate front-end formatting with Prettier (`pnpm format`).
- [ ] Verify front-end compilation (`pnpm build`).
- [ ] Update the task document with the completion summary and technical notes.
- [ ] Move the file from `tasks/doing/` to `tasks/done/`.
- [ ] Create a clear, descriptive Git commit (following Conventional Commits).

---

## 🏛️ Architecture & Coding Standards (Non-Negotiable)

Every line of code generated must comply with [project-definition.md](project-definition.md) and [UNIVERSAL-CODE-STYLE-RULES.md](UNIVERSAL-CODE-STYLE-RULES.md):

### 1. Back-End (PHP 8.3+ / Laravel 11)
- **Language:** Code, variables, methods, comments, and documentation must be written **100% in English**.
- **Strict Types:** `declare(strict_types=1);` is **mandatory** at the very top of **every PHP file**. Strict types for all arguments and return values.
- **Domain Services & Architecture:**
  - Services must be `final class` with static methods.
  - Must be encapsulated in existence guards:
    ```php
    <?php
    declare(strict_types=1);

    namespace App\Services\Domain;

    if (!class_exists(DomainService::class)) {
        final class DomainService
        {
            public static function execute(): void
            {
                // ...
            }
        }
    }
    ```
- **Control Flow:**
  - **Early Returns & Elseless:** The `else` keyword is **strictly forbidden**. Use guard clauses.
  - **Nesting limit:** Maximum of **1 level** of `if` nesting. Refactor complex branches into smaller private/helper methods.
- **Error Handling:** Never use error suppression (`@`). Use clean `try/catch` blocks.
- **Static References:** Always use `static::` instead of `self::`.
- **Database & Enums:**
  - Native PostgreSQL `ENUM` types are **forbidden**.
  - Status columns must use indexed `VARCHAR(50)` or `SMALLINT` in migrations.
  - Mapped in Eloquent models via `$casts` to **PHP 8.1+ Backed Enums**.
  - Native UTC timezone everywhere.
- **Multi-Tenancy:**
  - Schema isolation in PostgreSQL via `stancl/tenancy` (`PostgreSQLSchemaManager`).
  - Access control and roles via `spatie/laravel-permission`.
- **Payment & Subscriptions:**
  - Agnostic adapter pattern via `PaymentGatewayInterface`.
  - Access cycle dictated strictly by `paid_until` and `trial_ends_at`.
  - Read-only enforcement (HTTP 403) and grace period warning header (`X-Subscription-Warning`) handled by `EnforceSubscriptionAccess` middleware.
- **Demo HTTP Requests:**
  - For any created or modified route, generate or update a `.http` demo file in `backend/dev-contents/demo-requests/` with the `-demo.http` suffix.

### 2. Front-End (Nuxt 3 / Vue 3 / Tailwind CSS)
- **Iconify Mandatory:** Exclusively use `iconify-icon` (format `<collection>:<icon-name>`, e.g., `tabler:`, `fa7-solid:`, `mdi:`) styled with Tailwind classes. No scattered SVG files or icon fonts.
- **Code Style:** 4-space indentation formatted with Prettier (`.prettierrc`).
- **Subscription Awareness:** Use `useSubscription()` composable from `@saas/ui` and global HTTP interceptors to handle 403 read-only states and grace period banners.

---

## 🔄 Git Commit Protocol
- Create focused, atomic Git commits at every relevant milestone or evolution.
- Follow Conventional Commits format (`feat(...)`, `fix(...)`, `test(...)`, `style(...)`, `refactor(...)`, `docs(...)`).
- Never leave unstaged or uncommitted working tree modifications at the end of a milestone.
