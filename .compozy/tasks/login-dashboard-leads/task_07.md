---
status: completed
title: Build public landing page rendering and lead capture
type: backend
complexity: medium
dependencies:
    - task_01
    - task_06
---

# Task 07: Build public landing page rendering and lead capture

## Overview

Implement the public-facing side of the application: rendering landing pages by slug and capturing leads through an embedded contact form. This task bridges the admin-created content to the visitor experience and stores submitted leads in the database with a default status of "New" linked to the originating landing page.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `PublicController` with `show($slug)` and `storeLead($slug)` methods
- MUST create `LeadModel` extending `CodeIgniter\Model` with `$allowedFields` for all lead fields
- MUST create `app/Views/public/landing_page.php` for public rendering (no dashboard layout)
- MUST render a 404 page if the landing page slug does not exist
- MUST display the landing page title and content on the public page
- MUST embed a lead capture form with fields: Name, Email, Phone, Message
- MUST validate that Name and Email are required; Email must be a valid email format
- MUST store the lead with `landing_page_id` set to the current page's ID and `status` defaulting to "New"
- MUST show a simple success confirmation to the visitor after submission
- MUST not require authentication for public routes
</requirements>

## Subtasks
- [x] 07.1 Create `app/Models/LeadModel.php` with methods for search and filtering by landing page
- [x] 07.2 Create `app/Controllers/PublicController.php` with `show($slug)` and `storeLead($slug)`
- [x] 07.3 Create `app/Views/public/landing_page.php` with content display and lead form
- [x] 07.4 Add public routes `/p/(:any)` and `/p/(:any)/lead` to `Routes.php` outside the auth filter group
- [x] 07.5 Verify that a created landing page is accessible publicly at `/p/{slug}`
- [x] 07.6 Verify that submitting the lead form stores data correctly with `landing_page_id` and status "New"

## Implementation Details

See TechSpec "Data Models" for `LeadModel` configuration and TechSpec "Development Sequencing — Build Order Step 5" for public page implementation guidance. The public view does not use the dashboard layout; it should be a simple, clean page that displays the landing page content and includes the lead form. Consider adding basic styling inline or referencing `style.css` selectively.

### Relevant Files
- `app/Models/LeadModel.php` — New lead model
- `app/Controllers/PublicController.php` — New public-facing controller
- `app/Views/public/landing_page.php` — New public landing page view
- `app/Config/Routes.php` — Must add public routes (no auth filter)

### Dependent Files
- `app/Models/LandingPageModel.php` — Used to fetch page by slug (task_06)
- `writable/database.db` — Must have `leads` table (task_01)
- `app/Config/Filters.php` — Public routes must NOT be behind `auth` filter

### Related ADRs
- (none directly applicable)

## Deliverables
- `app/Models/LeadModel.php`
- `app/Controllers/PublicController.php`
- `app/Views/public/landing_page.php`
- Updated `app/Config/Routes.php`
- Unit tests with 80%+ coverage (REQUIRED)
- Integration tests for lead capture flow (REQUIRED)

## Tests
- Unit tests:
  - [ ] `LeadModel` inserts a lead with correct `landing_page_id` and default status "New"
  - [ ] `LeadModel::search()` returns leads matching a search term across name and email
  - [ ] `LeadModel::filterByLandingPage()` returns only leads for the given page ID
- Integration tests:
  - [ ] GET `/p/test-slug` renders the landing page content when the slug exists
  - [ ] GET `/p/nonexistent` returns a 404 response
  - [ ] POST `/p/test-slug/lead` with valid data stores a lead and shows success
  - [ ] POST `/p/test-slug/lead` with missing email shows a validation error
  - [ ] Stored lead has `landing_page_id` matching the page and `status` equal to "New"
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- Public landing pages render at `/p/{slug}` without requiring login
- Lead form submission stores the lead with correct `landing_page_id` and status "New"
- Invalid slug returns a 404 page
- Success confirmation is shown after valid lead submission
