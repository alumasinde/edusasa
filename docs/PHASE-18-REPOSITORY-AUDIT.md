# Phase 18 — Full Repository Audit

Phase 18 records the full-repository architecture/security audit and establishes the remediation baseline before end-to-end application testing.

## Audit scope

- Application bootstrap and module loading
- Routing and middleware boundaries
- Multi-tenant data access
- Authentication and authorization
- CSRF protection on state-changing web routes
- API/web route separation
- Database migration consistency
- Cross-module references used by the dashboard
- Error handling and production-safety concerns
- Repository documentation and operational checks

## Findings to remediate before production testing

1. **Dashboard route verification** — confirm both `/` and `/dashboard` are actually registered by the Dashboard module and protected by tenant/auth middleware.
2. **Cross-module schema verification** — dashboard queries reference Students, Teachers, Attendance, Finance, Exams, Timetable and Communication tables; migrations must exist and be applied in the expected order.
3. **Tenant isolation review** — every school-facing repository query must constrain records to the current tenant; ID-only lookups are especially important for detail, update and delete endpoints.
4. **State-changing route review** — POST/DELETE web routes must have CSRF protection unless the endpoint is intentionally API-only.
5. **Permission review** — destructive operations must require the appropriate management permission and, where applicable, an administrator role.
6. **Migration repeatability** — migration execution must be deterministic and must not silently depend on manual ordering.
7. **Production error handling** — debug output must never expose exception traces when `APP_DEBUG` is false.
8. **Dashboard data integrity** — dashboard counts must use canonical module tables and must not introduce duplicate state.

## Required test sequence

1. Run all database migrations on a fresh database.
2. Bootstrap the application with production-like `APP_DEBUG=false` settings.
3. Authenticate as a school administrator and open `/dashboard`.
4. Create/update records in Students, Teachers, Attendance, Finance, Exams and Timetable and verify dashboard values change correctly.
5. Publish Communication content and verify recipient/unread behaviour.
6. Attempt every protected state-changing endpoint without CSRF and confirm rejection.
7. Attempt cross-school record access with valid IDs and confirm rejection/empty result.
8. Attempt privileged actions with insufficient permissions and confirm 403/denial.
9. Exercise invalid routes and application exceptions with debug disabled.
10. Repeat migration/bootstrap tests to confirm idempotent deployment behaviour.

## Audit status

This phase is a repository-level audit baseline. Runtime behaviour still requires execution against the application's PHP/MySQL environment.
