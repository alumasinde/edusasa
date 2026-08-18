# EduSasa database migrations

Migrations are additive and should be applied in filename order after the base schema.

## Naming convention

`YYYYMMDD_phaseN_<feature>.sql` is used for new product work. Older numeric/legacy migrations are retained so existing installations do not lose migration history.

## Current feature sequence

1. `20260817_platform_saas.sql` — platform users, schools, plans, features, subscriptions and overrides.
2. `20260817_platform_onboarding.sql` — school-admin invitation/onboarding data.
3. `20260817_platform_rbac.sql` — platform roles and permissions.
4. `20260818_phase5_finance_foundation.sql` — fee categories, structures, invoices, payments and allocations.
5. `20260818_phase5_fee_billing_engine.sql` — fee-structure targeting and bulk billing batches.
6. `20260818_phase5_payment_reconciliation.sql` — daily reconciliation records.
7. `20260818_phase5_payment_channels_and_schema_alignment.sql` — school payment channels plus idempotent repair for Phase 5 tables/columns when earlier feature migrations were applied out of order.

## Important

Do not delete or rename an already-applied migration in a production installation. The legacy `029_add_enterprise_plan.sql` remains for migration-history compatibility; new migrations use the date/phase naming convention.

The schema-alignment migration is deliberately safe for existing installations: it uses `CREATE TABLE IF NOT EXISTS` and information-schema checks before adding the two fee-structure targeting columns.
