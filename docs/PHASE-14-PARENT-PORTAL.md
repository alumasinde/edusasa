# Phase 14 — Parent Portal

The parent portal gives a guardian account a secure, tenant-scoped view of only the students linked to that guardian.

## Delivered

- Guardian account ownership via `guardians.user_id`.
- Parent dashboard with linked children and high-level attendance, fees and latest published result data.
- Child detail page with published report cards, fee balances, payments, attendance history and published timetable.
- Notification inbox backed by Phase 12 communication recipients.
- Read/unread notification state.
- Parent contact profile updates for phone and address.
- CSRF protection for writes.
- Permission checks for portal, notifications and profile actions.
- Cross-school/unauthorized child access blocked by guardian-to-student relationship checks.
- Migration `024_parent_portal_phase14.sql` completes the guardian linkage fields expected by the existing Students module.

## Security boundary

The portal never accepts a student ID as proof of ownership. Every child request first resolves the authenticated user's guardian record in the current tenant and then checks `student_guardians` for the requested student.

Finance and examination data are read-only and limited to the linked student. Only published report cards/results and non-draft invoices are exposed.

## Test flow

1. Create/link a guardian to a student and create the guardian portal account.
2. Sign in as the guardian.
3. Open `/parent-portal`.
4. Confirm only linked students appear.
5. Open a child and verify attendance, fees, report cards and timetable.
6. Publish a Communication message to the guardian and confirm it appears in Notifications.
7. Mark it read.
8. Update phone/address from Profile.
9. Try another student's URL and confirm access is denied.
