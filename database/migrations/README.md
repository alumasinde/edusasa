# EduSasa database migrations

The migration set is now canonical and ordered by dependency. Use the numbered SQL files in lexicographic order.

```text
000_schema_migrations.sql
001_schools.sql
002_platform_access.sql
003_academic.sql
004_students.sql
005_teachers.sql
006_attendance.sql
007_finance.sql
008_finance_billing.sql
009_finance_payments.sql
010_finance_controls.sql
011_finance_integrity.sql
```

## Scope

These migrations cover the modules that currently exist in `modules/`: Platform, Academic, Students, Teachers, Attendance and Finance. They include the tables used by the current codebase, including the complete Phase 5 finance engine.

The old date-based Phase 5 migration files have been consolidated into this numbered set. The new files are additive and use `IF NOT EXISTS` where appropriate so an existing database can be brought into alignment without dropping application data.

## Existing installations

Take a database backup first. Apply the numbered migrations in order. Existing tables are preserved. If an older installation already has a table, its data is not deleted by these migrations.

## New installations

Run every `.sql` file in numerical order. `000_schema_migrations.sql` creates the registry table; the application/deployment runner should record each filename after successful execution.

## Naming convention

Use:

`NNN_<domain>_<purpose>.sql`

Examples: `001_schools.sql`, `003_academic.sql`, `009_finance_payments.sql`.

Do not return to date-based migration names for new schema changes.
