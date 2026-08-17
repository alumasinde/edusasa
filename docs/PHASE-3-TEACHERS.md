# Phase 3 — Teachers

The teacher domain is being brought into EduSasa incrementally from the existing SSMS implementation.

## Enterprise rules

- Every teacher/staff record is tenant-scoped.
- Teacher-subject assignments must be validated against the school academic structure.
- Teacher workload is derived from assignments and timetable requirements rather than hard-coded values.
- Teacher availability is treated as scheduling input.
- Sensitive staff data must be permission protected and audited.
- Import operations must validate rows before committing changes.

## Workflow

Teacher profile → employment/status → subjects/classes → availability → timetable workload → attendance/academic responsibilities.

The next step is integrating the existing teacher controllers, services, repositories, assignment/import flows and validation with the EduSasa Phase 2 core.
