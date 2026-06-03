---
status: completed
title: Cleanup Old Block System Files
type: refactor
complexity: medium
dependencies:
  - task_05
---

# Task 06: Cleanup Old Block System Files

## Overview

Remove all files and code from the old block-based system that are no longer needed. This includes deleting the `BlockTemplatesController`, `BlockTemplateModel`, `page-builder.js`, the entire `block_templates/` views directory, removing old block template routes from `Routes.php`, removing the "Block Templates" nav link from the sidebar, and removing composer-specific CSS classes from `style.css`. This task must be done last to ensure no remaining references break the application.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST delete `app/Controllers/BlockTemplatesController.php`
- MUST delete `app/Models/BlockTemplateModel.php`
- MUST delete `public/assets/js/page-builder.js`
- MUST delete the entire `app/Views/block_templates/` directory (index.php, create.php, edit.php)
- MUST remove all `/landing-pages/blocks/*` routes from `app/Config/Routes.php`
- MUST remove "Block Templates" (`<li>`) nav item from `app/Cells/sidebar.php`
- MUST remove all page-builder-specific CSS classes from `public/assets/css/style.css` (`.composer-layout`, `.composer-preview`, `.preview-iframe`, `.hero-editor`, `.block-instance`, `.token-input`, `.token-inputs`, `.block-instance-header`, `.block-instance-name`, `.block-instance-actions`, and their responsive variants)
- MUST remove any `sidebar_active => 'block-templates'` references in the remaining codebase
- MUST verify that no remaining code imports, references, or calls `BlockTemplateModel`, `BlockTemplatesController`, or `page-builder.js`
- MUST update test files that reference deleted components — either remove the test or mark it as skipped with a clear note about the feature removal
</requirements>

## Subtasks

- [x] 6.1 Delete BlockTemplatesController, BlockTemplateModel, and page-builder.js
- [x] 6.2 Delete `app/Views/block_templates/` directory
- [x] 6.3 Remove `/landing-pages/blocks/*` routes from Routes.php
- [x] 6.4 Remove "Block Templates" link from sidebar
- [x] 6.5 Remove composer-specific CSS classes from style.css
- [x] 6.6 Update or remove tests that reference deleted components
- [x] 6.7 Verify no stale references remain anywhere in the codebase

## Implementation Details

The cleanup is straightforward deletion. The critical risk is leaving a stale reference that breaks the application. After deletion, run the full test suite and verify the application boots (access `/dashboard`, `/landing-pages`, `/leads` without errors).

### Relevant Files
- `app/Controllers/BlockTemplatesController.php` — delete
- `app/Models/BlockTemplateModel.php` — delete
- `public/assets/js/page-builder.js` — delete
- `app/Views/block_templates/` — delete directory recursively
- `app/Config/Routes.php` — remove 7 block template routes (lines 25-31)
- `app/Cells/sidebar.php` — remove lines 14-18 (the `<li>` for "Block Templates")
- `public/assets/css/style.css` — remove ~18 lines of composer-specific CSS
- `tests/unit/BlockTemplateModelTest.php` — delete or mark skipped
- `tests/unit/BlockTemplatesControllerTest.php` — delete or mark skipped
- `tests/unit/BlockTemplatesRoutesTest.php` — delete or mark skipped
- `tests/unit/ComposerCssTest.php` — update to no longer assert page-builder CSS classes

### Dependent Files
- None — this is the final cleanup task with no dependents

### Related ADRs
- [ADR-005: File Upload Landing Page with Comment-Delimited Block Editing](../adrs/adr-005.md) — Clean break from old block system

## Deliverables

- Deleted: `BlockTemplatesController.php`
- Deleted: `BlockTemplateModel.php`
- Deleted: `page-builder.js`
- Deleted: `app/Views/block_templates/` directory
- Modified: `Routes.php` (clean)
- Modified: `sidebar.php` (clean)
- Modified: `style.css` (clean)
- Updated or removed: stale test files
- All remaining tests passing **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] No code in `app/Controllers/` imports `BlockTemplateModel` or references `BlockTemplatesController`
  - [ ] No code in `app/Models/` references `BlockTemplateModel` (file deleted)
  - [ ] `Routes.php` has no routes matching `/landing-pages/blocks/`
  - [ ] `sidebar.php` has no link to `/landing-pages/blocks`
  - [ ] `style.css` has no `.composer-layout`, `.block-instance`, `.token-input`, `.hero-editor`, or `.preview-iframe` classes
  - [ ] `public/assets/js/` no longer contains `page-builder.js`
- Integration tests:
  - [ ] Application boots without errors: GET `/dashboard` returns 200
  - [ ] GET `/landing-pages` returns 200 with no references to deleted components
  - [ ] GET `/landing-pages/blocks` returns 404 (route removed)
  - [ ] Full existing test suite passes with no failures
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- No `BlockTemplatesController`, `BlockTemplateModel`, or `page-builder.js` files exist
- No block template routes in the application
- Sidebar no longer shows "Block Templates"
- Full test suite passes
- Application loads without errors
