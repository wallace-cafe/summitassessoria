---
status: completed
title: Implement lead management table (LeadsController + View)
type: backend
complexity: medium
dependencies:
    - task_01
    - task_03
    - task_07
---

# Task 08: Implement lead management table (LeadsController + View)

## Overview

Build the admin interface for reviewing captured leads. This task creates a sortable, searchable lead table with a dropdown filter to show leads from a specific landing page. The admin can slice the lead list by source campaign to prioritize follow-ups.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `LeadsController` with `index()` supporting optional `search` and `landing_page` query parameters
- MUST create `app/Views/leads/index.php` extending `layouts/dashboard` with a data table
- MUST display columns: Name, Email, Phone, Source (landing page slug), Status, Date Captured
- MUST implement free-text search across `name` and `email` fields
- MUST implement clickable column headers for sorting (e.g., by date, name, status)
- MUST implement a dropdown filter to show only leads belonging to a specific landing page
- MUST load the list of landing pages into the filter dropdown from `LandingPageModel`
- MUST preserve search, sort, and filter state across pagination (if pagination is added later)
- MUST be protected by the `auth` filter
</requirements>

## Subtasks
- [x] 08.1 Create `app/Controllers/LeadsController.php` with `index()` accepting search and filter params
- [x] 08.2 Create `app/Views/leads/index.php` with table, search input, sortable headers, and filter dropdown
- [x] 08.3 Add lead route to `app/Config/Routes.php`
- [x] 08.4 Verify search filters leads by name and email across all pages
- [x] 08.5 Verify landing-page filter shows only leads from the selected campaign
- [x] 08.6 Verify sorting by date and name works correctly

## Implementation Details

See TechSpec "Data Models" for `LeadModel` helper methods (`search()`, `filterByLandingPage()`) and TechSpec "Development Sequencing — Build Order Step 6" for lead management implementation guidance. The controller delegates query building to the model based on query parameters. Use CI4's query builder for search (`like`) and filter (`where`).

### Relevant Files
- `app/Controllers/LeadsController.php` — New lead management controller
- `app/Views/leads/index.php` — New lead table view
- `app/Models/LeadModel.php` — Must add `search()` and `filterByLandingPage()` helpers (task_07)
- `app/Models/LandingPageModel.php` — Used to populate the filter dropdown (task_06)
- `app/Config/Routes.php` — Must add `/leads` route

### Dependent Files
- `app/Views/layouts/dashboard.php` — Lead view extends this layout (task_03)
- `app/Cells/SidebarCell.php` — Sidebar includes "Manage Leads" link (task_03)
- `writable/database.db` — Must have `leads` table with data (task_01, task_07)

### Related ADRs
- (none directly applicable)

## Deliverables
- `app/Controllers/LeadsController.php`
- `app/Views/leads/index.php`
- Updated `app/Config/Routes.php`
- Unit tests with 80%+ coverage (REQUIRED)
- Integration tests for lead filtering and search (REQUIRED)

## Tests
- Unit tests:
  - [ ] `LeadModel::search('john')` returns leads where name or email contains "john"
  - [ ] `LeadModel::filterByLandingPage(1)` returns only leads with `landing_page_id = 1`
  - [ ] Combining search and filter returns the intersection of both conditions
- Integration tests:
  - [ ] GET `/leads` displays all leads in a table (requires auth session)
  - [ ] GET `/leads?search=john` shows only matching leads
  - [ ] GET `/leads?landing_page=1` shows only leads from that page
  - [ ] GET `/leads?search=john&landing_page=1` shows the intersection
  - [ ] Clicking a sort header changes the ordering of results
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- Lead table displays all captured leads with correct columns
- Search filters by name and email
- Landing-page filter dropdown shows all pages and filters leads correctly
- Sorting by column headers works for at least date and name
- Page is accessible only when authenticated
