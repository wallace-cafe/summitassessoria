---
status: completed
title: Create auth and dashboard layout system
type: frontend
complexity: medium
dependencies:
    - task_02
---

# Task 03: Create auth and dashboard layout system

## Overview

Build the two primary layout templates that define the visual structure of the application: a minimal centered layout for the login page and a full dashboard layout with a persistent sidebar for all admin views. Also create a reusable View Cell for the sidebar navigation to keep layout markup clean and maintainable.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST create `app/Views/layouts/auth.php` with a centered card, dark canvas background, and no sidebar
- MUST create `app/Views/layouts/dashboard.php` with a sidebar, top bar, content area, and sections for `css` and `js` injection
- MUST create `app/Cells/SidebarCell.php` and `app/Views/cells/sidebar.php` for reusable sidebar navigation
- MUST use CI4's `extend()`, `section()`, and `renderSection()` mechanisms in both layouts
- MUST reference `public/css/style.css` and `public/js/app.js` in the dashboard layout
- MUST design the sidebar to show links for "Landing Pages" and "Manage Leads" with active-state highlighting
</requirements>

## Subtasks
- [x] 03.1 Create `app/Views/layouts/auth.php` with centered login card and dark theme
- [x] 03.2 Create `app/Views/layouts/dashboard.php` with sidebar, top bar, content area, and CSS/JS sections
- [x] 03.3 Create `SidebarCell` class and `app/Cells/sidebar.php` view
- [x] 03.4 Add active-state logic to sidebar links based on current route
- [x] 03.5 Verify both layouts render correctly with a simple test view

## Implementation Details

See TechSpec "System Architecture — Component Overview" for layout and SidebarCell definitions, and TechSpec "Development Sequencing — Build Order Step 2" for layout creation guidance. Reference DESIGN.md for dark theme tokens (canvas-dark `#0b0e11`, primary `#FCD535`, surface-card-dark `#1e2329`, hairline-on-dark `#2b3139`).

### Relevant Files
- `app/Views/layouts/auth.php` — New minimal auth layout
- `app/Views/layouts/dashboard.php` — New full dashboard layout
- `app/Cells/SidebarCell.php` — New View Cell class
- `app/Views/cells/sidebar.php` — New sidebar Cell view
- `public/css/style.css` — Global styles (from task_02)

### Dependent Files
- All auth views — Will extend `layouts/auth`
- All admin views — Will extend `layouts/dashboard`
- `DashboardController`, `LandingPagesController`, `LeadsController` — Will render views using these layouts

### Related ADRs
- [ADR-002: Separated Layout Architecture](adrs/adr-002.md) — Layout separation decision

## Deliverables
- `app/Views/layouts/auth.php`
- `app/Views/layouts/dashboard.php`
- `app/Cells/SidebarCell.php`
- `app/Views/cells/sidebar.php`
- Unit tests with 80%+ coverage (REQUIRED)
- Integration tests for layout rendering (REQUIRED)

## Tests
- Unit tests:
  - [ ] SidebarCell renders navigation links correctly
  - [ ] SidebarCell highlights the active link based on input parameter
- Integration tests:
  - [ ] A test view extending `layouts/auth.php` renders without errors
  - [ ] A test view extending `layouts/dashboard.php` renders without errors and includes the sidebar
  - [ ] CSS/JS sections injected from a child view appear in the final rendered dashboard layout
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- Both layouts render without PHP errors
- Sidebar Cell displays "Landing Pages" and "Manage Leads" links
- Dashboard layout includes per-view CSS/JS injection via `renderSection()`
