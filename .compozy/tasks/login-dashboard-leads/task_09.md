---
status: completed
title: Configure all application routes and final integration
type: backend
complexity: low
dependencies:
    - task_04
    - task_05
    - task_06
    - task_07
    - task_08
---

# Task 09: Configure all application routes and final integration

## Overview

Consolidate all route definitions, apply the auth filter to admin route groups, remove deprecated default files, and perform a final integration check to ensure the entire application flows correctly from login through landing page creation to lead capture and review.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST update `app/Config/Routes.php` with a clean, organized route map grouping admin routes
- MUST apply the `auth` filter to the admin route group in `Routes.php` or `Filters.php`
- MUST ensure public routes (`/p/...`) are NOT behind the `auth` filter
- MUST remove or redirect `app/Controllers/Home.php`
- MUST remove `app/Views/welcome_message.php`
- MUST verify the full end-to-end flow: login → create landing page → view public page → submit lead → review lead in dashboard
- MUST ensure all routes return expected HTTP status codes (200 for success, 302 for redirects, 404 for missing resources)
</requirements>

## Subtasks
- [x] 09.1 Consolidate and clean up `app/Config/Routes.php` with all routes defined
- [x] 09.2 Apply `auth` filter to admin route group; confirm public routes are excluded
- [x] 09.3 Remove `app/Controllers/Home.php` or redirect its `index()` to `/login`
- [x] 09.4 Remove `app/Views/welcome_message.php`
- [x] 09.5 Run end-to-end integration check: login → create page → capture lead → review lead
- [x] 09.6 Verify no broken links or missing assets (CSS, JS, favicon)

## Implementation Details

See TechSpec "API Endpoints" table for the complete route map and TechSpec "Impact Analysis" for files to modify or remove. Use CI4 route groups to apply the `auth` filter to all admin routes in one place:

```php
$routes->group('', ['filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('landing-pages', 'LandingPagesController::index');
    // ... etc
});
```

### Relevant Files
- `app/Config/Routes.php` — Must contain all application routes
- `app/Config/Filters.php` — May need route-group filter assignment
- `app/Controllers/Home.php` — To be removed or redirected
- `app/Views/welcome_message.php` — To be removed

### Dependent Files
- All controllers and views from previous tasks — Their routes must be correctly mapped

### Related ADRs
- (none directly applicable)

## Deliverables
- Cleaned `app/Config/Routes.php`
- Updated `app/Config/Filters.php` (if needed for group filtering)
- Removed or redirected `Home.php`
- Removed `welcome_message.php`
- Integration tests for full end-to-end flow (REQUIRED)

## Tests
- Integration tests:
  - [ ] GET `/` redirects to `/login`
  - [ ] Full happy path: login → create landing page → visit `/p/{slug}` → submit lead → view leads table and verify lead appears
  - [ ] Accessing `/dashboard` without session redirects to `/login`
  - [ ] Accessing `/p/{slug}` without session works (public route)
  - [ ] All routes return expected HTTP status codes
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- All admin routes are protected by `auth` filter
- All public routes are accessible without authentication
- No broken links or missing assets
- End-to-end flow works without manual intervention
