---
status: completed
title: PublicController Rewrite and Asset Serving
type: backend
complexity: medium
dependencies:
  - task_01
---

# Task 03: PublicController Rewrite and Asset Serving

## Overview

Rewrite `PublicController::show()` to read `index.html` from the page's filesystem directory (`writable/landing_pages/{slug}/`) instead of decoding JSON blocks. Inject a hidden `landing_page_id` field into any `<form id="lead-form">` for lead capture linkage. Add a new `asset()` method to serve uploaded static files (CSS, JS, images) at `/p/{slug}/assets/{path}`. Simplify the public view to output the HTML directly. Register the new asset route.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST rewrite `PublicController::show($slug)` to read `$page['file_path']` and serve `index.html` from `writable/{file_path}/index.html`
- MUST inject `<input type="hidden" name="landing_page_id" value="{id}">` immediately after `<form id="lead-form"` attribute in the HTML
- MUST handle missing `index.html` gracefully (log error, return 404)
- MUST handle missing `<form id="lead-form">` gracefully (log warning, serve page as-is)
- MUST add `PublicController::asset($slug, $filepath)` to serve static files
- MUST prevent path traversal in `asset()` by resolving `realpath()` and verifying it stays within the page's directory
- MUST set appropriate `Content-Type` and `Cache-Control: public, max-age=31536000` headers on asset responses
- MUST simplify `app/Views/public/landing_page.php` to output `$htmlContent` directly instead of `$heroHtml`, `$sectionsHtml`, `$customCss`
- MUST register route `GET /p/(:any)/assets/(:any)` → `PublicController::asset/$1/$2` in `Routes.php`
- MUST remove `use App\Models\BlockTemplateModel;` import from PublicController (no longer needed)
- MUST update existing tests in `tests/PublicAndLeadsTest.php` and `tests/unit/PublicControllerTest.php`
</requirements>

## Subtasks

- [x] 3.1 Rewrite `PublicController::show()` to read from filesystem and inject hidden field
- [x] 3.2 Add `PublicController::asset()` with path traversal protection
- [x] 3.3 Rewrite `app/Views/public/landing_page.php` to output `$htmlContent` directly
- [x] 3.4 Add asset route to `Routes.php`
- [x] 3.5 Update existing PublicController and public page tests

## Implementation Details

See TechSpec "Core Interfaces — PublicController::asset()" and "API Endpoints — Public Routes" sections. The `asset()` method must use `realpath()` on both the requested path and the base directory to prevent directory traversal. The `show()` method should use `file_get_contents()` to read `index.html` and `str_replace()` to inject the hidden field after `<form id="lead-form"`.

### Relevant Files
- `app/Controllers/PublicController.php` — rewrite `show()`, add `asset()`, remove `BlockTemplateModel` import and `renderHero()` method
- `app/Views/public/landing_page.php` — simplify to output `<?= $htmlContent ?>` inside a minimal HTML shell
- `app/Config/Routes.php` — add `$routes->get('p/(:any)/assets/(:any)', 'PublicController::asset/$1/$2');`

### Dependent Files
- `app/Controllers/LandingPagesController.php` (task 05) — stores files that `PublicController` serves
- `tests/PublicAndLeadsTest.php` — update tests for new rendering approach
- `tests/unit/PublicControllerTest.php` — rewrite to test filesystem-based serving

### Related ADRs
- [ADR-006: Static File Serving via PHP Controller Endpoint](../adrs/adr-006.md) — Rationale for PHP endpoint over symlinks or embedding

## Deliverables

- Updated controller: `app/Controllers/PublicController.php`
- Updated view: `app/Views/public/landing_page.php`
- Updated routes: `app/Config/Routes.php`
- Updated tests
- Unit tests with 80%+ coverage **(REQUIRED)**
- Integration tests for asset serving and path traversal **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `show()` with valid page returns HTML containing injected `landing_page_id` hidden field
  - [ ] `show()` with missing `index.html` file returns 404
  - [ ] `show()` with no `<form id="lead-form">` in HTML serves page without injection
  - [ ] `asset()` with valid file returns 200 with correct `Content-Type`
  - [ ] `asset()` prevents path traversal (`/p/slug/assets/../../etc/passwd` returns 404)
  - [ ] `asset()` with non-existent file returns 404
  - [ ] `asset()` with file outside page directory returns 404
  - [ ] `asset()` sets `Cache-Control: public, max-age=31536000` header
- Integration tests:
  - [ ] Upload page → visit `/p/{slug}` → verify HTML renders with hidden field
  - [ ] Upload page with images → visit `/p/{slug}/assets/images/hero.jpg` → verify image is served
  - [ ] Submit lead form on public page → verify `landing_page_id` is correctly stored
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- Public pages render uploaded HTML with automatic lead capture linkage
- Static assets (CSS, JS, images) are served with correct MIME types and caching headers
- Path traversal attacks are blocked
- Existing lead capture endpoint continues to work with the hidden `landing_page_id` field
