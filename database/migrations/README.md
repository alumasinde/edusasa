# EduSasa database migrations

The migration set is canonical and ordered by dependency. Use the numbered SQL files in lexicographic order.

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
020_teachers_phase10.sql
021_timetable_phase11.sql
```

## Scope

These migrations cover the platform, academic, students, teachers, attendance, finance and timetable modules. Phase 11 adds configurable teaching periods, timetable headers, schedule entries, timetable permissions and safe default periods for each school.

## Existing installations

Take a database backup first. Apply the numbered migrations in order. Existing tables and data are preserved. Phase 11 is additive and does not delete teacher assignments or academic data.

## Timetable generation

The generator consumes class-specific `teacher_subjects` assignments from Phase 10. It distributes the requested periods across Monday–Friday while preventing teacher and class/stream collisions. Break periods are excluded. Double-period assignments are placed consecutively when capacity allows; any workload that cannot fit is reported instead of silently dropped.

## Naming convention

Use `NNN_<domain>_<purpose>.sql`. Do not return to date-based migration names for new schema changes.
