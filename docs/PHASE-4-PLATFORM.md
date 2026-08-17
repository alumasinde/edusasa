# Phase 4 — Platform & Architecture

## Purpose
EduSasa is a multi-tenant SaaS product. Platform operations are separate from school operations.

## Platform boundary
Platform administrators operate on the platform host and manage schools, plans, subscriptions and feature entitlements. School users operate inside a school tenant. Platform access does not automatically grant a school role.

## Dynamic commercial model
Plans are records in `plans`. Features are records in `features`. `plan_features` defines the catalog. `school_feature_overrides` provides controlled per-school exceptions. No controller should contain Starter/Standard/Premium/Enterprise business rules as hard-coded conditionals.

## School lifecycle
`pending -> active -> suspended -> archived`

A school is created with a subscription to a selected active plan. Activation and suspension are explicit platform operations.

## Subscription model
Subscriptions belong to a school and reference a plan. Trial, active, past-due, suspended, cancelled and expired states are supported. Renewal/expiry dates are data, not code constants.

## Feature resolution
School feature access is resolved dynamically:

`school -> active subscription -> plan -> feature`

A current school override can enable/disable a feature or attach JSON limits. Overrides may expire.

## Security
- Platform routes require an approved platform host.
- Platform routes require a platform session.
- School tenant authentication remains separate.
- Platform operations should be audited without recording secrets.
- Support impersonation, when added, must be explicit, time-limited and audited.

## Current API foundation
- `GET /api/platform/schools`
- `POST /api/platform/schools`
- `PATCH /api/platform/schools/{id}/status`
- `GET /api/platform/schools/{id}/features/{feature}`
- `GET /api/platform/plans`
- `GET /api/platform/features`
- `PUT /api/platform/plans/{plan}/features/{feature}`

## Next integration work
The existing application bootstrap must load migrations safely and the platform UI should consume these APIs rather than duplicate business rules. Billing, school administrator provisioning, subscription payment integration and operational dashboards can build on this foundation.
