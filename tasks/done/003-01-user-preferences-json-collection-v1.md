# Task: User Preferences Column (JSON / AsCollection), Default Merging, API & UI Settings

**Related Plan:** User Management & Preferences  
**Status:** ✅ Concluído  
**Iniciado em:** 2026-09-19 10:46  
**Concluído em:** 2026-09-19 10:49  
**Responsável:** Antigravity / Gemini AI  
**Resultado:** Coluna `preferences` (`jsonb`) adicionada via migration, cast `AsCollection` no model `User`, método resiliente de merge de preferências com valores padrão (`locale => null`, `timezone => 'UTC'`, `color_scheme => null`), endpoints `GET/PUT /api/user/preferences`, 100% de testes passando (62 testes no total), composable defensivo `useUserPreferences` e modal de preferências integrado no front-end.

---

## Context
Add a `preferences` column on the `users` table as a `jsonb` field. In the `User` model, cast it to `AsCollection` and provide a robust method (`getPreferencesWithDefaults`) that merges user-stored preferences with system defaults (`locale => null`, `timezone => 'UTC'`, `color_scheme => null`). This ensures that even when new preferences are added to the application in the future, they are always present with default values in the returned collection. Build API endpoints, automated tests (happy and sad paths), and frontend integration with validation and a preferences modal.

## Work Scope
- [x] Create migration adding `preferences` (`jsonb`, nullable) to `users` table.
- [x] Update `User` model with `AsCollection` cast, `DEFAULT_PREFERENCES`, `getPreferencesWithDefaults()`, and `updatePreferences()`.
- [x] Create `UserPreferencesRequest` with validation rules for locale, timezone, and color_scheme.
- [x] Create `UserPreferencesController` with `show` and `update` methods.
- [x] Register routes in `routes/api.php` under `auth:sanctum`.
- [x] Create demo request file `backend/dev-contents/demo-requests/user-preferences-demo.http`.
- [x] Create automated Feature and Unit tests (`UserPreferencesTest.php`) covering happy and sad paths.
- [x] Create `useUserPreferences.ts` composable with validation and fallback guards in `packages/ui`.
- [x] Create `UserPreferencesModal.vue` in `packages/ui` and connect to `apps/web/app.vue`.
- [x] Verify `pnpm test`, `pnpm lint:php`, `pnpm build`, and `pnpm format`.

## Acceptance Criteria
- [x] `preferences` stored as JSON, cast to `Collection`.
- [x] Accessing preferences always returns all defined system preferences even if not present in DB.
- [x] Validation rejects invalid timezones and invalid color schemes with 422.
- [x] Unauthenticated requests return 401.
- [x] Frontend safely validates preference values and provides settings modal.
- [x] 100% tests passing and clean builds (62/62 tests passing).
