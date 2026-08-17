# Phase 3 Integration & Hardening Checklist

## Tenant boundaries
- Academic, student, staff, teacher assignments and attendance must always resolve through the current school tenant.
- Cross-tenant IDs must never be accepted as authorization.

## Academic relationships
- Student class/stream references must belong to the current school.
- Teacher subject assignments must reference valid school subjects/classes/streams.
- Attendance must reference students enrolled in the selected school/class/stream.

## Authorization
- Read/write operations require explicit module permissions.
- Bulk imports require a dedicated import permission.
- Sensitive student and staff records must not be exposed to users outside their role scope.

## Data integrity
- Imports validate before commit.
- Multi-row writes use transactions.
- Duplicate assignments are rejected.
- Status transitions must clean up dependent operational assignments where required.

## Audit
- Create/update/delete operations on students, staff, assignments and attendance are auditable.
- Audit payloads must not contain passwords, reset tokens or other secrets.

## Scheduling readiness
Teacher workload is exposed separately from teacher management so the timetable engine can consume it without coupling scheduling rules to CRUD code.

## Phase 3 exit criteria
1. Academic, Students, Teachers and Attendance use the same tenant/security boundary.
2. Existing business workflows are preserved where they are correct.
3. Imports cannot partially commit invalid data.
4. Teacher assignments provide clean scheduling inputs.
5. Remaining UI simplification is handled as a separate UX pass rather than mixing presentation changes into domain hardening.
