---
status: completed
title: Implement landing page CRUD (LandingPagesController + Model + Views)
type: backend
complexity: medium
dependencies:
    - task_01
    - task_03
    - task_05
---

# Task 06: Implement landing page CRUD (LandingPagesController + Model + Views)

## Overview

Build the complete landing page management subsystem: a controller handling list, create, edit, update, and delete actions; a model for database operations; and views for each action extending the dashboard layout. This enables the admin to publish landing pages with a custom slug that immediately becomes publicly accessible.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `LandingPageModel` extending `CodeIgniter\Model` with `$allowedFields = ['title', 'slug', 'content']`
- MUST create `LandingPagesController` with `index()`, `create()`, `store()`, `edit()`, `update()`, `delete()`
- MUST create views: `app/Views/landing_pages/index.php`, `create.php`, `edit.php` extending `layouts/dashboard`
- MUST validate that `title`, `slug`, and `content` are required; `slug` must be unique
- MUST use inline validation in controller methods for create and update forms
- MUST redirect to `/landing-pages` after successful store, update, or delete with a flash message
- MUST display the public URL (`/p/{slug}`) in the list view for each landing page
- MUST allow editing of title, content, and slug with uniqueness validation on update (excluding the current record)
</requirements>

## Subtasks
- [x] 06.1 Create `app/Models/LandingPageModel.php` with `findBySlug()` helper
- [x] 06.2 Create `app/Controllers/LandingPagesController.php` with CRUD methods
- [x] 06.3 Create `app/Views/landing_pages/index.php` with list table and public URL links
- [x] 06.4 Create `app/Views/landing_pages/create.php` with form for title, slug, content
- [x] 06.5 Create `app/Views/landing_pages/edit.php` with pre-filled form
- [x] 06.6 Add landing page routes to `app/Config/Routes.php`
- [x] 06.7 Verify full CRUD cycle: create → list → edit → update → delete

## Implementation Details

See TechSpec "Data Models" for `LandingPageModel` configuration and TechSpec "Development Sequencing — Build Order Step 4" for implementation guidance. The controller uses CI4's `validate()` helper with inline rules. Slug uniqueness on update must use the `is_unique` rule with an exception for the current record ID.

### Relevant Files
- `app/Models/LandingPageModel.php` — New landing page model
- `app/Controllers/LandingPagesController.php` — New CRUD controller
- `app/Views/landing_pages/index.php` — List view
- `app/Views/landing_pages/create.php` — Create form view
- `app/Views/landing_pages/edit.php` — Edit form view
- `app/Config/Routes.php` — Must add landing page routes

### Dependent Files
- `app/Views/layouts/dashboard.php` — All views extend this layout (task_03)
- `app/Cells/SidebarCell.php` — Sidebar navigation includes Landing Pages link (task_03)
- `writable/database.db` — Must have `landing_pages` table (task_01)
- `app/Config/Filters.php` — Admin routes protected by `auth` filter (task_04)

### Related ADRs
- (none directly applicable)

## Deliverables
- `app/Models/LandingPageModel.php`
- `app/Controllers/LandingPagesController.php`
- `app/Views/landing_pages/index.php`
- `app/Views/landing_pages/create.php`
- `app/Views/landing_pages/edit.php`
- Updated `app/Config/Routes.php`
- Unit tests with 80%+ coverage (REQUIRED)
- Integration tests for landing page CRUD (REQUIRED)

## Tests
- Unit tests:
  - [ ] `LandingPageModel::findBySlug()` returns the correct page for an existing slug
  - [ ] `LandingPageModel::findBySlug()` returns null for a non-existent slug
  - [ ] Validation fails when `slug` is not unique on create
- Integration tests:
  - [ ] GET `/landing-pages` lists all landing pages (requires auth session)
  - [ ] POST `/landing-pages` with valid data creates a landing page and redirects to list
  - [ ] POST `/landing-pages` with duplicate slug shows validation error
  - [ ] GET `/landing-pages/edit/1` shows pre-filled edit form
  - [ ] POST `/landing-pages/update/1` updates the page and redirects to list
  - [ ] GET `/landing-pages/delete/1` removes the page and redirects to list
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- Admin can create a landing page with title, slug, and content
- Public URL `/p/{slug}` is shown in the list view
- Slug uniqueness is enforced on create and update
- Edit form pre-fills existing data
- Delete removes the page from the database
