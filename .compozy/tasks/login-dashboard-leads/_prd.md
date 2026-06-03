# PRD: Login + Dashboard with Landing Pages & Lead Management

## Overview

A secure, dark-themed admin dashboard that enables a single business owner to rapidly create landing pages, publish them to public URLs, and manage the leads they capture — all through a unified login-protected interface. The product solves the friction of stitching together separate tools for page creation, hosting, and lead collection by combining them into one fast, self-contained workflow.

## Goals

- Enable an admin to go from login to a live, lead-collecting landing page in under two minutes.
- Provide a single view of all captured leads with clear status tracking.
- Protect all admin routes with session-based authentication and login throttling.
- Deliver a cohesive dark-dashboard experience consistent with the established design system.
- Target: functional MVP ready for daily use within one development cycle.

## User Stories

### Primary Persona: Solo Business Owner / Admin

- As an admin, I want to log in with a username and password so that I can access my dashboard securely.
- As an admin, I want the system to slow down repeated failed login attempts so that my account is protected from brute-force attacks.
- As an admin, I want to create a new landing page with a title, content, and custom URL slug so that I can publish a page quickly without technical help.
- As an admin, I want to see a list of all my landing pages with their URLs so that I can manage and share them.
- As an admin, I want to edit an existing landing page so that I can update its content or fix mistakes.
- As an admin, I want every landing page to include a contact form that stores submissions automatically so that I can collect leads without extra integrations.
- As an admin, I want to view all captured leads in a table with sort, search, and status filters so that I can prioritize follow-ups.
- As an admin, I want lead statuses (New, Contacted, Qualified, Converted) so that I can track where each lead is in my pipeline.

## Core Features

### 1. Secure Authentication
- Login form with username and password.
- Session-based access control: all dashboard pages require an active session.
- Rate throttling on login attempts to prevent brute-force abuse.
- Logout capability that terminates the session and redirects to the login screen.

### 2. Landing Page Manager
- **List view**: Display all landing pages with name, slug/URL, and creation date.
- **Create page**: Form to input page title, body content, and custom slug. The page is live immediately upon creation.
- **Edit page**: Modify title, content, or slug for an existing page.
- **Public page**: Each slug maps to a publicly accessible URL that renders the page content and includes a lead capture form.

### 3. Lead Capture Form
- Embedded on every public landing page.
- Fields: Name, Email, Phone, Message.
- On submission, the lead is stored and associated with the originating landing page.
- Simple success confirmation for the visitor after submission.

### 4. Lead Management Table
- **Columns**: Name, Email, Phone, Source (landing page slug), Status, Date Captured.
- **Search**: Free-text search across name and email.
- **Sort**: Clickable column headers for ordering.
- **Filter**: Dropdown to show only leads belonging to a specific landing page (source filtering).
- Default status for new submissions is "New."

## User Experience

### Primary Flow: Publish a Landing Page
1. Admin navigates to the login page and enters credentials.
2. Upon successful authentication, the admin is redirected to the dashboard.
3. The dashboard displays a sidebar with two links: "Landing Pages" and "Manage Leads."
4. Admin clicks "Landing Pages," then "Create New Page."
5. Admin fills in title, content, and custom slug, then saves.
6. The system confirms the page is live and displays the public URL.
7. Admin shares the URL; visitors fill out the contact form.
8. Admin returns to the dashboard, opens "Manage Leads," and sees the new submission with status "New."
9. Admin uses the landing page filter to review leads from a specific campaign and begins follow-up.

### Onboarding
- The very first admin account is pre-seeded, so there is no public registration flow.
- The login page is the sole entry point.

### UI/UX Considerations
- The admin dashboard follows a dark-canvas theme to reduce eye strain during extended use.
- The yellow accent color is reserved for primary actions (Create Page, Save, Login) to maintain strong visual hierarchy.
- Cards and tables sit on elevated dark surfaces with subtle hairline borders for separation.
- Touch targets meet accessibility minimums; the layout adapts to desktop and tablet widths.

## High-Level Technical Constraints

- The application must run on a standard PHP hosting environment without requiring additional server software beyond a web server and PHP.
- Database must be file-based and self-contained so the product works out of the box with no external database server configuration.
- Session state must be server-side and invalidated on logout.
- All admin routes must reject unauthenticated requests before any business logic executes.
- Public landing pages must remain accessible without authentication.
- Prefer built-in framework capabilities over custom solutions to reduce maintenance and leverage native security patterns.

## Non-Goals (Out of Scope)

- User registration or multiple admin accounts.
- Role-based access control or permissions.
- Rich text/WYSIWYG editor for landing page content (plain text or simple markup only in MVP).
- Landing page preview before publishing.
- Publish/unpublish toggle for landing pages.
- Inline editing of lead records.
- Dashboard statistics, charts, or analytics.
- Email notifications on new lead submission.
- Export of leads to CSV or other formats.
- Integration with external CRMs or marketing tools.
- Image or media uploads for landing pages.

## Phased Rollout Plan

### MVP (Phase 1)
- Secure login with session protection and throttling.
- Admin dashboard with sidebar navigation (Landing Pages, Manage Leads).
- Landing page list, creation, and editing.
- Auto-generated public URLs with embedded lead capture forms.
- Lead table with search, sort, and landing page source filter.
- Default lead status workflow (New, Contacted, Qualified, Converted).
- Dark-themed admin UI following the established design system.

**Success criteria to proceed to Phase 2:**
- Admin can create a landing page and receive the first captured lead within the same session.
- No critical security or usability issues reported in daily use.
- Lead filtering by landing page source is verified to work with multiple pages.

### Phase 2
- Landing page preview before publishing.
- Publish/unpublish toggle.
- Inline lead editing and notes.
- Dashboard home with summary stats (total leads, conversion count, recent activity).
- Mobile-responsive refinements for the admin interface.

### Phase 3
- Rich text editor for landing page content.
- Image and media uploads.
- Email notifications for new leads.
- Lead export (CSV).
- Analytics on landing page views and conversion rates.

## Success Metrics

- **Time-to-publish**: Median time from login to a live landing page collecting leads is under two minutes.
- **Lead capture reliability**: 100% of valid form submissions appear in the lead table within five seconds.
- **Login security**: Zero successful brute-force logins during monitored usage; throttling activates as expected.
- **Dashboard usability**: Admin can locate a specific lead via search or filter in under ten seconds.

## Risks and Mitigations

- **Adoption risk**: The admin may find plain-text landing pages too limited compared to established builders.  
  *Mitigation*: Keep page creation extremely fast and focused; use Phase 2 to add a preview and richer editing based on direct feedback.
- **Lead volume overwhelm**: If landing pages generate many leads, the table may feel crowded without bulk actions.  
  *Mitigation*: Implement robust search and status filtering in MVP so the admin can slice the list effectively.
- **Security perception**: A single seeded admin account with a default password could be seen as risky.  
  *Mitigation*: Document that the default password must be changed on first use; enforce session expiration.
- **Scope creep**: Technical details (e.g., WAL mode, Cells, renderSection) could pull focus toward engineering elegance over user outcomes.  
  *Mitigation*: Track only user-facing outcomes in this PRD; defer implementation specifics to the TechSpec.

## Architecture Decision Records

- [ADR-001: Minimal MVP Approach](adrs/adr-001.md) — Selected the Minimal MVP scope over a richer first release or public-first approach to prioritize time-to-publish and early validation.

## Open Questions

- Should the default admin password require a forced change on first login, or is documentation sufficient?
- What is the exact plain-text formatting allowed in landing page content (line breaks only, or a small subset of safe HTML)?
- Should failed login attempts be logged visibly to the admin for security review?
- Is there a preferred maximum length for landing page slugs to keep URLs readable?
