---
status: completed
title: Migration and Model Update
type: backend
complexity: low
dependencies: []
---

# Task 01: Migration and Model Update

## Overview

Create a new database migration that removes the old `block_templates` table and the `blocks`/`custom_css` columns from `landing_pages`, replacing them with a single `file_path` VARCHAR column. Update the `LandingPageModel` to reflect the new schema. This is the foundation task — no other backend work can proceed without it.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create a new migration class `RefactorLandingPagesForFileUpload` in `app/Database/Migrations/`
- MUST drop the `block_templates` table entirely
- MUST drop `blocks` and `custom_css` columns from `landing_pages`
- MUST add `file_path` VARCHAR(255) nullable column to `landing_pages`
- MUST handle SQLite < 3.35.0 DROP COLUMN limitation using the table-recreation pattern established in the existing migration `AddBlocksToLandingPages`
- MUST provide a `down()` method that restores `blocks`, `custom_css`, and the `block_templates` table
- MUST update `LandingPageModel::allowedFields` to `['title', 'slug', 'file_path']`
- MUST update the test file `tests/unit/LandingPageModelTest.php` to assert the new `allowedFields` and reject old fields
</requirements>

## Subtasks

- [x] 1.1 Create the migration file with proper up() and down() methods
- [x] 1.2 Update `LandingPageModel::allowedFields` to `['title', 'slug', 'file_path']`
- [x] 1.3 Update unit tests to validate the new schema

## Implementation Details

Create `app/Database/Migrations/2026-05-20-000001_RefactorLandingPagesForFileUpload.php`. Follow the same SQLite compatibility pattern used in `AddBlocksToLandingPages` — check SQLite version and use table-recreation if below 3.35.0.

### Relevant Files
- `app/Database/Migrations/2026-05-20-000000_AddBlocksToLandingPages.php` — reference for SQLite table-recreation pattern and existing schema
- `app/Models/LandingPageModel.php` — update `allowedFields` property
- `tests/unit/LandingPageModelTest.php` — update assertions for new schema

### Dependent Files
- `app/Controllers/LandingPagesController.php` — will read `file_path` instead of `blocks`/`custom_css` (task 05)
- `app/Controllers/PublicController.php` — will read `file_path` (task 03)
- `app/Views/landing_pages/create.php` — no longer uses `blocks`/`custom_css` (task 04)
- `app/Views/landing_pages/edit.php` — no longer uses `blocks`/`custom_css` (task 04)

### Related ADRs
- [ADR-007: Landing Pages Database Schema — file_path Column](../adrs/adr-007.md) — Rationale for single `file_path` column over individual columns or JSON

## Deliverables

- New migration file: `app/Database/Migrations/2026-05-20-000001_RefactorLandingPagesForFileUpload.php`
- Updated model: `app/Models/LandingPageModel.php`
- Updated test file: `tests/unit/LandingPageModelTest.php`
- Unit tests with 80%+ coverage **(REQUIRED)**
- Integration tests for migration up/down **(REQUIRED)**

## Tests

- Unit tests:
  - [x] `LandingPageModel::$allowedFields` contains `'title'`, `'slug'`, `'file_path'` and excludes `'blocks'`, `'custom_css'`, `'content'`
  - [x] `LandingPageModel::findBySlug()` works with new schema
  - [x] `file_path` column accepts NULL values
  - [x] Duplicate `slug` is still rejected at the model level
- Integration tests:
  - [x] Migration `up()` creates `file_path` column and drops `block_templates` table
  - [x] Migration `down()` restores `blocks`, `custom_css` columns and `block_templates` table
  - [x] Running `up()` twice is idempotent (no errors)
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- Migration runs cleanly against SQLite with no errors
- `LandingPageModel` correctly persists `title`, `slug`, `file_path` and ignores other fields
