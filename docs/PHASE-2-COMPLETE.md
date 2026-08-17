# Phase 2 — Core Runtime

Phase 2 establishes the application runtime on `main` without changing the repository branch strategy.

## Included

- Dependency injection container
- HTTP router with grouped middleware
- Request/response foundation
- Session management with secure cookie flags
- CSRF token generation and enforcement
- Authentication and authorization boundaries
- Tenant context and tenant resolution
- Module entitlement middleware
- Platform authentication/host middleware boundaries
- View rendering
- Module route loader
- Central exception types
- Application logging
- Notification channel registry
- Environment/configuration and database foundation from Phase 1

## Design rule

Core remains framework-free and modular. Business modules should depend on these stable contracts instead of reaching into HTTP/session/database globals directly.

## Next

Phase 3 adds school operations: students, teachers, academic structure and attendance. Existing SSMS modules remain the source of truth and will be migrated incrementally with their controllers, services, repositories, views and migrations.
