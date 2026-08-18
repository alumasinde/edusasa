# Phase 17 — School Dashboard & Cross-Module Integration

Phase 17 turns the dashboard into the operational entry point for a school tenant.

## Delivered

- Tenant-scoped school dashboard at `/` and `/dashboard`.
- Active student, teacher and class counts.
- Attendance marked today and attendance status breakdown.
- Outstanding fee balance from Finance invoices.
- Published examination count.
- Published timetable count.
- Unread Communication count for the authenticated user.
- Recent published announcements.
- Direct navigation to Students, Teachers, Attendance, Finance, Exams, Timetable and Reports.
- Authentication and tenant middleware on dashboard routes.
- Existing `Dashboard` module registration retained in `app/Config/app.php`.

## Integration boundary

The dashboard reads canonical data owned by the existing modules rather than maintaining duplicate dashboard tables or counters. This keeps the dashboard consistent with Students, Teachers, Attendance, Finance, Exams, Timetable and Communication.

## Test flow

1. Sign in as a school user.
2. Open `/dashboard` and `/`.
3. Confirm counts match the corresponding module records.
4. Mark attendance and confirm the daily attendance figures change.
5. Create/publish a finance invoice and confirm outstanding fees change.
6. Publish an examination and timetable and confirm their counts change.
7. Publish a Communication message for the signed-in user and confirm unread count/announcement list changes.
8. Follow each dashboard shortcut and confirm it opens the corresponding module.
9. Sign out and confirm the dashboard redirects to login.
10. Confirm a user cannot see another school's dashboard by changing tenant context.
