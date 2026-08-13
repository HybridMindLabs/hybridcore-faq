# Changelog

All notable changes to the HybridCore FAQ extension are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Public page redesign: animated hero, category sidebar on desktop (was a single long list), mobile keeps a horizontal category strip.
- Slug-based permalinks — shareable links read `#faq-how-do-i-reset-my-password` instead of a numeric id. Always derived from the question, editable in admin. Old numeric-id links still resolve.
- Admin list grouped by category with a filter box, instead of one flat list.
- Search term highlighting in questions, category icons, related-questions under each answer, and a closing "Still have a question?" card that reuses the site's configured social links.
- Admin bulk actions: publish/unpublish/delete across a selection.
- Zero-result search tracking — terms visitors searched that matched nothing surface in a dismissible admin panel.
- Optional free-text reason on a "Not helpful" vote, visible to admins per entry.

### Fixed
- Mobile category nav sat behind the sticky site header instead of below it.

### Performance
- Answers render from markdown once, on save, instead of on every public-page visit.
- Added an index on `faq_entries(is_published, category, sort)`, matching the public page's query shape.

## [1.0.0] - 2026-08-12

### Added
- Public FAQ page: grouped by category, expand/collapse, client-side search.
- Admin CRUD for entries (question, markdown answer, category, sort order).
- "Was this helpful?" yes/no feedback per entry, admin-visible only.
- English and Bulgarian translations.
