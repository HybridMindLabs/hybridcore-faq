# Changelog

All notable changes to the HybridCore FAQ extension are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Public page redesign: animated hero, category sidebar on desktop (was a single long list), mobile keeps a horizontal category strip.
- Slug-based permalinks — shareable links read `#faq-how-do-i-reset-my-password` instead of a numeric id. Always derived from the question, editable in admin. Old numeric-id links still resolve.
- Admin list grouped by category with a filter box, instead of one flat list.

### Fixed
- Mobile category nav sat behind the sticky site header instead of below it.

## [1.0.0] - 2026-08-12

### Added
- Public FAQ page: grouped by category, expand/collapse, client-side search.
- Admin CRUD for entries (question, markdown answer, category, sort order).
- "Was this helpful?" yes/no feedback per entry, admin-visible only.
- English and Bulgarian translations.
