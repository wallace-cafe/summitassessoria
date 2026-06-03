---
status: completed
title: LandingPagesController Rewrite with File Upload
type: backend
complexity: high
dependencies:
  - task_01
  - task_03
  - task_04
---

# Task 05: LandingPagesController Rewrite with File Upload

## Overview

Rewrite `LandingPagesController::store()` to handle file uploads: validate uploaded files, store them on disk under `writable/landing_pages/{slug}/`, scan the HTML for `<form id="lead-form">`, and insert a DB record with the correct `file_path`. Rewrite `update()` to receive edited block text from the edit view, call `rewrite_block_content()`, and save the modified HTML back to disk. Rewrite `delete()` to remove the page's files from disk before deleting the DB record.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST rewrite `store()` to accept `multipart/form-data` with file inputs matching the create view (task 04)
- MUST validate `index_html` is present, valid `.html` extension, and within size limits
- MUST validate `style_css` (optional) is `.css`, `app_js` (optional) is `.js`, `images[]` (optional) are valid image types
- MUST create directory `writable/landing_pages/{slug}/` on successful validation
- MUST move uploaded files to the directory preserving their original filenames
- MUST set `file_path` to `'landing_pages/' . $slug` on the inserted record
- MUST scan `index.html` for `<form id="lead-form">` and log a warning if absent
- MUST prevent overwriting existing page files by rejecting duplicate slugs (already handled by unique slug validation)
- MUST rewrite `update($id)` to read the current `index.html` from disk, call `parse_block_delimiters()` to validate blocks, then call `rewrite_block_content()` with the submitted `block_text[]` array
- MUST write the rewritten HTML back to the same `index.html` file on disk
- MUST NOT allow re-upload of files during update (file uploads are create-only per MVP)
- MUST rewrite `delete($id)` to remove the entire `writable/landing_pages/{slug}/` directory recursively, then delete the DB record
- MUST log info events on create, update, and delete operations
- MUST update validation rules in both `store()` and `update()` per TechSpec API Endpoints section
- MUST remove all old validation rules for `blocks` and `custom_css`
</requirements>

## Subtasks

- [x] 5.1 Rewrite `store()` with file upload handling, directory creation, and DB insert
- [x] 5.2 Rewrite `update()` to read HTML, rewrite block content, and save back to disk
- [x] 5.3 Rewrite `delete()` to remove files from disk before DB deletion
- [x] 5.4 Add logging for all CRUD operations
- [x] 5.5 Write unit and integration tests

## Implementation Details

See TechSpec "API Endpoints — Landing Pages Resource" section for validation rules and request format. Use CI4's `$this->request->getFile()` and `$file->move()` for file handling. Use `Filesystem::deleteDirectory()` or recursive `unlink`/`rmdir` for directory cleanup on delete. Load the BlockEditorHelper via `helper('block_editor')` in `update()`.

### Relevant Files
- `app/Controllers/LandingPagesController.php` — rewrite `store()`, `update()`, `delete()`
- `app/Models/LandingPageModel.php` — already updated in task 01
- `app/Helpers/block_editor_helper.php` (task 02) — provides `rewrite_block_content()`
- `app/Views/landing_pages/create.php` (task 04) — upload form POSTs to `store()`
- `app/Views/landing_pages/edit.php` (task 04) — block editor POSTs to `update()`
- `app/Config/Routes.php` (task 03) — routes already defined

### Dependent Files
- `tests/LandingPagesTest.php` — update CRUD tests for new file-based workflow
- `tests/unit/LandingPagesControllerTest.php` — rewrite for file upload testing

### Related ADRs
- [ADR-005: File Upload Landing Page with Comment-Delimited Block Editing](../adrs/adr-005.md) — Overall approach context
- [ADR-007: Landing Pages Database Schema — file_path Column](../adrs/adr-007.md) — Uses `file_path` column
- [ADR-008: Block Editor HTML Parsing via DOMDocument](../adrs/adr-008.md) — Uses `rewrite_block_content()` helper

## Deliverables

- Rewritten controller: `app/Controllers/LandingPagesController.php`
- Updated tests
- Unit tests with 80%+ coverage **(REQUIRED)**
- Integration tests for full upload/edit/delete flow **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `store()` with valid files creates directory and DB record with correct `file_path`
  - [ ] `store()` with missing `index.html` returns validation error
  - [ ] `store()` with invalid file extension returns validation error
  - [ ] `store()` with duplicate slug returns validation error
  - [ ] `store()` with missing `<form id="lead-form">` in HTML still succeeds (logs warning)
  - [ ] `update()` with valid block text rewrites `index.html` correctly
  - [ ] `update()` with no blocks detected falls back to raw HTML save
  - [ ] `delete()` removes directory from disk and DB record
  - [ ] `delete()` with non-existent page returns 404
  - [ ] Log events are created for create/update/delete operations
- Integration tests:
  - [ ] Full flow: upload page → visit `/p/{slug}` → verify HTML and hidden field → submit lead
  - [ ] Edit flow: upload page with 3 blocks → edit block 2 → save → verify only block 2 changed on public page
  - [ ] Delete flow: delete page → verify `/p/{slug}` returns 404 and directory is gone from disk
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- File upload creates correct directory structure and DB record
- Block text editing correctly rewrites only the target block in the HTML file
- Page deletion completely removes all files from disk
- All CRUD operations log appropriate events
