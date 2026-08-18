# Phase 18.1 — Initial Platform Administrator Setup

## Bootstrap

Visit `/setup` on the application to create the first Platform Super Admin.

The bootstrap page is available only while `platform_users` is empty. The account is created with a hashed password and assigned the canonical `super_admin` role inside one database transaction.

## Platform login

Use `/platform/login` for subsequent Platform Admin sign-in. Successful authentication stores `platform_user_id` in the session and updates `last_login_at`.

## Security

- CSRF protection on setup, login and logout forms.
- Strong initial password requirements.
- Bootstrap is one-time and database-backed.
- Existing Platform RBAC is reused; no second role system is introduced.
- The first account receives `super_admin`, not a school-scoped role.

## First-time deployment flow

1. Run migrations.
2. Open `/setup`.
3. Create the first Platform Super Admin.
4. The account is signed in automatically.
5. Create a school from Platform → Schools.
6. Use the generated school-admin invitation to activate the School Admin account.
7. School Admin then uses the normal school dashboard.

If platform users already exist, `/setup` is unavailable and `/platform/login` should be used.
