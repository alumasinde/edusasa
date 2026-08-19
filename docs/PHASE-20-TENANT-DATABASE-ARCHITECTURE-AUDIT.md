# Phase 20 — Tenant Database Architecture Audit

## Scope

This audit reviews the current `main` repository before changing the production data architecture. The repository remains a single PHP/MySQL application codebase with tenant-aware row-level isolation in a shared database. The target architecture is one platform database plus one database per school.

## Repository baseline

Audited baseline: `main` at commit `269ddc59f3e94399a89cf5998017ca5d38983b82`.

The repository contains a shared application under `app/`, domain modules under `modules/`, SQL migrations under `database/migrations/`, PHP views under `resources/views/`, the public entry point under `public_html/`, and platform/architecture documentation under `docs/`.

Existing modules include Academic, Attendance, Auth, Communication, Dashboard, Exams, Finance, ParentPortal, Platform, Settings, StudentPortal, Students, Teachers and Timetable.

## Current architecture

The current application is a shared-database, row-isolated multi-tenant design:

- `app/Core/Database.php` creates one singleton PDO connection from `DB_DATABASE`.
- `app/Core/Application.php` registers that database singleton and injects it into authentication, authorization and tenant resolution.
- `TenantResolver` queries the same database's `schools` table from the request hostname.
- `Tenant` stores the resolved school in process-global static state.
- `BaseRepository` defaults tenant operations to `school_id = Tenant::id()`.
- Operational tables from the academic, student, teacher, attendance, finance, examinations, portals, settings and authentication migrations generally carry `school_id`.
- Platform tables already exist in the same database, including schools, plans, features, subscriptions, platform users, platform RBAC and platform audit records.

Therefore the requested architecture is not a greenfield build. It is a database-boundary migration from one shared database to two database classes: one central platform database and independently provisioned tenant databases.

## Target architecture

```text
                         EDU SASA APPLICATION
                                  |
                    +-------------+-------------+
                    |                           |
              PLATFORM DB                  TENANT DB
                    |                           |
              schools                     School A
              domains                     School B
              databases                   School C
              plans                      ...
              subscriptions
              platform users
              platform RBAC
              platform audit
              provisioning

HTTP Host -> TenantResolver -> Platform DB -> School -> Tenant DB Manager -> Tenant PDO -> Module service/repository
```

The platform database is the only source of truth for tenant discovery. Tenant operational databases are never consulted to determine which school owns a request.

## Ownership matrix

| Current area | Current ownership | Target ownership | Migration action |
|---|---|---|---|
| `schools` | Platform catalog | Platform | Keep centrally; extend with immutable tenant/database metadata |
| `school_subscriptions` | Platform | Platform | Keep centrally |
| `plans` | Platform | Platform | Keep centrally |
| `features` / `plan_features` | Platform | Platform | Keep centrally |
| `school_feature_overrides` | Platform | Platform | Keep centrally |
| `platform_users` | Platform | Platform | Keep centrally |
| `platform_roles` / permissions mappings | Platform | Platform | Keep centrally |
| `platform_audit_logs` | Platform | Platform | Keep centrally; clarify cross-tenant administrative metadata |
| `school_admin_invitations` | Platform | Platform | Keep centrally; tenant activation will provision the tenant first |
| `academic_years`, `terms`, `classes`, `streams`, `subjects`, `departments`, `cbc_levels`, `class_subjects` | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| `students`, `guardians`, `student_guardians`, student medical/discipline/achievement/document/history tables | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| `teachers`, `teacher_subjects`, `teacher_class_assignments` | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| `attendance` | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| Finance tables | Shared DB with `school_id` and tenant-linked records | Tenant | Move schema into tenant migration set |
| Examination/assessment/grading/report-card tables | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| Timetable tables | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| Communication tables | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| Parent/student portal tables | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| School settings/numbering | Shared DB with `school_id` | Tenant | Move schema into tenant migration set |
| `users`, `roles`, `permissions`, `user_roles`, password reset records | Shared DB with tenant columns | Tenant | Move to each tenant database |

The exact table list must be finalized from every migration before production conversion; no table should be moved solely by module name.

## Critical findings

### 1. Database connection is globally shared

`Database` is a singleton and derives its database name directly from `DB_DATABASE`. There is no platform/tenant connection abstraction. This is the principal architectural change required.

### 2. TenantResolver currently resolves against the shared application database

`TenantResolver` uses the application `Database` to query `schools`. That is compatible with the desired platform database only after the database abstraction is split. The existing hostname logic also has a session fallback to `resolved_school_id`; this fallback must not be allowed to override a hostname-derived tenant for normal school requests.

### 3. Tenant state is static/global

`Tenant` uses static process state. This is acceptable only if PHP request isolation is guaranteed, but it makes connection ownership and accidental tenant switching harder to reason about. The redesign should introduce an explicit request-scoped tenant context and ensure the tenant connection is derived from that context exactly once.

### 4. BaseRepository is designed around row-level isolation

`BaseRepository` automatically injects `school_id` into CRUD operations. That has protected the current shared database from many accidental cross-school queries, but after database-per-school it becomes secondary defense rather than the primary isolation mechanism. Existing repositories and direct SQL repositories must be audited before the new connection manager is enabled globally.

### 5. Existing onboarding does not provision a database

`SchoolService::create()` currently creates a school row and subscription in the shared database and marks the school `pending`. `SchoolOnboardingService` creates a platform invitation. `SchoolAdminActivationService` creates the school user in the shared `users` table and activates the school. There is currently no tenant database creation, tenant migration, tenant seeding or provisioning state machine.

### 6. Platform and school identities currently share database infrastructure

The code already distinguishes platform authentication using `platform_user_id` and platform-host middleware, which is a useful foundation. However, school users and operational records remain in the shared database. The redesign must make platform authentication independent from tenant authentication at the database layer as well.

### 7. Existing migration runner is single-database only

`database/migrate.php` loads one `DB_DATABASE`, discovers one migration directory and records one `schema_migrations` table. It cannot currently target a platform database separately from a tenant database or report per-tenant migration state.

### 8. Production migration cannot be safely finalized from the repository alone

The repository exposes migration definitions but does not expose the live production database contents, row counts, applied migration registry, backups or existing tenant records. Therefore the final data-copy/cutover plan must not be executed until a production database inventory and backup/checkpoint procedure is available.

## Security findings relevant to the redesign

- Hostname-based tenant discovery is already present and must remain platform-database driven.
- The session `resolved_school_id` fallback is dangerous if treated as authoritative after a host changes; the new resolver must bind the session to the tenant identity and reject mismatches.
- Tenant authentication currently stores `school_id` in session, but authentication lookups are still against the shared `users` table. Tenant database context must become mandatory for user lookup and authorization.
- Platform authentication already has separate middleware and approved-host enforcement; preserve this boundary.
- Database identifiers cannot be parameter-bound in PDO, so tenant database names must come only from trusted, validated platform metadata and never directly from request input.
- Existing password reset and admin invitation designs hash one-time tokens, which should be preserved when moving tenant records.
- Central error handling already produces reference IDs and maps PDO failures to HTTP 503, which is a good foundation for tenant connection failures.

## Required redesign boundaries

1. `PlatformDatabase` — only platform DB connection and platform repositories/services.
2. `TenantDatabaseManager` — resolves a trusted tenant database record and returns a request-scoped tenant connection.
3. `TenantContext` — immutable request-scoped tenant identity, separate from database connection state.
4. `TenantResolver` — hostname -> platform database -> school/domain -> tenant context.
5. `TenantMigrationManager` — migration state per tenant database.
6. `ProvisioningService` — idempotent school database creation, migration, seeding, admin setup and activation.
7. `TenantDatabaseNameGenerator` — safe immutable database identifiers, collision checked against the platform registry and server metadata.
8. Platform-only repositories/services must depend explicitly on `PlatformDatabase`.
9. Tenant repositories/services must depend explicitly on `TenantDatabaseInterface`.

## Safe migration strategy

The migration must be staged rather than performed as an in-place destructive rewrite.

### Stage 1 — Freeze the architecture contract

Add the platform/tenant database abstractions without changing production data access yet.

### Stage 2 — Inventory production

Record:

- active schools
- school codes/domains
- current schema version
- row counts by tenant for every operational table
- orphaned tenant rows
- duplicate school codes/domains
- current user/role mappings
- storage/upload references
- existing migration history

### Stage 3 — Provision empty tenant databases

Create tenant databases for test schools first. Run only tenant migrations and deterministic seeders.

### Stage 4 — Dual verification

For a test school, compare representative reads between the legacy shared database and the new tenant database. No production cutover occurs until counts and critical records match.

### Stage 5 — Tenant-by-tenant cutover

Move one school at a time. During each cutover, protect against writes during the final copy, verify counts/checksums, switch its platform registry record, and test the school domain.

### Stage 6 — Retirement of shared operational tables

Only after every tenant has been migrated, verified and backed up should shared operational tables be considered for retirement. They must not be dropped as part of the first architecture phase.

## Phase plan

- Phase A — this audit and architecture contract.
- Phase B — platform/tenant database model and connection abstractions.
- Phase C — request-scoped tenant database manager.
- Phase D — platform-backed tenant resolution.
- Phase E — separate platform and tenant migration runners.
- Phase F — idempotent provisioning state machine.
- Phase G — school setup/admin integration.
- Phase H — authentication/authorization isolation.
- Phase I — backup/export/restore and migration tooling.
- Phase J — platform and school UI/UX.
- Phase K — isolation, migration, security and regression testing.

## Phase A exit criteria

- Existing modules preserved.
- Existing application remains untouched by the architecture audit.
- Target database boundaries documented.
- Production database is not modified.
- No destructive migration is proposed without production inventory.
- A dedicated implementation branch is created from the latest `main`.

## Decision

Proceed incrementally from the current shared-database architecture. Do not replace the application or rewrite working modules. The first implementation phase after this audit should introduce explicit platform and tenant database abstractions while retaining the existing database path as a compatibility mode until tenant provisioning and migration verification are complete.
