# Platform Entitlements

The Platform Admin controls package entitlements through database data rather than application conditionals.

## Model

School -> active subscription -> plan -> plan_features -> feature/module access -> optional school_feature_overrides.

A school override may enable or disable a feature, replace limits, carry a reason, and expire automatically.

## Admin surfaces

- `/platform/entitlements` — catalog of plans and features.
- `/platform/plans/{id}` — edit a plan and configure feature limits.
- `/platform/schools/{id}/entitlements` — manage school-specific overrides.

## Rules

- Package names are not hard-coded in entitlement checks.
- Features are identified by database codes.
- Limits are JSON so different features can define different constraints.
- Changes are recorded in `platform_audit_logs`.
- Tenant runtime access is resolved by `SchoolEntitlementService` and exposed to the tenant context.

## Phase 4 remaining

1. Complete school administrator invitation acceptance/password setup against the existing `users`/roles system.
2. Add platform role/permission management and a real platform-user login lifecycle.
3. Add subscription lifecycle management (trial, renewal, suspension, cancellation) and billing integration.
4. Add platform audit-log viewer and operational/support tooling.
5. Finish application boot/integration verification and automated tests.
6. Then move to Finance & Payments.
