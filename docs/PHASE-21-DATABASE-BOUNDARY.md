# Phase 21 — Platform/Tenant Database Boundary

Phase 21 establishes the target database boundary without migrating or deleting the existing shared schema.

## Principles

- One shared application codebase.
- One platform database for SaaS metadata.
- One independently provisioned database per school.
- Platform database is the only source of truth for tenant discovery.
- Tenant database identifiers come only from trusted platform metadata.
- Tenant database credentials are represented by secret references, not raw passwords in the platform schema.
- A tenant connection is request-scoped and cannot be rebound to another tenant during the request.
- The existing shared database remains available during the transition; no production data is changed by this phase.

## New boundaries

`PlatformDatabase` owns the central connection.

`TenantDatabase` owns a connection for exactly one immutable `TenantContext`.

`TenantDatabaseManager` reads the trusted `school_databases` registry from the platform database, validates the registered database identifier, verifies the registry matches the resolved tenant, and creates the tenant connection once per request.

`TenantDatabaseNameGenerator` turns a trusted tenant identifier into a MySQL-safe database name. It never accepts a database name from HTTP input.

## Registry contract

`school_databases` contains:

- school_id
- immutable tenant_identifier
- database_name
- database host/port
- username
- secret reference
- lifecycle status
- schema version

The database name is unique and immutable at the application level. Changing a school code must not rename an existing database.

## Important implementation boundary

Phase 21 registers the new abstractions in the application container but does not replace the existing `Database` singleton in authentication or repositories yet. That deliberate compatibility boundary prevents a partially implemented connection split from breaking working modules.

The next phase will move tenant resolution to `PlatformDatabase`, after which tenant-facing repositories can be migrated module by module to `TenantDatabaseManager`.

## Migration namespace

The repository now has explicit namespaces:

```text
database/
    platform/
        migrations/
    tenant/
        migrations/
```

The existing `database/migrations/` directory is intentionally retained during the transition. It must not be deleted or reinterpreted until the new migration runner and tenant data migration strategy are complete.
