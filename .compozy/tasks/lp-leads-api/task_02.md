---
status: completed
title: API LP Controller
type: backend
complexity: medium
dependencies:
  - task_01
---

# Task 2: API LP Controller

## Overview

Create `app/Controllers/Api/LpController.php` with two public methods: `list()` returning all landing pages and `leads(string $slug)` returning all leads for a given landing page. Both methods return JSON envelope responses and reuse the existing `LandingPageModel` and `LeadModel` without modification. This controller is the sole business logic component of the LP Leads API.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC "Core Interfaces — LpController" and "API Endpoints" sections for response shapes, status codes, and method chaining patterns — do not duplicate here
- FOCUS ON "WHAT" — implement both controller methods with correct JSON envelopes and HTTP status codes
- MINIMIZE CODE — the controller is intentionally thin; all data access goes through existing model methods
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
1. MUST create `app/Controllers/Api/LpController.php` with namespace `App\Controllers\Api`
2. MUST extend `CodeIgniter\Controller` directly — NOT `App\Controllers\BaseController` (which loads session/form helpers unnecessary for a stateless API)
3. MUST implement `list()` returning all landing pages ordered by `created_at DESC`, exposing only `id`, `title`, `slug`, `created_at` — the `file_path` and `updated_at` fields MUST be excluded
4. MUST implement `leads(string $slug)` that resolves the landing page via `LandingPageModel::findBySlug($slug)` and returns all associated leads via `LeadModel::filterByLandingPage()->orderBy('created_at', 'DESC')->findAll()`
5. MUST return `404` JSON envelope when `findBySlug()` returns null
6. MUST return `200` with `"data": []` (empty array, not an error) when a landing page exists but has no leads
7. MUST NOT modify `LandingPageModel` or `LeadModel`
8. MUST use the JSON envelope `{ "data": ..., "meta": {}, "errors": null }` on all success responses and `{ "data": null, "meta": {}, "errors": "<message>" }` on all error responses — see TechSpec "API Endpoints" section for exact shapes
9. SHOULD use `$this->response->setJSON()` for all responses to ensure correct `Content-Type: application/json` header
</requirements>

## Subtasks

- [x] 2.1 Create directory `app/Controllers/Api/` and file `LpController.php` with correct namespace and class declaration extending `CodeIgniter\Controller`
- [x] 2.2 Implement `list()` method: fetch all landing pages, map to allowed fields only, return `200` JSON envelope
- [x] 2.3 Implement `leads(string $slug)` method: resolve landing page by slug, return `404` if not found, fetch and return leads on success
- [x] 2.4 Verify `filterByLandingPage()` chaining with `orderBy()->findAll()` produces correct ordered results
- [x] 2.5 Write unit and integration tests for both methods

## Implementation Details

See TechSpec "Core Interfaces — LpController" for the full method bodies and "Data Models" section for field lists. See TechSpec "API Endpoints" for the exact JSON envelope structure for each response scenario.

`LandingPageModel::findBySlug(string $slug)` already exists and returns `?array`. `LeadModel::filterByLandingPage(int $id)` returns `$this` (the model builder), enabling chaining.

The field exclusion in `list()` must use `array_map()` to project only the allowed fields — do not use `$model->select()` or model-level field restrictions, as those would affect other callers of the model.

### Relevant Files

- `app/Controllers/Api/LpController.php` — **new file** to create in new subdirectory
- `app/Models/LandingPageModel.php` — provides `findBySlug()` and `orderBy()->findAll()`; read-only usage
- `app/Models/LeadModel.php` — provides `filterByLandingPage()->orderBy()->findAll()`; read-only usage
- `app/Controllers/BaseController.php` — reference only to confirm what NOT to extend
- `app/Controllers/LeadsController.php` — reference for existing `filterByLandingPage()` usage pattern

### Dependent Files

- `app/Config/Routes.php` — will map `api/lp/list` → `Api\LpController::list` and `api/lp/leads/(:segment)` → `Api\LpController::leads/$1` (task_03)

### Related ADRs

- [ADR-001: Dedicated API Controller with Bearer Token Filter](../adrs/adr-001.md) — mandates isolated controller reusing existing models
- [ADR-003: API Controller in `app/Controllers/Api/` Subdirectory](../adrs/adr-003.md) — mandates `app/Controllers/Api/` location and `App\Controllers\Api` namespace

## Deliverables

- `app/Controllers/Api/LpController.php` — fully implemented controller with both methods
- Unit tests for `list()` and `leads()` covering all scenarios below **(REQUIRED)**
- Integration tests for both endpoints **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `list()` with landing pages in DB returns `200` with array containing only `id`, `title`, `slug`, `created_at` (no `file_path`)
  - [ ] `list()` with no landing pages returns `200` with `"data": []`
  - [ ] `list()` returns results ordered by `created_at` descending
  - [ ] `leads('campanha-agosto')` with valid slug and existing leads returns `200` with all lead fields (`id`, `landing_page_id`, `name`, `email`, `phone`, `message`, `status`, `created_at`)
  - [ ] `leads('campanha-agosto')` with valid slug but no leads returns `200` with `"data": []`
  - [ ] `leads('slug-inexistente')` with non-existent slug returns `404` with `"errors": "Landing page not found"` and `"data": null`
  - [ ] `leads()` returns leads ordered by `created_at` descending
- Integration tests:
  - [ ] `POST /api/lp/leads/campanha-agosto` with valid Bearer token returns `200` with lead data (requires task_01 and task_03)
  - [ ] `GET /api/lp/list` with valid Bearer token returns `200` with landing page data (requires task_01 and task_03)
  - [ ] All responses include `Content-Type: application/json` header
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- `GET /api/lp/list` returns correct landing page list without `file_path` field
- `POST /api/lp/leads/{slug}` returns full lead records for existing slugs
- `POST /api/lp/leads/{invalid-slug}` returns `404` JSON
- `LandingPageModel` and `LeadModel` have zero modifications
