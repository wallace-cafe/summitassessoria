---
status: completed
title: Controller Validation and Unified Flat Storage
type: backend
complexity: medium
dependencies:
  - task_01
---

# Task 2: Controller Validation and Unified Flat Storage

## Overview
This task implements server-side validation for the new dynamic assets list, expanding file size limits to 50MB and allowing images and videos. Validated uploads are written flat under a unified `assets/` directory within the landing page's root folder.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- The backend controller validator MUST check the `assets` payload instead of `images`.
- The allowed size limit MUST be expanded to 50MB (`max_size[assets,51200]`).
- Allowed formats MUST include `.jpg, .jpeg, .png, .webp, .svg, .gif` and video formats `.mp4, .webm, .ogg`.
- Successfully validated files MUST be saved flat in the directory `WRITEPATH . 'landing_pages/' . $slug . '/assets/'`.
- The old `images/` directory MUST no longer be created for new pages.
</requirements>

## Subtasks
- [x] 2.1 Update validation rules in `LandingPagesController::store()` to reference `assets` and expand file limits to 50MB and video formats.
- [x] 2.2 Refactor saving logic in `LandingPagesController::store()` to process multiple uploaded `assets` and move them flat to `/assets/` directory.
- [x] 2.3 Add unit tests in `tests/LandingPagesTest.php` verifying validation rules and flat directory storage.

## Implementation Details
Adjust backend upload validation and storage location. See TechSpec 'System Architecture - Component Overview' and 'API Endpoints' for patterns.

### Relevant Files
- `app/Controllers/LandingPagesController.php` — Controller validating the input and managing filesystem writes.
- `tests/LandingPagesTest.php` — Test suite for verifying controller and storage rules.

### Dependent Files
- `app/Controllers/PublicController.php` — Depends on files being uploaded to the `/assets/` path in order to resolve them.

### Related ADRs
- [ADR-001: Unified Asset Pipeline for Landing Pages](../adrs/adr-001.md) — Decisions on unified directory and video support.
- [ADR-002: Dynamic Flat Asset Serving and Validation Strategy](../adrs/adr-002.md) — Strategy for CodeIgniter built-in validation rules and size limits.

## Deliverables
- Upgraded upload controller backend rules.
- Flat storage filesystem management implementation.
- Controller unit tests validating formats and size boundaries.

## Tests
- Unit tests:
  - [x] Validation logic: Assert that files larger than 50MB are rejected.
  - [x] Format rules: Assert that `.mp4`, `.webm`, and `.png` are accepted, while `.zip` or `.exe` are rejected.
  - [x] Flat directory layout: Verify files are created flat inside `assets/` subfolder.
- Integration tests:
  - [x] Full creation flow: Send dummy media and verify directory structure creation and page insert.
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- Controller successfully rejects invalid files and sizes above 50MB.
- Uploaded assets are saved directly flat under the new `assets/` subfolder.
