---
status: completed
title: Implement AuthFilter, ThrottleFilter, and filter registration
type: backend
complexity: medium
dependencies: []
---

# Task 04: Implement AuthFilter, ThrottleFilter, and filter registration

## Overview

Create the two cross-cutting security filters that protect admin routes and prevent brute-force login attempts. Register both filters in the CI4 filter configuration so they are applied to the correct routes before any business logic executes.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `AuthFilter` implementing `FilterInterface` that checks `session()->get('isLoggedIn')` and redirects to `/login` when absent
- MUST create `ThrottleFilter` that uses CI4's `Throttler` service to limit POST login attempts to 5 per minute per IP
- MUST register both filters as aliases in `app/Config/Filters.php`
- MUST apply the `auth` filter to all admin route groups in `Filters.php`
- MUST apply the `throttle` filter to POST `/login` in `Filters.php`
- MUST return HTTP 429 (Too Many Requests) when the throttle limit is exceeded
- MUST ensure Throttler uses a non-dummy cache handler (file-based cache is sufficient)
</requirements>

## Subtasks
- [x] 04.1 Create `app/Filters/AuthFilter.php` with session verification logic
- [x] 04.2 Create `app/Filters/ThrottleFilter.php` with Throttler integration
- [x] 04.3 Register `auth` and `throttle` aliases in `app/Config/Filters.php`
- [x] 04.4 Apply `auth` filter to admin routes and `throttle` to POST login
- [x] 04.5 Verify that accessing a protected route without a session redirects to `/login`
- [x] 04.6 Verify that 6 rapid failed login attempts triggers a 429 response

## Implementation Details

See TechSpec "Core Interfaces" section for the `AuthFilter` code pattern and TechSpec "Development Sequencing — Build Order Step 3" for filter registration guidance. The `ThrottleFilter` should follow the official CI4 Throttler example from the documentation.

### Relevant Files
- `app/Filters/AuthFilter.php` — New session guard filter
- `app/Filters/ThrottleFilter.php` — New rate-limiting filter
- `app/Config/Filters.php` — Must register aliases and assign filters to routes
- `app/Config/Cache.php` — Ensure file-based cache is active for Throttler

### Dependent Files
- All admin controllers — Their routes will be protected by `AuthFilter`
- `AuthController::authenticate()` — Will be rate-limited by `ThrottleFilter`

### Related ADRs
- (none directly applicable)

## Deliverables
- `app/Filters/AuthFilter.php`
- `app/Filters/ThrottleFilter.php`
- Updated `app/Config/Filters.php`
- Unit tests with 80%+ coverage (REQUIRED)
- Integration tests for filter behavior (REQUIRED)

## Tests
- Unit tests:
  - [ ] AuthFilter redirects to `/login` when `isLoggedIn` is missing from session
  - [ ] AuthFilter allows the request to proceed when `isLoggedIn` is present
  - [ ] ThrottleFilter returns 429 after exceeding the allowed attempt count
  - [ ] ThrottleFilter allows the request when under the attempt limit
- Integration tests:
  - [ ] GET `/dashboard` without a session redirects to `/login` with 302 status
  - [ ] POST `/login` more than 5 times in 60 seconds returns 429
  - [ ] POST `/login` within the limit returns 200 or redirect (not 429)
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- `AuthFilter` redirects unauthenticated requests to `/login`
- `ThrottleFilter` returns 429 after 5 failed login attempts within 1 minute
- Both filters are registered and active in `Filters.php`
