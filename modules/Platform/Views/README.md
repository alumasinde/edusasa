# Platform Admin Views

Platform administration views live under `resources/views/platform/` and are intentionally data-driven.

The dashboard consumes platform-level school and plan data. School onboarding and plan management must use the Platform services/API rather than duplicating business rules in templates.

## UI rules

- Never hard-code package names or feature lists.
- Never expose school-tenant navigation to platform users unless explicitly authorized.
- Keep destructive actions behind confirmation and server-side authorization.
- Use the same CSRF/session protections as the rest of the application.
