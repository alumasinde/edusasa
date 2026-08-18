# Phase 13 — Reporting

Phase 13 adds a tenant-scoped read-only reporting layer over existing EduSasa data.

## Reports
- School overview: enrollment, status and class distribution.
- Attendance: date-range totals and class-level attendance rates.
- Academic: approved/published examination result averages and grade distribution.

## Design
- Reports query existing canonical tables; no duplicate reporting database is introduced.
- School context is taken from `Tenant::id()`.
- Dates are validated as ISO calendar dates before querying.
- Existing permission middleware protects report routes.
- Finance remains owned by the Finance module; Phase 13 provides the reporting foundation for future cross-module dashboards.

## Routes
- `/reports`
- `/reports/attendance`
- `/reports/academic`
