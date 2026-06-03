# LP Leads API — Technical Specification

## Executive Summary

This feature adds two read-only JSON endpoints to the existing CodeIgniter 4 application: `GET /api/lp/list` and `POST /api/lp/leads/{slug}`. Authentication is handled by a new `BearerTokenFilter` that validates the `Authorization` header against the `encryption.key` value already present in `.env`. A dedicated `App\Controllers\Api\LpController` serves both endpoints and reuses the existing `LandingPageModel` and `LeadModel` without modification.

The primary trade-off of this approach: the API Bearer token and the application's encryption key share the same value. This eliminates any additional setup but couples their rotation — rotating one rotates the other. For an internal dashboard with a single operator, this coupling is acceptable at MVP scale.

---

## System Architecture

### Component Overview

```
HTTP Request
    │
    ▼
[app/Config/Routes.php]  ←── adds api/* route group
    │
    ▼
[app/Filters/BearerTokenFilter.php]  ←── new file
    │  reads getenv('encryption.key')
    │  validates Authorization: Bearer <token>
    │  returns 401 JSON on failure
    ▼
[app/Controllers/Api/LpController.php]  ←── new file
    │
    ├── list()       → LandingPageModel::orderBy()->findAll()
    └── leads($slug) → LandingPageModel::findBySlug()
                       LeadModel::filterByLandingPage()->findAll()
    │
    ▼
JSON Envelope Response { data, meta, errors }
```

**Components:**

| Component | Type | Responsibility |
|---|---|---|
| `BearerTokenFilter` | New filter | Intercept all `api/*` requests; validate Bearer token; return `401` on failure |
| `Api\LpController` | New controller | Serve JSON responses for landing page list and leads-by-slug |
| `LandingPageModel` | Existing — unchanged | Query `landing_pages` table by slug or fetch all |
| `LeadModel` | Existing — unchanged | Query `leads` table filtered by `landing_page_id` |
| `app/Config/Routes.php` | Modified | Register `api/lp/list` and `api/lp/leads/(:segment)` with `bearerToken` filter |
| `app/Config/Filters.php` | Modified | Register `bearerToken` alias pointing to `BearerTokenFilter` |

---

## Implementation Design

### Core Interfaces

**`app/Filters/BearerTokenFilter.php`**

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class BearerTokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $expected = getenv('encryption.key');
        $header   = $request->getHeaderLine('Authorization');

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $m) || $m[1] !== $expected) {
            log_message('warning', '[api.auth.401] ip={ip} uri={uri}', [
                'ip'  => $request->getIPAddress(),
                'uri' => $request->getUri()->getPath(),
            ]);
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON(['data' => null, 'meta' => (object) [], 'errors' => 'Unauthorized']);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void {}
}
```

**`app/Controllers/Api/LpController.php`**

```php
<?php

namespace App\Controllers\Api;

use App\Models\LandingPageModel;
use App\Models\LeadModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class LpController extends Controller
{
    public function list(): ResponseInterface
    {
        $pages = (new LandingPageModel())
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $data = array_map(static fn($p) => [
            'id'         => $p['id'],
            'title'      => $p['title'],
            'slug'       => $p['slug'],
            'created_at' => $p['created_at'],
        ], $pages);

        return $this->response->setJSON(['data' => $data, 'meta' => (object) [], 'errors' => null]);
    }

    public function leads(string $slug): ResponseInterface
    {
        $page = (new LandingPageModel())->findBySlug($slug);

        if (! $page) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                ->setJSON(['data' => null, 'meta' => (object) [], 'errors' => 'Landing page not found']);
        }

        $leads = (new LeadModel())
            ->filterByLandingPage((int) $page['id'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->response->setJSON(['data' => $leads, 'meta' => (object) [], 'errors' => null]);
    }
}
```

---

### Data Models

**Existing models — no changes required.**

#### `LandingPageModel` (existing)

| Field | Type | Notes |
|---|---|---|
| `id` | int | Primary key |
| `title` | string | Landing page display name |
| `slug` | string | URL-safe unique identifier |
| `file_path` | string | **Excluded from API responses** |
| `created_at` | datetime | ISO 8601 |
| `updated_at` | datetime | Not returned by API |

#### `LeadModel` (existing)

| Field | Type | Notes |
|---|---|---|
| `id` | int | Primary key |
| `landing_page_id` | int | FK to `landing_pages.id` |
| `name` | string | |
| `email` | string | |
| `phone` | string | |
| `message` | text | |
| `status` | string | |
| `created_at` | datetime | ISO 8601 |

---

### API Endpoints

#### `GET /api/lp/list`

Returns all registered landing pages ordered by creation date descending.

**Request:**
```
GET /api/lp/list HTTP/1.1
Authorization: Bearer hex2bin:<value-from-encryption.key-in-env>
```

**Response `200 OK`:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Campanha Agosto",
      "slug": "campanha-agosto",
      "created_at": "2026-05-01 10:30:00"
    }
  ],
  "meta": {},
  "errors": null
}
```

**Response `401 Unauthorized`** (missing or invalid token):
```json
{
  "data": null,
  "meta": {},
  "errors": "Unauthorized"
}
```

---

#### `POST /api/lp/leads/{slug}`

Returns all leads for the landing page identified by `{slug}`, ordered by `created_at` descending.

**Request:**
```
POST /api/lp/leads/campanha-agosto HTTP/1.1
Authorization: Bearer hex2bin:<value-from-encryption.key-in-env>
```

**Response `200 OK`:**
```json
{
  "data": [
    {
      "id": 42,
      "landing_page_id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "phone": "11999990000",
      "message": "Quero saber mais.",
      "status": "new",
      "created_at": "2026-05-20 08:15:00"
    }
  ],
  "meta": {},
  "errors": null
}
```

**Response `404 Not Found`** (slug does not exist):
```json
{
  "data": null,
  "meta": {},
  "errors": "Landing page not found"
}
```

**Response `401 Unauthorized`** (missing or invalid token):
```json
{
  "data": null,
  "meta": {},
  "errors": "Unauthorized"
}
```

---

## Impact Analysis

| Component | Impact Type | Description and Risk | Required Action |
|---|---|---|---|
| `app/Filters/BearerTokenFilter.php` | New | New filter — no existing code affected | Create file |
| `app/Controllers/Api/LpController.php` | New | New controller in new subdirectory — no conflict with existing routing | Create file |
| `app/Config/Routes.php` | Modified | Two new routes + one route group added; existing routes unaffected | Add API route group after existing routes |
| `app/Config/Filters.php` | Modified | New `bearerToken` alias registered; no changes to existing aliases or filter assignments | Add alias entry |
| `app/Models/LandingPageModel.php` | Unchanged | Reused as-is; `findBySlug()` already exists | No action |
| `app/Models/LeadModel.php` | Unchanged | Reused as-is; `filterByLandingPage()` already exists | No action |
| `app/Controllers/BaseController.php` | Unchanged | `LpController` extends `CodeIgniter\Controller` directly, not `BaseController` | No action |

---

## Integration Points

### `.env` Token Source

The `BearerTokenFilter` reads `getenv('encryption.key')` at request time. This value is set by `php spark key:generate` during initial server setup. No additional configuration step is required.

**Token format in `.env`:**
```
encryption.key = hex2bin:a3f2c1b9d8e7f645...
```

**Token format in Authorization header (client sends the full value verbatim):**
```
Authorization: Bearer hex2bin:a3f2c1b9d8e7f645...
```

---

## Testing Approach

### Unit Tests

**`BearerTokenFilter`**

Test cases (using CI4's `FilterTestTrait` or direct instantiation with mocked request/response):

| Scenario | Expected Result |
|---|---|
| `Authorization` header absent | `401` JSON response; log entry written |
| `Authorization` header present, wrong token | `401` JSON response; log entry written |
| `Authorization` header present, correct token | `null` returned (request proceeds) |
| `encryption.key` not set in env | `401` JSON response (empty token never matches) |

**`Api\LpController::list()`**

| Scenario | Expected Result |
|---|---|
| Landing pages exist in DB | `200` with correct field subset (no `file_path`) |
| No landing pages exist | `200` with empty `data` array |

**`Api\LpController::leads($slug)`**

| Scenario | Expected Result |
|---|---|
| Slug exists, leads present | `200` with all lead fields |
| Slug exists, no leads | `200` with empty `data` array |
| Slug not found | `404` with `errors` message |

### Integration Tests

- Send a full HTTP request to `GET /api/lp/list` with a valid token against a test database seeded with at least one landing page; assert response shape and field values.
- Send a full HTTP request to `POST /api/lp/leads/{slug}` with a valid token; assert leads are returned in `created_at DESC` order.
- Send a request without token to any API route; assert `401` and verify no data leaks.

---

## Development Sequencing

### Build Order

1. **`app/Filters/BearerTokenFilter.php`** — no dependencies; self-contained filter that reads from `getenv`
2. **`app/Config/Filters.php`** — depends on step 1; register `'bearerToken' => BearerTokenFilter::class` in `$aliases`
3. **`app/Controllers/Api/LpController.php`** — depends on existing models (`LandingPageModel`, `LeadModel`); no other new dependency
4. **`app/Config/Routes.php`** — depends on steps 2 and 3; register the `api/*` route group with `['filter' => 'bearerToken']`
5. **Smoke test** — send a request with correct token to `GET /api/lp/list`; verify `200`; send without token, verify `401`

### Technical Dependencies

- `php spark key:generate` must have been run on the target environment so that `encryption.key` is set in `.env` before any request is made
- No new Composer packages required

---

## Monitoring and Observability

### Log Events

All logging uses CI4's native `log_message()`, writing to `writable/logs/`.

| Event | Level | Log Format |
|---|---|---|
| Invalid/missing Bearer token | `warning` | `[api.auth.401] ip={ip} uri={uri}` |
| Unhandled exception in controller | `error` | CI4 default exception logging |

No additional metrics infrastructure is required at MVP scale.

---

## Technical Considerations

### Key Decisions

**Extending `CodeIgniter\Controller` instead of `BaseController`**

`BaseController` loads the `form` and `url` helpers and initializes a session service — none of which are needed by a stateless JSON API. `LpController` extends `CodeIgniter\Controller` directly to avoid unnecessary initialization overhead and to keep the API controller's dependencies explicit.

**`filterByLandingPage()` returns a builder, not results**

`LeadModel::filterByLandingPage()` applies a `where()` clause and returns `$this` (the model/builder), allowing method chaining with `orderBy()->findAll()`. The controller chains on top of it without instantiating any intermediate object.

**Empty `data` array vs. `404` for landing page with no leads**

When a landing page exists but has no leads, the response is `200` with `"data": []`. A `404` would be incorrect — the resource (the landing page) was found; it simply has no associated leads yet.

### Known Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| `php spark key:generate` run again post-deploy, breaking all API clients | Low | Document in deployment runbook that `key:generate` is a one-time setup step; add to `.env.example` with a placeholder |
| `encryption.key` absent from `.env` on a fresh deploy | Low | Filter returns `401` (empty string never matches); server startup checklist should verify key presence |

---

## Architecture Decision Records

- [ADR-001: Dedicated API Controller with Bearer Token Filter](adrs/adr-001.md) — Isolated `Api\LpController` + new `BearerTokenFilter` on `api/*`; existing models unchanged
- [ADR-002: Reuse `encryption.key` as the API Bearer Token](adrs/adr-002.md) — Single credential from `.env` via `getenv('encryption.key')`; zero extra configuration
- [ADR-003: API Controller in `app/Controllers/Api/` Subdirectory](adrs/adr-003.md) — Namespace `App\Controllers\Api`; clean separation from web controllers via subdirectory
