---
status: completed
title: Build authentication system (AuthController + UserModel + login view)
type: backend
complexity: medium
dependencies:
    - task_01
    - task_03
    - task_04
---

# Task 05: Build authentication system (AuthController + UserModel + login view)

## Overview

Implement the complete login/logout flow including the controller, model, and view. This task creates the entry point for all admin access: a secure login form that verifies credentials against the seeded admin user, creates a session on success, and destroys it on logout.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `UserModel` extending `CodeIgniter\Model` for the `users` table
- MUST create `AuthController` with `login()`, `authenticate()`, and `logout()` methods
- MUST create `app/Views/auth/login.php` extending `layouts/auth`
- MUST use `password_verify()` to validate the submitted password against the database hash
- MUST set `session()->set('isLoggedIn', true)` and `session()->set('user_id', $id)` on successful login
- MUST call `session()->regenerate(true)` after successful authentication to prevent session fixation
- MUST call `session()->destroy()` on logout and redirect to `/login`
- MUST redirect `/` (root) to `/login` since there is no public home page
- MUST show an error message on the login form for invalid credentials
</requirements>

## Subtasks
- [x] 05.1 Create `app/Models/UserModel.php` with `findByUsername()` method
- [x] 05.2 Create `app/Controllers/AuthController.php` with `login()`, `authenticate()`, `logout()`
- [x] 05.3 Create `app/Views/auth/login.php` with username/password form and error display
- [x] 05.4 Update `app/Config/Routes.php` to add `/login`, POST `/login`, POST `/logout`, and redirect `/` to `/login`
- [x] 05.5 Verify successful login redirects to `/dashboard` and creates session data
- [x] 05.6 Verify logout destroys the session and redirects to `/login`

## Implementation Details

See TechSpec "Data Models" for `UserModel` configuration and TechSpec "Development Sequencing — Build Order Step 3" for authentication implementation guidance. The `AuthController` extends `BaseController` and uses inline validation for the login form.

### Relevant Files
- `app/Models/UserModel.php` — New model for user lookup
- `app/Controllers/AuthController.php` — New authentication controller
- `app/Views/auth/login.php` — New login view extending auth layout
- `app/Config/Routes.php` — Must add auth routes
- `app/Controllers/Home.php` — Should be deprecated/redirected

### Dependent Files
- `app/Config/Filters.php` — `AuthFilter` and `ThrottleFilter` must already be registered (task_04)
- `app/Views/layouts/auth.php` — Login view extends this layout (task_03)
- `writable/database.db` — Must contain the seeded admin user (task_01)

### Related ADRs
- [ADR-004: SQLite with WAL Mode via Migration](adrs/adr-004.md) — Admin seeding with password_hash

## Deliverables
- `app/Models/UserModel.php`
- `app/Controllers/AuthController.php`
- `app/Views/auth/login.php`
- Updated `app/Config/Routes.php`
- Unit tests with 80%+ coverage (REQUIRED)
- Integration tests for login/logout flow (REQUIRED)

## Tests
- Unit tests:
  - [ ] `UserModel::findByUsername()` returns the correct user array for an existing username
  - [ ] `UserModel::findByUsername()` returns null for a non-existent username
  - [ ] `password_verify('123456', $hash)` is true for the seeded admin password
- Integration tests:
  - [ ] POST `/login` with valid credentials creates session and redirects to `/dashboard`
  - [ ] POST `/login` with invalid credentials shows error and does not create session
  - [ ] POST `/logout` destroys session and redirects to `/login`
  - [ ] GET `/` redirects to `/login`
  - [ ] GET `/login` while already authenticated redirects to `/dashboard`
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- Admin can log in with username `admin` and password `123456`
- Successful login redirects to `/dashboard` and sets `isLoggedIn` in session
- Logout destroys the session and redirects to `/login`
- Root URL `/` redirects to `/login`
