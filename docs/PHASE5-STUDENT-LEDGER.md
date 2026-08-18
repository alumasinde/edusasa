# Phase 5 — Student Account & Billing Ledger

The finance module now exposes a tenant-scoped student ledger and statement.

## Ledger

`/finance/students/{id}/ledger`

Shows invoice line items, payments, receipts, allocations and current outstanding totals. Optional `from` and `to` filters use `YYYY-MM-DD`.

## Statement

`/finance/students/{id}/statement`

Builds a chronological debit/credit statement and running balance from the student's invoices and confirmed payments.

## Security

Both endpoints require the existing finance view permission and resolve the student using the current tenant, preventing cross-school student access by ID.
