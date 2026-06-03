---
status: completed
title: API Route Group
type: backend
complexity: low
dependencies:
  - task_01
  - task_02
---

# Task 3: API Route Group

## Overview

Register the two API endpoints in `app/Config/Routes.php` as a named group under the `api/lp/` prefix with the `bearerToken` filter applied. This wires the `BearerTokenFilter` (task_01) and `Api\LpController` (task_02) together into a live, authenticated API surface. No new files are created — this task modifies only one existing file.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC "System Architecture — Component Overview" and "Development Sequencing" sections for the exact route group structure
- FOCUS ON "WHAT" — register the two routes with the correct HTTP methods, paths, controller references, and filter
- MINIMIZE CODE — this task is two route declarations inside one group block; no additional abstraction is needed
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
1. MUST add a `$routes->group()` block in `app/Config/Routes.php` after all existing route declarations
2. MUST register `GET api/lp/list` mapped to `Api\LpController::list`
3. MUST register `POST api/lp/leads/(:segment)` mapped to `Api\LpController::leads/$1`
4. MUST apply the `bearerToken` filter to the entire `api` group via `['filter' => 'bearerToken']` option on the group call
5. MUST NOT modify, reorder, or remove any existing route declaration
6. MUST NOT apply the `bearerToken` filter globally or to any route outside the `api` group
7. SHOULD place the API group block after existing admin routes and before any trailing newline, with a comment `// API routes` above it
</requirements>

## Subtasks

- [x] 3.1 Add a `// API routes` comment and `$routes->group('api', ['filter' => 'bearerToken'], ...)` block at the end of `app/Config/Routes.php`
- [x] 3.2 Register `GET lp/list` → `Api\LpController::list` inside the group
- [x] 3.3 Register `POST lp/leads/(:segment)` → `Api\LpController::leads/$1` inside the group
- [x] 3.4 Verify no existing routes are affected by running the application and checking web routes still work
- [x] 3.5 Write smoke/integration tests confirming authenticated access to both endpoints

## Implementation Details

See TechSpec "System Architecture — Component Overview" for the component wiring diagram and "Development Sequencing — Build Order step 4" for the exact route group pattern.

Inside a `$routes->group('api', ...)` callback, the inner route paths are relative to the group prefix. The controller reference must use the subdirectory notation understood by CI4's PSR-4 autoloader: `'Api\LpController::list'`.

The `(:segment)` placeholder matches a single URL segment (no slashes), which is the correct constraint for a landing page slug.

### Relevant Files

- `app/Config/Routes.php` — **modified file**; the only file changed in this task

### Dependent Files

- `app/Filters/BearerTokenFilter.php` — must exist and be registered before routes can reference the `bearerToken` filter alias (task_01)
- `app/Controllers/Api/LpController.php` — must exist before routes can dispatch to it (task_02)

### Related ADRs

- [ADR-001: Dedicated API Controller with Bearer Token Filter](../adrs/adr-001.md) — mandates filter applied at route group level, not per-controller

## Deliverables

- `app/Config/Routes.php` — updated with the `api` route group
- Integration tests confirming both endpoints are reachable and authenticated **(REQUIRED)**

## Tests

- Integration tests:
  - [ ] `GET /api/lp/list` with correct `Authorization: Bearer <token>` header returns `200` (not `404` or `401`)
  - [ ] `POST /api/lp/leads/any-slug` with correct `Authorization: Bearer <token>` header returns `200` or `404` (not `401` or route-not-found)
  - [ ] `GET /api/lp/list` without `Authorization` header returns `401` JSON response
  - [ ] `POST /api/lp/leads/any-slug` without `Authorization` header returns `401` JSON response
  - [ ] `GET /dashboard` (existing web route) still returns `302` redirect to login — no regression
  - [ ] `GET /p/some-slug` (existing public route) still resolves correctly — no regression
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- Both API endpoints respond at their declared paths with correct authentication behavior
- All pre-existing web and public routes continue to work without modification
- `app/Config/Routes.php` has exactly one new `group()` block; no existing routes altered
