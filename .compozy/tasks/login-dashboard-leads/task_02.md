---
status: completed
title: Update BaseController and create global CSS/JS assets
type: backend
complexity: low
dependencies: []
---

# Task 02: Update BaseController and create global CSS/JS assets

## Overview

Prepare the shared controller foundation and global frontend assets so that all subsequent features have the required helpers, session support, and dark-theme styling available. This is a low-complexity enabling task that touches the base controller and public asset directories.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST update `BaseController` to preload `session`, `form`, and `url` helpers for all extending controllers
- MUST create `public/css/style.css` with the dark-canvas theme base styles (background, text colors, hairlines)
- MUST create `public/js/app.js` as a minimal global JavaScript file (can be empty or contain basic utilities)
- MUST ensure both asset directories exist and are web-accessible
- MUST keep changes minimal; no view logic in BaseController beyond helper preloading
</requirements>

## Subtasks
- [x] 02.1 Update `BaseController::initController()` to preload `session`, `form`, and `url` helpers
- [x] 02.2 Create `public/css/` directory and `public/css/style.css` with dark theme base tokens
- [x] 02.3 Create `public/js/` directory and `public/js/app.js` with minimal global JS
- [x] 02.4 Verify assets are served correctly by accessing them via browser

## Implementation Details

See TechSpec "Core Interfaces" section for the `BaseController` pattern and TechSpec "Impact Analysis" for asset directory creation.

### Relevant Files
- `app/Controllers/BaseController.php` — Must preload helpers and session
- `public/css/style.css` — New global stylesheet
- `public/js/app.js` — New global JavaScript file

### Dependent Files
- All future controllers extending `BaseController` — Will inherit preloaded helpers
- All future views — Will reference `style.css` and `app.js`

### Related ADRs
- (none directly applicable)

## Deliverables
- Updated `app/Controllers/BaseController.php`
- `public/css/style.css` with dark theme foundation
- `public/js/app.js`
- Unit tests with 80%+ coverage (REQUIRED)
- Integration tests for asset serving (REQUIRED)

## Tests
- Unit tests:
  - [ ] BaseController preloads `session`, `form`, and `url` helpers
- Integration tests:
  - [ ] GET `/css/style.css` returns HTTP 200 with correct Content-Type
  - [ ] GET `/js/app.js` returns HTTP 200 with correct Content-Type
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- `BaseController` successfully preloads helpers without errors
- Both CSS and JS files are accessible via HTTP and return 200 OK
