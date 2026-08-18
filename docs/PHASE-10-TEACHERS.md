# Phase 10 — Teachers

Phase 10 activates the canonical `teachers` domain already defined by the current EduSasa database schema.

## Delivered

- Tenant-scoped teacher listing, search and pagination
- Teacher create/edit/view/status/delete workflows
- Teacher CSV export
- Department association
- Subject assignments with optional class scope and periods/week
- General teacher capability matrix (`Who Teaches What`)
- Audit events for teacher and assignment changes
- Teacher permissions and module entitlement enforcement
- Assignment data prepared for Timetable workload consumption

## Important schema alignment

The current repository's canonical schema uses `teachers.teacher_id` relationships, while older service code referenced a legacy `staff` model. Phase 10 keeps the current `teachers` table authoritative and aligns the active assignment repository/services with it.
