# EduSasa database migrations

Migrations are additive and must be applied in filename order after the base schema. A production deployment should record every applied filename in its migration runner and must never rely on manually skipping a migration because a table already exists.

## Naming convention

New product migrations use:

`YYYYMMDD_phaseN_<feature>.sql`

Older numeric/legacy migrations are retained for compatibility and must not be renamed or deleted after they have been applied to a production installation.

## Platform foundation

1. `20260817_platform_saas.sql` — platform users, schools, plans, features, subscriptions and overrides.
2. `20260817_platform_onboarding.sql` — school-admin invitations and activation data.
3. `20260817_platform_rbac.sql` — platform roles, permissions and role assignments.

## Phase 5 — Finance

4. `20260818_phase5_finance_foundation.sql` — fee categories, fee structures, student fee accounts, invoices, invoice items, payments and allocations.
5. `20260818_phase5_fee_billing_engine.sql` — fee targeting and bulk billing batches.
6. `20260818_phase5_payment_reconciliation.sql` — daily payment reconciliation records.
7. `20260818_phase5_payment_channels_and_schema_alignment.sql` — school-configurable payment channels and compatibility alignment for fee-billing columns/tables.
8. `20260818_phase5_payment_provider_engine.sql` — provider transactions and provider callback/event storage for M-Pesa and other online providers.
9. `20260818_phase5_receipts_and_payment_notifications.sql` — official receipts and notification delivery records.
10. `20260818_phase5_adjustments_and_refunds.sql` — discounts, waivers, credits and refund workflow data.
11. `20260818_phase5_finance_controls.sql` — financial periods, controlled reversals, invoice voids and control audit records.
12. `20260818_phase5_finance_integrity.sql` — integrity-scan metadata and finance-integrity permission.
13. `20260818_phase5_migration_alignment.sql` — final Phase 5 permission backfill/alignment for installations that applied feature migrations partially or out of order.

## Legacy migrations

`029_add_enterprise_plan.sql` is retained because it belongs to the existing migration history. Do not rename or remove it in production.

## Feature-to-migration rule

Some Phase 5 features intentionally do not have their own migration because they are application layers over existing finance tables. This includes finance reports, student statements/ledger views, parent payment pages and provider checkout pages.

## Safety

All Phase 5 migrations are intended for MySQL 8+. Take a database backup before applying migrations to an existing production school. The final alignment migration is additive and only backfills permissions; it does not delete financial data or remove role assignments.

See `docs/PHASE-5-MIGRATION-AUDIT.md` for the complete feature-to-migration audit and known limitations.