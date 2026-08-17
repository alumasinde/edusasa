# Phase 4 — Subscription Lifecycle

EduSasa subscriptions are data-driven. A school has a subscription linked to a database plan; package access is resolved by the entitlement layer rather than by hard-coded plan names.

## Platform operations

- List/search subscriptions
- Filter by lifecycle status
- Change a school's active plan
- Change subscription status
- Record subscription lifecycle audit events
- Preserve previous subscription history when changing plans

## Lifecycle statuses

`trial`, `active`, `past_due`, `suspended`, `cancelled`, `expired`

## Plan changes

Changing a plan closes the currently active/trial/past-due subscription and creates a new active subscription. The old record remains available for history and audit.

## Limits and features

Plans remain the source of baseline feature access. School-specific overrides and limits are resolved separately by the entitlement service.

## Remaining Phase 4 work

- Complete school-admin invitation acceptance against the verified school-user authentication schema.
- Platform-user role/permission management.
- Platform audit log viewer.
- Subscription billing/provider integration and payment reconciliation.
- End-to-end production verification of platform routes, tenant isolation and entitlement enforcement.
