---
status: completed
title: Configure SQLite3 database with WAL mode, migrations, and admin seed
type: infra
complexity: medium
dependencies: []
---

# Task 01: Configure SQLite3 database with WAL mode, migrations, and admin seed

## Overview

Set up the self-contained SQLite3 database as the default connection, enable WAL mode for optimized read/write concurrency, and create the initial schema with migrations. Seed the default admin user with a bcrypt-hashed password so the application works out of the box with zero external database configuration.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST configure SQLite3 as the default database driver in `app/Config/Database.php`
- MUST set the database file path to `WRITEPATH . 'database.db'`
- MUST create a migration that runs `PRAGMA journal_mode=WAL;` and creates the `users`, `landing_pages`, and `leads` tables with correct schema
- MUST create `AdminSeeder` that inserts one user with `password_hash('123456', PASSWORD_DEFAULT)`
- MUST ensure the migration enables WAL mode only once at database creation time
- MUST ensure `username` on `users` table is unique
- MUST ensure `slug` on `landing_pages` table is unique
- MUST ensure `landing_page_id` on `leads` table has a foreign key reference to `landing_pages(id)`
</requirements>

## Subtasks
- [x] 01.1 Configure `Database.php` to use SQLite3 with file path in `writable/`
- [x] 01.2 Create migration file that enables WAL mode and creates `users` table
- [x] 01.3 Extend migration to create `landing_pages` table with correct fields and constraints
- [x] 01.4 Extend migration to create `leads` table with correct fields, foreign key, and default status
- [x] 01.5 Create `AdminSeeder` that inserts the default admin user with hashed password
- [x] 01.6 Run `spark migrate` and `spark db:seed AdminSeeder` to verify setup

## Implementation Details

See TechSpec "Data Models" and "Development Sequencing — Build Order Step 1" for schema definitions and migration patterns.

### Relevant Files
- `app/Config/Database.php` — Must switch default driver to SQLite3
- `app/Database/Migrations/` — Location for new migration file
- `app/Database/Seeds/` — Location for `AdminSeeder`
- `writable/` — Must be writable by the web server for the database file

### Dependent Files
- `app/Config/Database.php` — Driver and connection settings changed
- `writable/database.db` — New SQLite file created by migration
- `writable/database.db-wal` / `writable/database.db-shm` — WAL sidecar files created automatically

### Related ADRs
- [ADR-004: SQLite with WAL Mode via Migration](adrs/adr-004.md) — Database approach and admin seeding decision

## Deliverables
- `app/Config/Database.php` updated with SQLite3 configuration
- Migration file in `app/Database/Migrations/` creating all three tables and enabling WAL
- `AdminSeeder` in `app/Database/Seeds/AdminSeeder.php`
- Verified database file in `writable/database.db` with seeded admin user
- Unit tests with 80%+ coverage (REQUIRED)
- Integration tests for database setup (REQUIRED)

## Tests
- Unit tests:
  - [ ] `AdminSeeder` inserts exactly one user with non-empty hashed password
  - [ ] Migration creates `users`, `landing_pages`, and `leads` tables
  - [ ] `landing_pages.slug` column enforces uniqueness
  - [ ] `leads.status` defaults to "New"
- Integration tests:
  - [ ] Running `spark migrate` succeeds without errors and creates tables
  - [ ] Running `spark db:seed AdminSeeder` inserts the admin user
  - [ ] `password_verify('123456', $hash)` returns true for the seeded user
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- `php spark migrate` runs without errors
- `php spark db:seed AdminSeeder` runs without errors and creates one admin user
- SQLite database file exists in `writable/` with WAL mode active (verified via `PRAGMA journal_mode;`)
