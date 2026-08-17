# Security

## Secrets

- Never commit `.env` or production credentials.
- Keep secrets in environment variables or the deployment secret store.
- Rotate credentials that have previously appeared in source archives or commits.

## Tenant isolation

Every tenant-scoped repository/service operation must enforce the current school/tenant context. Platform-level operations must be explicit.

## Uploads

Uploaded files are validated by extension and detected MIME type. File names are randomized before storage.

## Headers

Baseline response security headers are applied centrally. Deploy behind HTTPS and review CSP directives whenever adding external assets.

## Reporting

Security issues should be reported privately to the maintainers rather than disclosed in public issues.
