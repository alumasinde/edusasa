# Phase 12 — Communication

Communication is a tenant-scoped module for school announcements, notices, messages and an authenticated notification inbox.

## Capabilities

- Draft, publish and archive communications.
- Audience targeting: everyone, role, class, stream or individual user.
- Recipient snapshots are created at publish time so later audience changes do not rewrite history.
- Read/unread tracking per recipient.
- Communication history and notification inbox.
- CSRF, tenant, module-entitlement and permission middleware on web routes.

## Data

`022_communication_phase12.sql` creates `communications`, `communication_recipients` and `communication_templates`.

The module uses the existing `communication.core` feature entitlement. The next portal phases can consume the recipient inbox without duplicating communication data.

External SMS/email delivery is intentionally not coupled to publication in this phase; the persisted recipient model is the integration point for later notification channels.
