# Phase 19 — UI and Asset Foundation

Phase 19 establishes the shared frontend foundation for EduSasa without requiring every existing PHP view to be rewritten at once.

## Assets

- `public_html/assets/css/app.css` — shared responsive design tokens, forms, tables, cards, navigation, alerts, modals and utility classes.
- `public_html/assets/js/app.js` — shared menu, form-loading, confirmation, flash, and modal behaviours.
- `public_html/assets/images/logo.svg` — EduSasa logo.
- `public_html/assets/images/favicon.svg` — EduSasa favicon.

## Integration

`App\\Core\\ViewRenderer` injects the shared stylesheet, favicon and JavaScript into rendered HTML views. Existing module views therefore receive the common assets without changing every view individually.

Asset URLs are versioned with `?v=19` so browser caches refresh when Phase 19 changes.

## Deployment

Pull `main` and run `composer dump-autoload -o` if Composer files changed. No database migration is required for Phase 19.
