# TechSpec: Login + Dashboard with Landing Pages & Lead Management

## Executive Summary

This specification defines the technical implementation of a secure admin dashboard built on CodeIgniter 4.7.2. The system provides session-based authentication with rate-throttled logins, a dark-themed dashboard for landing page management, public landing pages with embedded lead capture forms, and a searchable, sortable lead table filtered by landing page source.

Key architectural decisions: (1) two dedicated layout files separate the minimal login experience from the full dashboard; (2) resource-oriented controllers isolate authentication, dashboard home, landing page CRUD, and lead management; (3) SQLite3 with WAL mode activated via migration provides a self-contained database with no external server; (4) the default admin account is seeded with a `password_hash()`-protected credential.

Primary trade-off: SQLite's simplicity and zero-config deployment versus limited horizontal scalability. This is acceptable for a single-admin MVP and can be migrated to a client-server database in a future phase without touching application logic.

## System Architecture

### Component Overview

| Component | Responsibility | Key Files |
|---|---|---|
| **AuthController** | Render login form, authenticate credentials, destroy session on logout | `app/Controllers/AuthController.php` |
| **DashboardController** | Render the admin home page (dashboard index) | `app/Controllers/DashboardController.php` |
| **LandingPagesController** | CRUD operations for landing pages; list, create, edit, delete | `app/Controllers/LandingPagesController.php` |
| **LeadsController** | List leads with search, sort, and landing-page source filter | `app/Controllers/LeadsController.php` |
| **PublicController** | Render public landing pages and handle lead form submissions | `app/Controllers/PublicController.php` |
| **AuthFilter** | Verify active session before allowing access to admin routes | `app/Filters/AuthFilter.php` |
| **ThrottleFilter** | Rate-limit login attempts using CI4's Throttler service | `app/Filters/ThrottleFilter.php` |
| **UserModel** | Query the `users` table for authentication | `app/Models/UserModel.php` |
| **LandingPageModel** | Query the `landing_pages` table | `app/Models/LandingPageModel.php` |
| **LeadModel** | Query the `leads` table with search and filter helpers | `app/Models/LeadModel.php` |
| **Auth Layout** | Minimal layout for the login page (centered card, no sidebar) | `app/Views/layouts/auth.php` |
| **Dashboard Layout** | Full layout with sidebar, top bar, and content area for admin views | `app/Views/layouts/dashboard.php` |
| **Sidebar Cell** | Reusable View Cell rendering the navigation sidebar | `app/Cells/SidebarCell.php` + `app/Views/cells/sidebar.php` |

### Data Flow

1. **Login**: Visitor submits credentials → `AuthController::authenticate()` → `UserModel::findByUsername()` → `password_verify()` → session created → redirect to `/dashboard`.
2. **Dashboard Access**: Request hits `/dashboard` → `AuthFilter::before()` verifies session → `DashboardController::index()` renders dashboard layout.
3. **Create Landing Page**: Admin submits form → `LandingPagesController::store()` validates input → `LandingPageModel::insert()` → redirect to landing page list.
4. **Public Landing Page**: Visitor accesses `/p/{slug}` → `PublicController::show()` fetches page by slug → renders public view with lead form.
5. **Lead Capture**: Visitor submits lead form → `PublicController::storeLead()` validates → `LeadModel::insert()` with `landing_page_id` → success message.
6. **Manage Leads**: Admin opens `/leads` → `AuthFilter` passes → `LeadsController::index()` queries `LeadModel` with optional search text and landing-page filter → renders table.

## Implementation Design

### Core Interfaces

The `AuthFilter` is the primary cross-cutting component that all admin routes depend on. It implements CI4's `FilterInterface`:

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $session = session();
        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
        // No post-processing required
    }
}
```

The `BaseController` is updated to preload services required by all controllers:

```php
<?php

namespace App\Controllers;

use CodeIgniter\Controller;

abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url'];

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->session = \Config\Services::session();
    }
}
```

### Data Models

#### Database Schema

**Table: `users`**
| Column | Type | Constraints |
|---|---|---|
| id | INTEGER | PRIMARY KEY, AUTOINCREMENT |
| username | VARCHAR(255) | NOT NULL, UNIQUE |
| password | VARCHAR(255) | NOT NULL |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |

**Table: `landing_pages`**
| Column | Type | Constraints |
|---|---|---|
| id | INTEGER | PRIMARY KEY, AUTOINCREMENT |
| title | VARCHAR(255) | NOT NULL |
| slug | VARCHAR(255) | NOT NULL, UNIQUE |
| content | TEXT | NOT NULL |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| updated_at | DATETIME | NULL |

**Table: `leads`**
| Column | Type | Constraints |
|---|---|---|
| id | INTEGER | PRIMARY KEY, AUTOINCREMENT |
| landing_page_id | INTEGER | NOT NULL, FOREIGN KEY landing_pages(id) |
| name | VARCHAR(255) | NOT NULL |
| email | VARCHAR(255) | NOT NULL |
| phone | VARCHAR(50) | NULL |
| message | TEXT | NULL |
| status | VARCHAR(50) | NOT NULL, DEFAULT 'New' |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP |

#### Model Definitions

Models extend `CodeIgniter\Model` with simple configurations (no Entity classes):

- `UserModel`: `$table = 'users'`, `$allowedFields = ['username', 'password']`, `$returnType = 'array'`.
- `LandingPageModel`: `$table = 'landing_pages'`, `$allowedFields = ['title', 'slug', 'content']`, `$returnType = 'array'`, uses `findBySlug()` custom method.
- `LeadModel`: `$table = 'leads'`, `$allowedFields = [...]`, `$returnType = 'array'`, provides `search($term)`, `filterByLandingPage($id)`, and `orderBy()` helpers.

### API Endpoints

All endpoints are server-rendered HTML (not JSON API). Routes are defined in `app/Config/Routes.php`.

| Method | Path | Controller::Method | Access | Description |
|---|---|---|---|---|
| GET | `/login` | `AuthController::login` | Public | Render login form |
| POST | `/login` | `AuthController::authenticate` | Public | Process login, create session |
| POST | `/logout` | `AuthController::logout` | Public | Destroy session, redirect to login |
| GET | `/dashboard` | `DashboardController::index` | Admin | Dashboard home |
| GET | `/landing-pages` | `LandingPagesController::index` | Admin | List all landing pages |
| GET | `/landing-pages/create` | `LandingPagesController::create` | Admin | Show create form |
| POST | `/landing-pages` | `LandingPagesController::store` | Admin | Store new landing page |
| GET | `/landing-pages/edit/(:num)` | `LandingPagesController::edit/$1` | Admin | Show edit form |
| POST | `/landing-pages/update/(:num)` | `LandingPagesController::update/$1` | Admin | Update landing page |
| GET | `/landing-pages/delete/(:num)` | `LandingPagesController::delete/$1` | Admin | Delete landing page |
| GET | `/leads` | `LeadsController::index` | Admin | List leads with optional search/filter |
| GET | `/p/(:any)` | `PublicController::show/$1` | Public | Render public landing page by slug |
| POST | `/p/(:any)/lead` | `PublicController::storeLead/$1` | Public | Submit lead from landing page |

**Auth protection**: Admin routes are protected by applying the `auth` filter to the route group in `Filters.php`.

**Rate limiting**: The `throttle` filter is applied to POST `/login` to prevent brute-force attempts.

## Integration Points

No external services or third-party APIs are integrated in the MVP. The application is fully self-contained.

## Impact Analysis

| Component | Impact Type | Description and Risk | Required Action |
|---|---|---|---|
| `BaseController` | Modified | Preload `session`, `form`, and `url` helpers for all controllers. Low risk. | Update `initController()` method. |
| `app/Config/Routes.php` | Modified | Add all new routes for auth, dashboard, landing pages, leads, and public pages. Low risk. | Replace existing single route with full route map. |
| `app/Config/Filters.php` | Modified | Register `auth` and `throttle` filter aliases; apply `auth` to admin route groups and `throttle` to POST login. Low risk. | Add aliases and route-based filter assignments. |
| `app/Config/Database.php` | Modified | Switch default connection from MySQLi to SQLite3 with file path in `writable/`. Low risk. | Uncomment and configure SQLite3 block. |
| `app/Config/Session.php` | No change | File-based sessions already configured and working. | None. |
| `app/Controllers/Home.php` | Deprecated | Default welcome controller no longer needed. | Remove or redirect `/` to `/login`. |
| `app/Views/welcome_message.php` | Deprecated | Default welcome view replaced by new layouts. | Remove. |
| `public/css/` | New | Create directory; add `style.css` for global dark theme styles. | Create directory and file. |
| `public/js/` | New | Create directory; add `app.js` for global JS utilities. | Create directory and file. |
| `writable/database.db` | New | SQLite database file created by migrations. | Ensure web server has write permissions to `writable/`. |

## Testing Approach

### Unit Tests

No unit tests are required for the MVP. CodeIgniter 4's model and validation behaviors are framework-guaranteed. The simplicity of the controllers (thin, delegating to models) means the value of isolated unit tests is low compared to integration tests.

### Integration Tests

Integration tests are written using CI4's `ControllerTestTrait` and test against an in-memory SQLite database (`:memory:`) configured in the `tests` database group.

**Key integration test scenarios:**
1. **Login flow**: Valid credentials create a session and redirect to `/dashboard`; invalid credentials show an error and do not create a session.
2. **Throttle**: More than 5 failed login attempts within 1 minute returns a 429 response.
3. **Auth filter**: Accessing `/dashboard` without a session redirects to `/login`.
4. **Landing page CRUD**: Create → verify in database → edit → verify update → delete → verify removal.
5. **Lead capture**: Submit lead form on public page → verify lead exists in database with correct `landing_page_id` and status "New."
6. **Lead filtering**: Create leads from two landing pages → filter by one page → verify only matching leads appear.

**Test data**: Use CI4's database seeders to populate test fixtures before each test run.

## Development Sequencing

### Build Order

1. **Database configuration and migration** — no dependencies
   - Configure SQLite3 in `Database.php`.
   - Create migration that runs `PRAGMA journal_mode=WAL;` and creates `users`, `landing_pages`, and `leads` tables.
   - Create `AdminSeeder` with hashed default password.
   - Run `spark migrate` and `spark db:seed AdminSeeder`.

2. **BaseController update and layouts** — depends on step 1
   - Update `BaseController` to preload session and helpers.
   - Create `public/css/style.css` and `public/js/app.js` with global dark theme.
   - Create `app/Views/layouts/auth.php` and `app/Views/layouts/dashboard.php`.

3. **Authentication system** — depends on step 2
   - Create `AuthFilter` and `ThrottleFilter`.
   - Register filters in `Filters.php`; apply `auth` to admin routes and `throttle` to POST login.
   - Create `AuthController` with `login()`, `authenticate()`, and `logout()`.
   - Create `UserModel`.
   - Create `app/Views/auth/login.php` extending `layouts/auth`.

4. **Landing page management** — depends on step 3
   - Create `LandingPageModel` and migration (if not already created in step 1).
   - Create `LandingPagesController` with index, create, store, edit, update, delete.
   - Create views: `landing_pages/index.php`, `landing_pages/create.php`, `landing_pages/edit.php` extending `layouts/dashboard`.
   - Add routes.

5. **Public landing pages and lead capture** — depends on step 4
   - Create `PublicController` with `show($slug)` and `storeLead($slug)`.
   - Create `LeadModel`.
   - Create `app/Views/public/landing_page.php` (simple, not using dashboard layout).
   - Add public routes (`/p/(:any)` and `/p/(:any)/lead`).

6. **Lead management table** — depends on step 5
   - Create `LeadsController::index()` with search, sort, and landing-page filter.
   - Create `app/Views/leads/index.php` extending `layouts/dashboard`.
   - Add route.

7. **Sidebar Cell and polish** — depends on steps 3–6
   - Create `SidebarCell` to render navigation sidebar.
   - Update `layouts/dashboard.php` to use the Sidebar Cell.
   - Add per-view CSS/JS sections where needed.

### Technical Dependencies

- PHP 8.2+ with `sqlite3` and `pdo_sqlite` extensions enabled.
- Web server write permissions on `writable/` directory (for database, sessions, and logs).
- CI4's built-in Throttler requires a cache handler other than `dummy`; file-based cache is sufficient for the MVP.

## Monitoring and Observability

The MVP does not include custom monitoring. Operational visibility is provided by:

- **CI4 Debug Toolbar**: Enabled in development environment; shows queries, request timeline, and session data.
- **Application logs**: Written to `writable/logs/` by CI4's Logger. Log level threshold is configurable in `app/Config/Logger.php`.
- **Login failure visibility**: Failed login attempts are logged at the `warning` level with the username and IP address for security review.

No custom metrics, alerts, or structured logging are required for the MVP.

## Technical Considerations

### Key Decisions

| Decision | Rationale | Trade-offs | Alternatives Rejected |
|---|---|---|---|
| SQLite3 with WAL mode | Zero external dependency; WAL activated once in migration. | Not horizontally scalable; sidecar files require careful backup. | MySQLi (requires external server); SQLite without WAL (poor concurrency). |
| Two separate layouts | Clean separation between auth and admin contexts. | Two files to maintain; shared head elements must be kept in sync. | Single layout with conditionals (muddies concerns); no layout inheritance (unmaintainable). |
| Resource-oriented controllers | Each controller has a single responsibility; maps cleanly to routes. | More files than a monolithic controller. | Monolithic `AdminController` (violates SRP); single `AppController` (unscalable). |
| Models without Entities | Faster to write; less boilerplate; arrays/stdClass are sufficient for simple CRUD. | No type-safe entity objects; business logic lives in controllers. | Full Entity classes (overkill for MVP scope). |
| Inline validation in controllers | Simple and direct; no indirection for small rule sets. | Rules are not reusable between create and edit if they differ. | Centralized validation config files (premature abstraction for MVP). |
| View Cells for sidebar | Encapsulates sidebar markup; reusable; easy to update independently. | Slightly more files than inline sidebar in layout. | Inline sidebar in layout (harder to maintain if logic grows). |

### Known Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| SQLite file becomes corrupted if WAL sidecar files are deleted | Low | Document that backups must include `.db`, `.db-wal`, and `.db-shm`. |
| Default admin password never changed in production | Medium | Add a prominent post-login notice reminding the user to change the password; document this in setup instructions. |
| Landing page slug collision | Low | Enforce unique constraint at database level and validate uniqueness in controller. |
| Session fixation if session ID is not regenerated after login | Low | Call `session()->regenerate(true)` in `AuthController::authenticate()` upon successful login. |
| CSRF token missing on forms | Low | Enable the `csrf` filter globally for POST requests in `Filters.php` after confirming it does not interfere with public lead forms (or apply it selectively to admin routes). |

## Architecture Decision Records

- [ADR-001: Minimal MVP Approach](adrs/adr-001.md) — Selected the Minimal MVP scope to prioritize time-to-publish and early validation.
- [ADR-002: Separated Layout Architecture](adrs/adr-002.md) — Chose dedicated `auth.php` and `dashboard.php` layouts over a shared conditional layout or no inheritance.
- [ADR-003: Resource-Oriented Controllers](adrs/adr-003.md) — Adopted one controller per domain (Auth, Dashboard, LandingPages, Leads, Public) instead of monolithic controllers.
- [ADR-004: SQLite with WAL Mode via Migration](adrs/adr-004.md) — Configured SQLite3 with WAL mode activated in migration and seeded the admin account with a `password_hash()`-protected credential.
