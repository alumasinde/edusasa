# EduSasa Phase 5 Migration Audit

## Status

Phase 5 migrations are now mapped to the features implemented in the Finance module. Historical migrations are retained; no already-applied migration is renamed or deleted.

## Ordered migration set

1. `20260817_platform_saas.sql` — platform SaaS foundation.
2. `20260817_platform_onboarding.sql` — school-admin onboarding.
3. `20260817_platform_rbac.sql` — platform roles and permissions.
4. `20260818_phase5_finance_foundation.sql` — fee categories, structures, student fee accounts, invoices, invoice items, payments and allocations.
5. `20260818_phase5_fee_billing_engine.sql` — fee targeting and billing batches.
6. `20260818_phase5_payment_reconciliation.sql` — reconciliation records.
7. `20260818_phase5_payment_channels_and_schema_alignment.sql` — school-configurable payment channels and compatibility alignment.
8. `20260818_phase5_payment_provider_engine.sql` — provider transactions/events for M-Pesa and other providers.
9. `20260818_phase5_receipts_and_payment_notifications.sql` — receipts and payment notification delivery records.
10. `20260818_phase5_adjustments_and_refunds.sql` — discounts, waivers, credits and refunds.
11. `20260818_phase5_finance_controls.sql` — financial periods and controlled reversals/voids.
12. `20260818_phase5_finance_integrity.sql` — integrity scan metadata and integrity permission.
13. `20260818_phase5_migration_alignment.sql` — final Phase 5 permission backfill/alignment for installations that applied earlier migrations partially.

## Features without new tables

Finance reports, student statements/ledger views, parent payment pages and provider checkout pages reuse the Phase 5 tables above and therefore do not require duplicate feature-specific migrations.

## Audit findings

- Migration names are now descriptive and phase-scoped.
- Legacy `029_add_enterprise_plan.sql` is retained for compatibility.
- Payment channels are school-scoped; provider credentials/configuration are not global.
- Provider events and payment allocations have separate persistence from the invoice ledger.
- Refunds and finance controls have dedicated audit tables.
- Integrity scanning is read-only; it reports inconsistencies rather than silently mutating financial records.
- The alignment migration backfills all Phase 5 finance permissions without deleting existing role assignments.
- A real production dry-run still requires a MySQL database containing the base schema and representative Phase 5 data; GitHub source inspection cannot execute SQL against that database.

## Production rule

Run migrations in filename order using a migration runner that records each applied filename. Never rely on manually skipping a migration because a table already exists. For existing installations, take a database backup before applying the Phase 5 alignment migration.
