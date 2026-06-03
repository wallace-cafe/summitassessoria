---
status: completed
title: Write integration tests for authentication, landing pages, and leads
type: test
complexity: medium
dependencies:
    - task_05
    - task_06
    - task_07
    - task_08
---

# Task 10: Write integration tests for authentication, landing pages, and leads

## Overview

Create a comprehensive integration test suite using CI4's `ControllerTestTrait` that validates the entire application flow. Tests cover authentication, authorization, landing page CRUD, lead capture, and lead filtering. The test database uses the in-memory SQLite3 configuration (`:memory:`) from the `tests` group.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create test files in `tests/` directory using CI4's `ControllerTestTrait`
- MUST configure the test environment to use the `tests` database group (SQLite3 in-memory)
- MUST seed test fixtures (admin user, landing pages, leads) before each test using migrations and seeders
- MUST test the login flow: valid credentials succeed, invalid credentials fail, throttling activates after 5 attempts
- MUST test auth filter behavior: protected routes redirect, public routes remain open
- MUST test landing page CRUD: create, read, update, delete
- MUST test lead capture: form submission stores lead with correct `landing_page_id` and status
- MUST test lead filtering: search by name/email, filter by landing page source
- MUST assert specific HTTP status codes (200, 302, 404, 429) and response content
- MUST achieve at least 80% code coverage across the tested components
</requirements>

## Subtasks
- [x] 10.1 Set up test base class with `ControllerTestTrait` and database reset strategy
- [x] 10.2 Write `AuthTest` covering login, logout, throttle, and auth filter
- [x] 10.3 Write `LandingPagesTest` covering CRUD operations
- [x] 10.4 Write `PublicAndLeadsTest` covering public page rendering, lead capture, and lead filtering
- [x] 10.5 Write `RoutesAndIntegrationTest` covering route protection and end-to-end flow
- [x] 10.6 Run full test suite and verify all tests pass

## Implementation Details

See TechSpec "Testing Approach" section for integration test scenarios and CI4's testing documentation. Use `CIUnitTestCase` with `ControllerTestTrait`. Reset the database before each test by re-running migrations and seeders. Use `assertRedirectTo()` for redirect assertions and `assertSee()` for content assertions.

### Relevant Files
- `tests/` — New test files location
- `phpunit.xml.dist` — PHPUnit configuration (already exists)
- `app/Config/Database.php` — `tests` group already uses SQLite3 `:memory:`

### Dependent Files
- All controllers, models, filters, and views from tasks 05–08 — These are the subjects under test

### Related ADRs
- (none directly applicable)

## Deliverables
- `tests/AuthTest.php`
- `tests/LandingPagesTest.php`
- `tests/PublicAndLeadsTest.php`
- `tests/RoutesAndIntegrationTest.php`
- PHPUnit coverage report showing >=80%

## Tests
- Integration tests:
  - [ ] `AuthTest::testValidLoginRedirectsToDashboard` — valid credentials create session and redirect
  - [ ] `AuthTest::testInvalidLoginShowsError` — invalid credentials display error without session
  - [ ] `AuthTest::testThrottleReturns429` — 6 rapid failed attempts return HTTP 429
  - [ ] `AuthTest::testAuthFilterRedirectsGuest` — GET `/dashboard` without session redirects to `/login`
  - [ ] `LandingPagesTest::testCreateLandingPage` — POST with valid data creates record and redirects
  - [ ] `LandingPagesTest::testDuplicateSlugShowsError` — duplicate slug returns validation error
  - [ ] `LandingPagesTest::testEditAndUpdate` — edit form pre-fills; update changes data
  - [ ] `LandingPagesTest::testDeleteLandingPage` — delete removes record
  - [ ] `PublicAndLeadsTest::testPublicPageRenders` — `/p/{slug}` returns 200 with content
  - [ ] `PublicAndLeadsTest::testPublicPage404` — `/p/nonexistent` returns 404
  - [ ] `PublicAndLeadsTest::testLeadCaptureStoresData` — form submission creates lead with "New" status
  - [ ] `PublicAndLeadsTest::testLeadSearchFilters` — search query returns matching leads only
  - [ ] `PublicAndLeadsTest::testLeadSourceFilter` — landing-page filter returns matching leads only
  - [ ] `RoutesAndIntegrationTest::testEndToEndFlow` — login → create page → capture lead → verify in list
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- Full integration test suite runs successfully with `phpunit`
- Every controller action has at least one integration test
- Auth, landing page, and lead flows are fully covered
