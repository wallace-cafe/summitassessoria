---
status: completed
title: Bearer Token Filter
type: backend
complexity: low
dependencies: []
---

# Task 1: Bearer Token Filter

## Overview

Create `app/Filters/BearerTokenFilter.php`, a CodeIgniter 4 filter that validates the `Authorization: Bearer <token>` header on every `api/*` request by comparing the token against `encryption.key` from `.env`. Register the filter alias `bearerToken` in `app/Config/Filters.php`. This is the authentication gateway for the entire API surface.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC "Core Interfaces — BearerTokenFilter" section for the exact implementation pattern — do not duplicate here
- FOCUS ON "WHAT" — implement the filter and register it; the controller depends on this task being complete
- MINIMIZE CODE — the filter body is intentionally small; do not add abstractions not present in the TechSpec
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
1. MUST create `app/Filters/BearerTokenFilter.php` with namespace `App\Filters` implementing `FilterInterface`
2. MUST read the expected token via `getenv('encryption.key')` — no hardcoded values, no separate env variable
3. MUST return a `401` JSON envelope response (`{ "data": null, "meta": {}, "errors": "Unauthorized" }`) when the header is absent, malformed, or the token does not match
4. MUST call `log_message('warning', ...)` with `[api.auth.401]` prefix, IP, and URI on every rejected request — see TechSpec "Monitoring and Observability" section
5. MUST register the alias `'bearerToken' => \App\Filters\BearerTokenFilter::class` in the `$aliases` array of `app/Config/Filters.php`
6. MUST NOT modify any existing alias, global filter assignment, or filter rule already present in `Filters.php`
7. SHOULD return `null` from `before()` when the token is valid, allowing the request to proceed
</requirements>

## Subtasks

- [x] 1.1 Create `app/Filters/BearerTokenFilter.php` implementing `FilterInterface` with `before()` and `after()` methods
- [x] 1.2 Implement token extraction from `Authorization` header using a `preg_match` against `Bearer <token>` pattern
- [x] 1.3 Implement token comparison against `getenv('encryption.key')` and write warning log on mismatch
- [x] 1.4 Return the `401` JSON envelope on auth failure; return `null` on success
- [x] 1.5 Register `bearerToken` alias in `app/Config/Filters.php`
- [x] 1.6 Write unit tests for all filter scenarios

## Implementation Details

See TechSpec "Core Interfaces — BearerTokenFilter" for the complete method signatures, log format, and envelope structure. The filter must not apply any logic in `after()`.

The `encryption.key` value in `.env` has the format `hex2bin:ABCDEF...` — the client sends this verbatim as the Bearer token. The filter compares the full string including the `hex2bin:` prefix.

### Relevant Files

- `app/Filters/BearerTokenFilter.php` — **new file** to create
- `app/Config/Filters.php` — add `bearerToken` alias to `$aliases` array
- `app/Filters/AuthFilter.php` — reference for existing filter structure and interface usage
- `app/Filters/ThrottleFilter.php` — reference for `FilterInterface` pattern and `service('response')` usage

### Dependent Files

- `app/Config/Routes.php` — will reference `bearerToken` filter in the `api/*` route group (task_03)
- `app/Controllers/Api/LpController.php` — depends on the filter being registered to apply correctly (task_02)

### Related ADRs

- [ADR-001: Dedicated API Controller with Bearer Token Filter](../adrs/adr-001.md) — mandates a dedicated filter on `api/*`; no inline auth in controllers
- [ADR-002: Reuse `encryption.key` as the API Bearer Token](../adrs/adr-002.md) — mandates `getenv('encryption.key')` as the token source; no separate variable

## Deliverables

- `app/Filters/BearerTokenFilter.php` — fully implemented filter
- `app/Config/Filters.php` — updated with `bearerToken` alias
- Unit tests for `BearerTokenFilter` covering all scenarios below **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `Authorization` header absent — filter returns `401` JSON response with `"errors": "Unauthorized"`
  - [ ] `Authorization` header present with wrong token value — filter returns `401` JSON response
  - [ ] `Authorization` header present with correct `encryption.key` value — filter returns `null` (request proceeds)
  - [ ] `Authorization` header has `Bearer ` prefix but empty token — filter returns `401`
  - [ ] `encryption.key` not set in env (empty string) — filter returns `401` (empty string never matches)
  - [ ] Warning log entry contains `[api.auth.401]`, client IP, and request URI when token is rejected
- Integration tests:
  - [ ] HTTP request to any `api/*` route without `Authorization` header returns `401` JSON (verified after task_03 is complete)
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- `GET /api/lp/list` without a token returns `401` with JSON body after routes are wired (task_03)
- `app/Config/Filters.php` `$aliases` contains `'bearerToken'` key pointing to `BearerTokenFilter::class`
- No existing filter aliases or rules modified
