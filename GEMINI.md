# 🌟 Gemini & Antigravity Assistant Instructions

This document specifies operational workflows and project expectations for **Gemini** and the **Antigravity AI Assistant** working on the **Multi-Tenant SaaS Boilerplate** monorepo.

---

## 📋 Task Discovery & Management Workflow (`tasks/README.md`)

When interacting with tasks, Gemini **MUST** use the system outlined in [tasks/README.md](tasks/README.md):

### 1. Finding / Selecting New Tasks
1. **Always inspect `tasks/doing/` first:**
   - Run `ls tasks/doing/` to detect if an active task exists. If a task is already present, ask the user or proceed with it before picking up a new one.
2. **Search `tasks/plans/` for the next priority:**
   - Run `ls tasks/plans/` and sort by the numeric prefix (`001-`, `002-`, etc.).
   - Select the lowest numbered plan file.
3. **Transition to `doing/`:**
   - Move the plan to `tasks/doing/`:
     ```bash
     mv tasks/plans/NNN-sequence-title-MMM.md tasks/doing/
     ```
   - Update the status header to `🟡 In Progress` with the start timestamp.

### 2. Creating New Tasks & Plans
When creating tasks, write them to `tasks/plans/` using the standard format:
- Path: `tasks/plans/NNN-sequence-title-MMM.md`
- Content structure:
  - Title and Epic/Plan context.
  - Objective and Work Scope (`- [ ] Subtasks`).
  - Acceptance Criteria (strict types, tests, code style).
  - Technical notes and relevant file references.

### 3. Completing Tasks
- **Verify all tests pass (100% success rate on new/updated tests and the entire regression suite).**
- Update the completion metadata in the task document.
- Move the file to `tasks/done/`:
  ```bash
  mv tasks/doing/NNN-sequence-title-MMM.md tasks/done/
  ```
- Commit the changes immediately.

---

## 🧪 Mandatory Automated Testing (Happy Path & Sad Path)

Gemini **MUST** enforce comprehensive automated testing on all code changes:

1. **Mandatory Automated Test Coverage:**
   - **Every feature, business rule, and critical logic** must have automated tests (Unit and/or Feature tests in PHP/Laravel).
   - No feature or modification can be accepted without dedicated tests.
2. **Happy Path & Sad Path Coverage:**
   - Every test suite must cover both:
     - **Happy Path:** Valid inputs, successful business flow, correct state transitions, 2xx HTTP response codes.
     - **Sad Path:** Invalid/malformed data, unauthenticated/unauthorized access (401/403), expired trials or subscriptions, payment failure scenarios, read-only mode blocks, edge cases, and proper error payloads.
3. **Pre-Completion Test Validation:**
   - Before completing any task, running a final commit, or transitioning a task to `tasks/done/`:
     - Run `pnpm test:php` (all PHP unit & feature tests).
     - Run `pnpm test` (monorepo test suites).
     - Ensure **both the newly added/modified tests AND the entire general test suite pass without a single failure**.

---

## 🛠️ Key CLI Commands for Verification

Gemini should routinely execute these commands to verify modifications:

```bash
# Back-end automated tests (Unit & Feature)
pnpm test:php

# Validate PSR-12 code style with Laravel Pint
pnpm lint:php

# Auto-fix PHP code style
pnpm format:php

# Front-end & monorepo build check
pnpm build

# Format Front-end & Markdown with Prettier
pnpm format
```

---

## 🔐 Local Environment Credentials

- Local development credentials (PostgreSQL, Redis, Mailpit, S3/MinIO) are defined in `local-only.credenciais.no-commit.md`.
- Never commit credentials to version control.
- Ensure `apps/api/.env` points to the local Docker/network services described in that file.

---

## ⚠️ Architectural Guardrails (Strict Enforcement)

1. **Strict Typing & English:**
   - Every PHP file begins with `declare(strict_types=1);`.
   - Method signatures must specify types for all parameters and return values.
   - All code, symbols, variables, comments, and commit messages must be in English.
2. **Domain Services:**
   - Defined as `final class` with static methods.
   - Wrapped in `if (!class_exists(...))` existence check.
3. **Early Returns & Elseless:**
   - The `else` keyword is **strictly forbidden**.
   - Maximum 1 level of `if` nesting.
4. **Static Binding:**
   - Use `static::` instead of `self::`.
5. **PostgreSQL & Eloquent:**
   - No PostgreSQL native `ENUM`s. Use indexed `VARCHAR(50)`.
   - Cast Eloquent attributes to PHP 8.1+ Backed Enums.
   - Multi-tenancy uses `PostgreSQLSchemaManager`.
6. **Iconify Only:**
   - Use `iconify-icon` (e.g., `tabler:`, `fa7-solid:`, `mdi:`) with Tailwind CSS classes. No inline SVGs or custom icon fonts.
7. **Demonstration Requests:**
   - All route modifications must have corresponding `.http` request files in `backend/dev-contents/demo-requests/` with the `-demo.http` suffix.
8. **Git Commits:**
   - Commit at every relevant evolution with clear Conventional Commits messages.
