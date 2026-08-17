# EduSasa

Enterprise-ready, multi-tenant school management platform built from the existing SSMS codebase.

## Product direction

EduSasa is designed to be powerful without being difficult to use. School staff see role-focused workflows while administrators retain enterprise controls.

### Commercial packages

- **Starter** — essential school operations
- **Standard** — finance, exams, attendance, reports and communication
- **Premium** — portals and advanced operations
- **Enterprise** — advanced automation, governance, integrations and multi-campus capabilities

Package entitlements are data-driven through plans and module mappings rather than hard-coded into controllers.

## Engines

- Constraint-aware timetable generation
- Attendance and absence analytics
- Examination and grading workflows
- Fees and payment reconciliation
- Notification workflows
- Reporting/export engine
- Audit/compliance engine
- Subscription and entitlement engine

## Architecture

The existing modular PHP architecture under `app/` and `modules/` is retained and strengthened incrementally. The goal is not a framework rewrite.

## Security

Never commit `.env`, production credentials, school/student data, generated uploads, logs or caches. Rotate any credentials that have previously been exposed.

## Modernization status

The first modernization pass hardens uploads, cache deserialization, client IP handling and response security headers, adds the Enterprise plan migration, and removes timetable behavior that invented extra lessons beyond curriculum targets.
