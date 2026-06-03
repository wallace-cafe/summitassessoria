# Login + Dashboard with Landing Pages & Lead Management — Task List

## Tasks

| # | Title | Status | Complexity | Dependencies |
|---|-------|--------|------------|--------------|
| 01 | Configure SQLite3 database with WAL mode, migrations, and admin seed | completed | medium | — |
| 02 | Update BaseController and create global CSS/JS assets | completed | low | — |
| 03 | Create auth and dashboard layout system | completed | medium | task_02 |
| 04 | Implement AuthFilter, ThrottleFilter, and filter registration | completed | medium | — |
| 05 | Build authentication system (AuthController + UserModel + login view) | completed | medium | task_01, task_03, task_04 |
| 06 | Implement landing page CRUD (LandingPagesController + Model + Views) | completed | medium | task_01, task_03, task_05 |
| 07 | Build public landing page rendering and lead capture | completed | medium | task_01, task_06 |
| 08 | Implement lead management table (LeadsController + View) | completed | medium | task_01, task_03, task_07 |
| 09 | Configure all application routes and final integration | completed | low | task_04, task_05, task_06, task_07, task_08 |
| 10 | Write integration tests for authentication, landing pages, and leads | completed | medium | task_05, task_06, task_07, task_08 |
