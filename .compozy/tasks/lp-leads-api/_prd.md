# LP Leads API — Product Requirements Document

## Overview

The LP Leads API exposes landing page and lead data to internal dashboards and tooling through a lightweight, secured HTTP interface. Currently, leads captured by each landing page are only accessible through the web dashboard of this application; there is no programmatic way for internal teams to consume this data.

This API gives an internal dashboard the ability to retrieve the full list of registered landing pages and to fetch all leads associated with any specific landing page — identified by its slug — in a structured JSON format. Every request is authenticated with a static Bearer token stored in the application's `.env` file, keeping the integration simple and dependency-free.

## Goals

- Enable an internal dashboard to consume landing page and lead data without requiring a browser session or human interaction.
- Deliver a read-only JSON API with exactly two endpoints at launch: one for listing landing pages, one for fetching leads by landing page slug.
- Ensure all requests are authenticated via a static Bearer token configured in the application environment, with no dependency on JWT or third-party auth libraries.
- Respond with consistent, envelope-wrapped JSON payloads that are easy to parse and extend.

## User Stories

### Internal Dashboard Developer

- As an internal dashboard developer, I want to call `GET /api/lp/list` so that I can display all registered landing pages in the dashboard without logging into the web app.
- As an internal dashboard developer, I want to call `POST /api/lp/leads/{slug}` so that I can retrieve all leads for a specific landing page and present them to the internal team.
- As an internal dashboard developer, I want to receive a `401 Unauthorized` response when no token or an incorrect token is provided so that I know immediately when the request is misconfigured.
- As an internal dashboard developer, I want to receive a `404 Not Found` response when a slug does not match any landing page so that I can handle missing resources gracefully in the dashboard UI.
- As an internal dashboard developer, I want all lead fields (`name`, `email`, `phone`, `message`, `status`, `created_at`) to be included in the response so that the dashboard can display complete lead information without making additional requests.

### System / Operations

- As a system operator, I want the API token to be configured exclusively in the `.env` file using CodeIgniter 4's native encryption tooling so that the token is never hardcoded in source code and can be rotated without a deployment.

## Core Features

### F1 — List Landing Pages (`GET /api/lp/list`)

Returns a JSON array of all registered landing pages, ordered by creation date (newest first). Each entry includes the landing page's `id`, `title`, `slug`, and `created_at` timestamp. The `file_path` field is excluded from the response as it is an internal implementation detail irrelevant to consumers.

**Behavior:**
- Returns `200 OK` with an envelope-wrapped array of landing page objects.
- Returns an empty `data` array (not an error) when no landing pages exist.
- Requires a valid Bearer token in the `Authorization` header.

### F2 — Get Leads by Landing Page Slug (`POST /api/lp/leads/{slug}`)

> **Note on HTTP method:** The endpoint uses `POST` as specified by the product owner. The endpoint reads by slug from the URL path and returns all associated leads.

Returns a JSON array of all leads associated with the landing page identified by `{slug}`. Each lead entry includes all available fields: `id`, `name`, `email`, `phone`, `message`, `status`, and `created_at`.

**Behavior:**
- Returns `200 OK` with an envelope-wrapped array of lead objects, ordered by `created_at` descending.
- Returns `404 Not Found` with an error message when the slug does not match any registered landing page.
- Returns an empty `data` array when the landing page exists but has no leads.
- Requires a valid Bearer token in the `Authorization` header.

### F3 — Bearer Token Authentication

All requests to `api/*` routes are rejected unless the `Authorization` header contains `Bearer <token>`, where `<token>` matches the value stored in `.env`. The token is generated using CodeIgniter 4's native encryption key facility and stored as an environment variable.

**Behavior:**
- Returns `401 Unauthorized` with a JSON error body when the header is absent, malformed, or the token does not match.
- The token value is never returned in any API response.

### F4 — Consistent JSON Envelope

Every API response — success or error — follows a uniform envelope structure:

```json
{
  "data": [],
  "meta": {},
  "errors": null
}
```

Success responses populate `data`; error responses populate `errors` and set `data` to `null`. The `meta` field is reserved for future use (e.g., pagination counts).

## User Experience

### Consumer Journey (Internal Dashboard Developer)

1. **Configuration**: The developer receives the Bearer token from a system operator (shared out-of-band). They configure it once in their dashboard environment or secrets manager.
2. **Discovery**: The developer calls `GET /api/lp/list` to retrieve all available landing pages and cache their slugs.
3. **Lead retrieval**: For each landing page of interest, the developer calls `POST /api/lp/leads/{slug}` to fetch the full lead list.
4. **Error handling**: The developer checks the HTTP status code first; a `401` means the token is wrong or missing, a `404` means the slug is invalid, a `200` with an empty `data` array means no leads yet.
5. **Token rotation**: When the operator rotates the token in `.env`, the developer updates their stored token and resumes normal operation — no code changes required.

### API Response Clarity

- HTTP status codes are the primary signal of success or failure — no need to parse the body to detect errors.
- Error responses include a human-readable `message` field to help developers diagnose issues quickly.
- Field names use `snake_case` consistently, matching the existing database schema and PHP conventions of this project.

## High-Level Technical Constraints

- Authentication must use a static Bearer token read from the application's `.env` file. JWT and any third-party authentication library are out of scope.
- The token must be generated using CodeIgniter 4's native encryption or key generation facilities — no custom random string generators.
- The API must reuse existing data models (`LandingPageModel`, `LeadModel`) without modifying them.
- All responses must be valid JSON with `Content-Type: application/json`.
- The API must be accessible at the `/api/lp/` path prefix without conflicting with existing web routes.

## Non-Goals (Out of Scope)

- **Lead creation via API** — The API is read-only; leads are captured through the existing public landing page form.
- **JWT or OAuth authentication** — Explicitly excluded; a static Bearer token is sufficient for an internal tool.
- **Pagination** — Not required in the MVP; all leads for a landing page are returned in a single response.
- **Write operations on landing pages** — Creation, update, and deletion of landing pages remain web-only operations.
- **Public access** — The API is for internal use only; no unauthenticated endpoints are planned.
- **Filtering or searching leads** — Query-based filtering (by status, date range, etc.) is deferred to a future phase.
- **API versioning** — Not required at launch given the single internal consumer.
- **Webhooks or push notifications** — Out of scope; the dashboard polls on demand.

## Phased Rollout Plan

### MVP (Phase 1) — Current Scope

- `GET /api/lp/list` returns all landing pages.
- `POST /api/lp/leads/{slug}` returns all leads for a landing page.
- Bearer token authentication on all `api/*` routes.
- Consistent JSON envelope on all responses.
- `401`, `404`, and `200` status codes handled correctly.

**Success criteria to proceed:** Internal dashboard successfully consumes both endpoints; no authentication bypass is possible with a missing or wrong token.

### Phase 2 — Filtering and Pagination

- Add optional query parameters to `POST /api/lp/leads/{slug}`: `status`, `date_from`, `date_to`, `limit`, `offset`.
- Return pagination metadata in the `meta` envelope field.
- Add `total_count` and `filtered_count` to `meta`.

**Success criteria to proceed:** Dashboard team requests filtering capability and the lead volume per landing page grows beyond a manageable single-response size.

### Phase 3 — Expanded Surface

- Additional endpoints as needed (e.g., single lead detail, lead status update).
- Token rotation endpoint or management UI.
- API usage logging and auditing.

## Success Metrics

- Both endpoints return correct data for all existing landing pages and leads within 500ms under normal load.
- Zero `401` errors from the legitimate internal dashboard consumer after initial setup.
- Zero unhandled server errors (`5xx`) on valid requests.
- Internal dashboard team confirms they can replace any existing manual data export process with API calls within the first week of deployment.

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Token shared insecurely (e.g., sent via unencrypted channel) | Medium | High | Document that the token must be shared via a secrets manager or encrypted channel; never via email or chat |
| Token not rotated after personnel changes | Medium | High | Establish a documented token rotation procedure; token rotation requires only a `.env` update and server reload |
| Internal dashboard makes excessive requests, impacting server load | Low | Medium | Apply the existing `ThrottleFilter` to `api/*` routes in Phase 2 if usage grows |
| Slug collision if a landing page is deleted and recreated with the same slug | Low | Low | Leads are always fetched by current slug; deleted landing pages return `404` — behavior is correct by design |

## Architecture Decision Records

- [ADR-001: Dedicated API Controller with Bearer Token Filter](adrs/adr-001.md) — Use a new isolated `Api\LpController` and a new `BearerTokenFilter` applied to `api/*`, reusing existing models unchanged.

## Open Questions

- **Token distribution process**: How will the Bearer token be securely communicated to the internal dashboard team? (Does not block MVP implementation — the token value is determined at deployment time.)
- **Response field for `file_path`**: Confirmed excluded from the landing page list response. If a consumer ever needs the public URL of a landing page, a `public_url` field derived from the slug should be considered in Phase 2.
