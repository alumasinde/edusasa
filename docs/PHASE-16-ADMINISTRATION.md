# Phase 16 — Administration & Settings

Phase 16 completes the school administration foundation without creating a second user/account system.

## Delivered
- Tenant-scoped school identity and operational settings.
- School contact information and timezone.
- Configurable default currency, date format and attendance cutoff.
- Academic year and term labels for UI/configuration consumers.
- Communication enabled/disabled setting.
- Settings permissions and platform permission definitions.
- School-scoped audit log viewer using the existing platform audit table.
- CSRF protection on settings updates.
- Tenant and permission middleware on all routes.

## Routes
- `GET /settings`
- `POST /settings`
- `GET /settings/audit`

## Design note
User/role administration remains backed by the existing RBAC and Platform services. This phase deliberately does not introduce a parallel users table or authentication model. The next hardening pass should expose those existing capabilities through a unified administration UI.

## Test flow
1. Sign in as a school administrator with settings permissions.
2. Open `/settings`.
3. Change school identity, timezone and operational defaults.
4. Save and reload; values should persist in `schools.settings_json`.
5. Open `/settings/audit` and confirm the update is recorded.
6. Remove `settings.manage` and verify POST updates are denied.
7. Switch tenant and verify the previous school's settings/audit entries are not visible.
