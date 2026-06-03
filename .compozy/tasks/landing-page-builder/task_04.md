---
status: completed
title: Upload and Edit Views
type: frontend
complexity: medium
dependencies:
  - task_02
---

# Task 04: Upload and Edit Views

## Overview

Rewrite `app/Views/landing_pages/create.php` as a file upload form with `enctype="multipart/form-data"` for uploading index.html, style.css, app.js, and images. Rewrite `app/Views/landing_pages/edit.php` to load the uploaded HTML, parse `<!-- BLOCO_N_INICIO/FIM -->` delimiters via the BlockEditorHelper, and display textareas per block for administrators to edit text content. Both views drop all old composer components (CodeMirror, page-builder.js, preview iframe).

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST rewrite `create.php` with `enctype="multipart/form-data"` on the form
- MUST include file inputs: `index_html` (required, .html only), `style_css` (optional, .css), `app_js` (optional, .js), `images[]` (optional, multiple, image types only)
- MUST include title and slug text inputs with auto-slug generation (existing JS pattern from old view)
- MUST NOT include CodeMirror, page-builder.js, preview iframe, hero block fields, CSS editor textarea, or blocks payload textarea
- MUST rewrite `edit.php` to call `parse_block_delimiters()` from BlockEditorHelper on the page's `index.html`
- MUST display each detected block as a labeled textarea labeled "Bloco N" with the block's text content pre-filled
- MUST fall back to a raw HTML textarea for the full file if no block delimiters are detected
- MUST submit block text data as `block_text[]` array in the form POST
- MUST include a hidden `file_unchanged` field indicating no file re-upload (for the edit view)
- MUST NOT include the old "Block Templates" sidebar link or references to old routes

</requirements>

## Subtasks

- [x] 4.1 Rewrite `create.php` as file upload form with all file inputs
- [x] 4.2 Rewrite `edit.php` to load HTML, parse blocks, render textareas
- [x] 4.3 Implement fallback raw HTML editor when no block delimiters exist

## Implementation Details

See PRD "UI Layout — Upload Form" and "UI Layout — Block Editor" sections for the exact form layouts. The edit view must read the HTML file from disk (`writable/{file_path}/index.html`), call `parse_block_delimiters($html)`, then render each block's text in a `<textarea name="block_text[]">`. On save, the POST data is sent to `LandingPagesController::update()` which calls `rewrite_block_content()` (task 05).

### Relevant Files
- `app/Views/landing_pages/create.php` — full rewrite
- `app/Views/landing_pages/edit.php` — full rewrite

### Dependent Files
- `app/Controllers/LandingPagesController.php` (task 05) — receives the form POST from these views
- `app/Helpers/block_editor_helper.php` (task 02) — provides block parsing functions used by edit view
- `public/assets/js/page-builder.js` — will be deleted in task 06 (no longer needed by these views)

### Related ADRs
- [ADR-008: Block Editor HTML Parsing via DOMDocument](../adrs/adr-008.md) — Block parsing approach used by the edit view

## Deliverables

- Rewritten view: `app/Views/landing_pages/create.php`
- Rewritten view: `app/Views/landing_pages/edit.php`
- Unit tests with 80%+ coverage **(REQUIRED)**
- Integration tests for view rendering **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `create.php` view renders file input for `index_html` with required attribute
  - [ ] `create.php` view renders multi-file input for `images[]`
  - [ ] `create.php` view has `enctype="multipart/form-data"` on the form
  - [ ] `create.php` view does NOT contain references to `page-builder.js`, `CodeMirror`, or `preview-iframe`
  - [ ] `edit.php` view with a page containing 3 block delimiters renders 3 textareas labeled "Bloco 1", "Bloco 2", "Bloco 3"
  - [ ] `edit.php` view with no block delimiters renders a single raw HTML textarea
  - [ ] `edit.php` view pre-fills textareas with the correct text content from blocks
- Integration tests:
  - [ ] GET `/landing-pages/create` returns 200 with upload form HTML
  - [ ] GET `/landing-pages/edit/{id}` for a page with files returns 200 with block editor
  - [ ] GET `/landing-pages/edit/{id}` for a page created with the new system renders correctly
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- Upload form accepts files and is submitted with correct `enctype`
- Block editor displays correct number of textareas matching `BLOCO_N` delimiters in the uploaded HTML
- Fallback raw HTML editor appears when no delimiters exist
- No references to old block system components in either view
