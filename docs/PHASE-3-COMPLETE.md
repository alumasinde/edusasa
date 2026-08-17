# EduSasa Phase 3 — School Operations

Status: **COMPLETE (domain foundation and hardening scope)**

Phase 3 establishes the school-operations domain boundary used by EduSasa's later engines.

## Included

- Academic structure integration boundary: academic years, terms, classes, streams and subjects.
- Student domain: admissions/profile lifecycle and extended student records.
- Guardian, medical, discipline, achievement and document boundaries.
- Teacher/staff lifecycle.
- Teacher-subject/class/stream assignment rules.
- Teacher import validation with atomic commit semantics.
- Teacher workload matrix for downstream scheduling.
- Attendance domain boundary and reporting inputs.
- Tenant isolation, permission checks, CSRF protection and audit requirements documented as phase exit criteria.

## Engineering decisions

1. Domain services remain separate from the future timetable/automation engines.
2. Scheduling consumes teacher workload/assignment data; it does not own teacher CRUD logic.
3. Imports validate the complete dataset before committing.
4. Tenant context is required for school-owned records.
5. The existing business behavior is preserved where it is already correct; UX simplification is a separate concern.

## Phase 3 exit criteria

- [x] Academic → student relationships defined.
- [x] Student → class/stream relationships defined.
- [x] Teacher → subject/class/stream assignments defined.
- [x] Teacher workload exposed as scheduling input.
- [x] Attendance is tied to school/student context.
- [x] Import and assignment hardening rules documented and implemented for the transferred services.
- [x] Audit and tenant-boundary requirements documented.

## Important scope note

This completion marker refers to the **Phase 3 domain foundation transferred into the current EduSasa repository**. It is not a claim that every file from the original SSMS archive has been copied into GitHub. The original archive must be available to the working environment to perform a complete file-for-file migration.

## Next phase

Phase 4: **Finance & Payments** — fees, invoices, payment allocation, M-Pesa integration, reconciliation, receipts and financial reporting.
