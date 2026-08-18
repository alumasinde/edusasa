# Phase 15 — Student Portal

Phase 15 adds a secure, tenant-scoped portal for students with linked user accounts.

## Delivered

- Student account ownership through `students.user_id`.
- Student dashboard with class/stream, attendance, fee balance, latest results and notifications.
- Academic page with published report cards and published examination results.
- Published class/stream timetable.
- Attendance summary and recent history.
- Read-only invoices and payment history.
- Notification inbox backed by Phase 12 communication recipients.
- Read/unread notification state.
- Student profile updates for phone, email and address only.
- CSRF protection for writes.
- Tenant and permission checks on every portal route.
- Cross-student access blocked because every request resolves the authenticated student's record first.
- No draft finance or unpublished examination data is exposed.

## Security boundary

The portal never trusts a student ID supplied by the browser. The authenticated user is resolved to exactly one active student in the current school tenant through `students.user_id`. All subsequent reads and writes use that student ID and school ID.

Academic and finance data are read-only. Identity, admission, class, stream and other school-controlled fields cannot be edited from the portal.

## Test flow

1. Link a student record to a user account through `students.user_id`.
2. Sign in as that student.
3. Open `/student-portal`.
4. Verify dashboard, academics, timetable, attendance and fees.
5. Publish a Communication message to the student and confirm it appears in Notifications.
6. Mark the notification as read.
7. Update phone/email/address from Profile.
8. Try another student's URL or alter request parameters and confirm no other student data is exposed.
