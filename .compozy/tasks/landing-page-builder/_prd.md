# Landing Page Builder — File Upload Refactoring

## Overview

The Landing Page Builder is being refactored from a block composition system to a file upload system. Instead of assembling pages from reusable HTML blocks with `%TOKEN%` placeholders, users now upload a complete landing page package — `index.html`, `style.css`, `app.js`, and accompanying images — as a single project. The system stores these files on disk, serves them at the public URL `/p/{slug}`, and allows administrators to edit text content within comment-delimited sections of the HTML.

This shift is driven by a change in the user workflow: external designers and agencies now produce complete landing pages and deliver them to law firm administrators for publishing. The old block/token system was too rigid for full HTML/CSS/JS creative freedom. The new system treats the designer's output as the source of truth, detects the lead capture form already present in the HTML, links it to the landing page via a hidden field, and provides administrators with a simple text-based editing interface for updating copy without touching code.

The refactoring replaces the existing implementation entirely. Previous landing pages created with the block system are not migrated.

---

## Goals

- Enable external designers and agencies to deliver complete, custom-branded landing pages as a package of HTML, CSS, JS, and image files — no block template constraints.
- Reduce the administrator's publishing workflow to a single upload action, with lead capture and public URL generation handled automatically.
- Provide administrators with a simple per-block text editor so they can update copy (headlines, body text, CTAs) without touching HTML or re-engaging the designer.
- Ensure every lead submitted through a published page is automatically linked to its landing page by detecting the existing `<form id="lead-form">` and injecting a hidden page ID field.
- Replace the entire old block-based system with a clean slate. No backward compatibility with existing block-style pages.

---

## User Stories

### Primary Persona: Law Firm Administrator

- As an administrator, I want to upload a landing page package (HTML, CSS, JS, images) delivered by our design agency so that I can publish it in one step.
- As an administrator, I want every lead submitted through the page to be automatically linked to that landing page so that I can track which campaign generated the lead.
- As an administrator, I want to see a list of all published landing pages so that I can manage them from the dashboard.
- As an administrator, I want to edit the text content of specific sections on a page — without understanding HTML — so that I can update campaign copy quickly.
- As an administrator, I want changes to go live immediately after saving so that urgent updates are not delayed.
- As an administrator, I want to delete a landing page so that I can remove outdated campaigns.

### Secondary Persona: External Designer / Agency

- As a designer, I want to build a landing page using any HTML, CSS, and JS I choose so that I am not constrained by a template system.
- As a designer, I want to include images with relative paths so that the page works as a self-contained project.
- As a designer, I want to mark editable text sections with `<!-- BLOCO_N_INICIO -->` / `<!-- BLOCO_N_FIM -->` comment delimiters so that my client (the administrator) can update copy without breaking the layout.
- As a designer, I want to include a `<form id="lead-form">` in the HTML with my own fields and styling so that the lead capture looks exactly as designed, and the system automatically links submissions to the landing page.

---

## Core Features

### 1. Upload Landing Page Package (Priority: P0)

The administrator uploads a complete landing page as individual files via a form in the dashboard.

- **Accepted files**: `index.html`, `style.css`, `app.js`, and any number of image files (`.jpg`, `.png`, `.webp`, `.svg`, `.gif`). Additional file types are rejected.
- **Upload mechanism**: Individual file inputs for each required file type, plus a multi-file image uploader.
- **Validation**: `index.html` is required. All files are validated for allowed extensions and reasonable size limits per file (e.g., 5MB per image, 1MB per HTML/CSS/JS).
- **Processing**: Uploaded files are stored in a directory named after the page slug on the server filesystem, preserving the relative paths used during upload.
- **Result**: A new record is created in the `landing_pages` table with `title`, `slug`, and a reference to the filesystem path. The page is immediately live at `/p/{slug}`.

### 2. File Storage and Serving (Priority: P0)

Uploaded files are stored on disk and served to visitors via a controlled PHP endpoint.

- **Storage location**: Files are stored in `writable/landing_pages/{slug}/` — outside the webroot — with subdirectories preserved as uploaded.
- **Serving mechanism**: The public controller (`PublicController::show($slug)`) reads `index.html` from the page's directory, performs lead form injection, and outputs the assembled HTML. CSS, JS, and image files are served via a dedicated file-serving endpoint that maps the relative request path to the correct file on disk.
- **Lead form linkage**: The system scans the HTML for `<form id="lead-form">`. If found, it injects a hidden `<input type="hidden" name="landing_page_id" value="{page_id}">` inside the form. The form's `action` attribute must point to `/p/{slug}/lead` (the existing lead capture endpoint). If no form with that ID is found, the page is published without lead capture linkage.
- **Page deletion**: Deleting a landing page removes its directory and all files from disk.

### 3. Block-Based Text Editing (Priority: P0)

Administrators edit the text content of landing page sections without touching HTML.

- **Block detection**: The system parses `index.html` for `<!-- BLOCO_N_INICIO -->` and `<!-- BLOCO_N_FIM -->` comment pairs (where `N` is a sequential number: 1, 2, 3...). Each pair defines one editable block.
- **Block display**: The editor view lists all detected blocks. Each block is labeled `"Bloco N"` and shows a text area pre-filled with the inner text content of that block (text nodes only, not HTML tags).
- **Editing**: The administrator edits the text in each block's text area.
- **Saving**: On save, the system rewrites each block's content in the `index.html` file, replacing only the text between the opening and closing delimiters. All HTML structure, classes, attributes, and surrounding markup are preserved.
- **No blocks defined**: If the HTML contains no comment delimiters, the editor shows a message explaining that the page has no editable blocks and provides a fallback raw HTML textarea for the full file.

### 4. Landing Page Management (Priority: P0)

Standard CRUD operations for landing pages in the dashboard.

- **List view**: A table showing all published landing pages with title, slug, public URL, and created date. Actions: Edit, View Public Page, Delete.
- **Edit**: Opens the block editor view described in Feature 3.
- **Delete**: Removes the landing page record and all associated files from disk. Confirmation dialog required.

### 5. Public Landing Page with Lead Capture (Priority: P0)

The public-facing page displays the uploaded design. Lead capture is handled by the form already present in the uploaded HTML, with automatic page linkage.

- **URL structure**: Published pages are served at `/p/{slug}`.
- **Content**: The page renders the `index.html` with uploaded CSS, JS, and images. The system scans for `<form id="lead-form">` and injects a hidden `landing_page_id` input so submissions are linked to the correct page.
- **Lead form**: The designer defines the form fields, styling, and layout. The form must POST to `/p/{slug}/lead`. The server validates name (required) and email (required, valid format), stores the lead in the `leads` table with the landing page reference, and redirects back with a success message.
- **No dashboard chrome**: The public page is fully standalone — no dashboard header, sidebar, or branding appears.

---

## User Experience

### Primary User Flow: Publishing a Landing Page (Administrator)

1. Administrator navigates to **Landing Pages → New Page** (`/landing-pages/create`).
2. The upload form appears with file inputs: `index.html` (required), `style.css`, `app.js`, and multiple image files.
3. Administrator fills in the page title. The slug is auto-generated from the title but can be edited.
4. Administrator selects and uploads the files from the designer's delivery package.
5. Administrator clicks "Save". The system validates the files, stores them on disk, scans the HTML for `<form id="lead-form">` and injects a hidden `landing_page_id` field, creates the DB record, and redirects to the landing pages list with a success message.
6. The page is immediately live at `/p/{slug}`.

### Primary User Flow: Editing Page Content (Administrator)

1. Administrator clicks "Edit" on a landing page in the list view.
2. The editor loads the `index.html`, parses block delimiters, and displays each block as a labeled text area.
3. Administrator updates the text in one or more blocks.
4. Administrator clicks "Save". The system rewrites the changed sections in `index.html`.
5. The changes are immediately live at `/p/{slug}`. No explicit publish step.

### Primary User Flow: Viewing a Public Page (Visitor)

1. Visitor navigates to `/p/{slug}`.
2. The page renders the uploaded HTML with the designer's lead form. A hidden `landing_page_id` field was injected server-side linking the form to this page.
3. CSS and JS assets load from the uploaded files.
4. Visitor fills in the designer's lead form and submits. The page ID is sent with the submission.
5. Data is stored in the system linked to the correct landing page. Visitor sees a success message.

### UI Layout — Upload Form

```
┌─────────────────────────────────────────────────────────┐
│  New Landing Page                                        │
│                                                          │
│  Title:        [________________________________]        │
│  Slug:         [________________________________]        │
│                                                          │
│  Files:                                                  │
│  index.html:   [Choose File]  (required)                 │
│  style.css:    [Choose File]  (.css, optional)           │
│  app.js:       [Choose File]  (.js, optional)            │
│  Images:       [Choose Files] (.jpg, .png, .webp, etc.) │
│                                                          │
│  [Save Page]                                             │
└─────────────────────────────────────────────────────────┘
```

### UI Layout — Block Editor

```
┌─────────────────────────────────────────────────────────┐
│  Edit: Campanha Trabalhista                              │
│                                                          │
│  Bloco 1 — Hero Section                                  │
│  ┌─────────────────────────────────────────────────────┐│
│  │ [Text content of the hero block goes here...]       ││
│  │                                                     ││
│  └─────────────────────────────────────────────────────┘│
│                                                          │
│  Bloco 2 — Services                                      │
│  ┌─────────────────────────────────────────────────────┐│
│  │ [Text content of the services block goes here...]   ││
│  │                                                     ││
│  └─────────────────────────────────────────────────────┘│
│                                                          │
│  [Save Changes]   [Cancel]   [View Public Page]          │
└─────────────────────────────────────────────────────────┘
```

---

## High-Level Technical Constraints

- All uploaded files must be stored outside the webroot for security and served via a controlled PHP endpoint that validates the request.
- The system must detect `<form id="lead-form">` in the uploaded HTML at save time and inject a hidden `landing_page_id` field server-side. The form's design, fields, and styling belong to the designer's HTML — the system only adds the linkage field.
- File validation must happen server-side (MIME type, extension, size limits) — client-side checks are supplementary only.
- The system must handle cleanup of files on disk when a landing page is deleted.
- The public page must be fully functional with the dashboard offline — no dependency on dashboard assets or session state.
- Uploaded JavaScript files are served as-is. No sanitization is applied to JS (administrators and their designers are trusted content authors).

---

## Non-Goals (Out of Scope)

- **Block template library**: The old block template system is removed entirely. No reusable block creation, editing, or library features.
- **Token substitution**: The `%TOKEN%` system is removed. Text is edited directly in blocks, not via named placeholders.
- **Visual page composer**: No drag-and-drop, no live iframe preview, no block reordering within the dashboard.
- **CSS editor**: Each page's CSS comes from the uploaded file. No in-dashboard CSS editing.
- **Hero block as a special concept**: There is no system-level Hero block. The uploaded HTML's structure is the page structure.
- **Draft/publish workflow**: Saves go live immediately. No staging or preview mode in MVP.
- **Version history**: No revision tracking, undo, or file versioning.
- **ZIP upload**: Files are uploaded individually. Bulk ZIP upload is a Phase 2 enhancement.
- **Image management**: No image library, no cropping, no CDN integration. Images are stored as uploaded.
- **SEO metadata editor**: Title and slug are the only metadata.
- **A/B testing or analytics**: Out of scope entirely.
- **Collaborative editing**: Single-user editing only.
- **Export or download page package**: No export feature in MVP.

---

## Phased Rollout Plan

### MVP (Phase 1)

**Goal**: Replace the block-based system with file upload + block text editing end-to-end.

Core features:
- Upload form for index.html, style.css, app.js, and image files
- File storage on disk per landing page slug
- Public page serving with lead form injection (`<!-- LEAD_FORM -->` tag)
- Block detection via `<!-- BLOCO_N_INICIO/FIM -->` comment delimiters
- Per-block text editor with save → rewrite HTML on disk
- Landing page CRUD (create, list, edit, delete) with file cleanup on delete
- Lead capture: name, email, phone, message fields stored in leads table
- Remove old block_templates table, blocks/custom_css columns from landing_pages, page-builder.js, and composer views

Success criteria to proceed to Phase 2:
- An administrator can upload a designer-delivered package and see the page live at `/p/{slug}` with lead form working.
- An administrator can edit block text and see the updated copy on the live page immediately.
- A visitor can submit a lead through the injected form and the lead appears in the dashboard.
- Old block-based landing pages are removed without errors.

### Phase 2

Additional features:
- ZIP file upload (one file containing the full project structure)
- Drag-and-drop file uploader with progress indication
- "Duplicate page" action (copies files and creates a new record)
- Block label customization: designers can set custom labels via `<!-- BLOCO_NOME: Meu Bloco -->` syntax
- Image replacement within the dashboard (re-upload a single image without re-uploading the full page)

Success criteria:
- User satisfaction with upload speed — target: package upload and publish under 30 seconds.

### Phase 3

Full feature set:
- Page statistics view (visits, lead conversions per page)
- SEO metadata fields (meta description, og:image, custom title tag)
- Preview before publish with a "test lead" submission mode
- Automatic backup of previous HTML version on each edit save

---

## Success Metrics

- **Time to publish**: An administrator uploads a 5-file package and publishes the page in under 2 minutes from clicking "New Page" to the page being live.
- **Edit accuracy**: Text changes made in the block editor appear identically on the public page 100% of the time — no broken HTML, no lost formatting.
- **Lead capture reliability**: Every public page with `<!-- LEAD_FORM -->` present captures submissions without error. Zero lead forms failing to store data.
- **File cleanup completeness**: Deleting a landing page removes all associated files from disk. Zero orphaned files one week post-launch.
- **Zero regression on non-landing-page features**: Existing dashboard (leads, dashboard, auth) is unaffected by the changes.

---

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|---|
| Designer forgets to include `<form id="lead-form">`, resulting in no lead capture linkage | High | Medium | Publish the page normally without linkage; show a warning in the dashboard after save that no lead form was detected |
| Designers forget to add `<!-- BLOCO_N_INICIO/FIM -->` delimiters, leaving administrators with no editable sections | High | Medium | Fall back to a raw HTML textarea for the full file; warn in the editor that no blocks were detected |
| Uploaded files consume excessive disk space over time | Low | Medium | Display per-page storage usage in the list view; set a reasonable per-page file size cap (e.g., 50MB total) |
| Administrator accidentally overwrites layout while editing block text | Low | High | The block editor only rewrites text nodes inside delimiters — HTML structure is untouched. A confirmation prompt before save is sufficient |
| Upload fails due to PHP file size limits without clear feedback | Medium | Medium | Validate file sizes client-side before upload; display clear error messages for server-side limit rejections |

---

## Architecture Decision Records

- [ADR-005: File Upload Landing Page with Comment-Delimited Block Editing](adrs/adr-005.md) — Adopted directory-per-page file storage + HTML comment-delimited block editing over in-DB HTML storage or draft/publish workflows, enabling full creative freedom for external designers while keeping administrator editing simple.

---

## Open Questions

1. **Missing `<form id="lead-form">` behavior**: When the form is absent, should the system (a) publish the page without lead linkage and show a warning, or (b) block publishing until a form with that ID is added? The MVP defaults to option (a) — warn but allow publish.
2. **Multiple image upload UX**: Should images be uploaded as a group (multi-file input) or individually with previews before submit? Multi-file input is simpler for MVP.
3. **Block numbering format**: Should blocks be numbered (`BLOCO_1`, `BLOCO_2`) or allow custom names (`BLOCO_HERO`, `BLOCO_SERVICOS`)? Numbered is simpler for MVP; custom names can be added in Phase 2.
4. **File overwrite on re-upload**: Should editing a page allow re-uploading individual files to replace them, or must the administrator re-upload everything? Re-upload of individual files is a Phase 2 feature — MVP requires full re-upload to change files.
