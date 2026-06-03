# Landing Page Builder — File Upload Refactoring Technical Specification

## Executive Summary

This refactoring replaces the existing block composition system (block templates + `%TOKEN%` substitution + JSON state in DB) with a file-upload system. Administrators upload a complete landing page package — `index.html`, `style.css`, `app.js`, and images — which is stored on disk in `writable/landing_pages/{slug}/`. The public controller serves the uploaded files at `/p/{slug}`, injecting a hidden `landing_page_id` field into `<form id="lead-form">` for lead capture linkage. Administrators edit text content of comment-delimited blocks in the HTML via a simple form that uses DOMDocument to safely parse and rewrite the file.

The primary trade-off is file storage on disk vs database: storing files on disk gives designers full creative freedom (any HTML/CSS/JS, relative image paths) but introduces filesystem management (directory creation, cleanup on delete, file serving via PHP). In-DB storage would be simpler to manage but would not support image uploads or separate JS/CSS files, defeating the refactoring's purpose.

---

## System Architecture

### Component Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│  Browser (Admin Dashboard)                                          │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  Upload Form (create.php / edit.php)                          │   │
│  │  ├─ File inputs: index.html (required), style.css, app.js,   │   │
│  │  │               images (multi-file)                          │   │
│  │  ├─ Title + Slug fields                                       │   │
│  │  └─ Block editor (edit only): textareas per BLOCO_N block    │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                           │ POST (multipart/form-data)               │
│                           ▼                                          │
└─────────────────────────────────────────────────────────────────────┘
                           │
                ┌──────────▼──────────┐
                │  CodeIgniter 4 App  │
                │                     │
                │  LandingPages       │
                │  Controller         │
                │  (store/update →    │
                │   file upload +     │
                │   HTML processing)  │
                │                     │
                │  PublicController   │
                │  (show → serve      │
                │   index.html +      │
                │   assets)           │
                │                     │
                │  BlockEditor        │
                │  Helper (parse +    │
                │   rewrite block     │
                │   delimiters via    │
                │   DOMDocument)      │
                └──────────┬──────────┘
                           │
                ┌──────────▼──────────┐
                │  Filesystem         │
                │  writable/          │
                │  landing_pages/     │
                │  └─ meu-slug/       │
                │     ├─ index.html   │
                │     ├─ style.css    │
                │     ├─ app.js       │
                │     └─ images/      │
                │        └─ hero.jpg  │
                └─────────────────────┘
                           │
                ┌──────────▼──────────┐
                │  SQLite3 Database   │
                │  writable/database  │
                │                     │
                │  landing_pages      │
                │  (title, slug,      │
                │   file_path)        │
                │  leads              │
                │  (landing_page_id,  │
                │   name, email, etc) │
                └─────────────────────┘
```

**Components:**

| Component | File(s) | Responsibility |
|---|---|---|
| `LandingPagesController` | `app/Controllers/LandingPagesController.php` (rewritten) | Handle file upload (multipart), validate files, store on disk, manage CRUD |
| `PublicController` | `app/Controllers/PublicController.php` (rewritten) | Serve `index.html` with lead form linkage; serve static assets (CSS, JS, images) |
| `BlockEditorHelper` | `app/Helpers/block_editor_helper.php` (new) | Parse `<!-- BLOCO_N_INICIO/FIM -->` delimiters via DOMDocument; extract text; rewrite block content |
| `LandingPageModel` | `app/Models/LandingPageModel.php` (modified) | Update `allowedFields` to `['title', 'slug', 'file_path']` |
| `LeadModel` | `app/Models/LeadModel.php` (unchanged) | No changes needed |
| Migration | `app/Database/Migrations/2026-05-20-000001_RefactorLandingPagesForFileUpload.php` (new) | Drop `blocks`/`custom_css`, add `file_path`, drop `block_templates` table |
| Upload Views | `app/Views/landing_pages/create.php` (rewritten) | File upload form with multi-part encoding |
| Edit View | `app/Views/landing_pages/edit.php` (rewritten) | Block editor with textareas per detected block |
| Public View | `app/Views/public/landing_page.php` (simplified) | Minimal view — outputs `$htmlContent` directly |
| Index View | `app/Views/landing_pages/index.php` (unchanged) | No changes needed |

---

## Implementation Design

### Core Interfaces

#### PHP: BlockEditorHelper — DOMDocument-based block parser

```php
/**
 * Parse index.html for BLOCO_N comment delimiters and extract block data.
 *
 * @param string $html The full index.html content
 * @return array<int, array{start: int, end: int, text: string}>
 *   Each block: start offset, end offset, extracted text content
 */
function parse_block_delimiters(string $html): array
{
    $blocks = [];
    $dom    = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

    $xpath   = new DOMXPath($dom);
    $comments = $xpath->query('//comment()');

    $openMap = []; // comment node index -> BLOCO_N number
    foreach ($comments as $i => $comment) {
        if (preg_match('/BLOCO_(\d+)_INICIO/', $comment->nodeValue, $m)) {
            $openMap[$i] = (int) $m[1];
        }
    }

    // Match each INICIO with its following FIM and extract text
    // (full implementation in helper file)
    return $blocks;
}

/**
 * Rewrite block text content in index.html.
 *
 * @param string $html          Original HTML content
 * @param array<int, string> $blocks Map of block number -> new text
 * @return string Modified HTML with block text replaced
 */
function rewrite_block_content(string $html, array $blocks): string
{
    // Use DOMDocument to replace text nodes between each
    // BLOCO_N_INICIO/BLOCO_N_FIM pair, preserving all HTML structure
    // (full implementation in helper file)
    return $html;
}
```

#### PHP: PublicController::asset() — Static file serving

```php
/**
 * Serve uploaded static files (CSS, JS, images).
 *
 * @param string $slug     Page slug
 * @param string $filepath Relative path within the page directory
 */
public function asset(string $slug, string $filepath)
{
    $page = (new LandingPageModel())->findBySlug($slug);
    if (! $page || ! $page['file_path']) {
        throw PageNotFoundException::forPageNotFound();
    }

    $fullPath = WRITEPATH . $page['file_path'] . '/' . $filepath;
    $realPath = realpath($fullPath);
    $basePath = realpath(WRITEPATH . $page['file_path']);

    // Prevent path traversal: resolved path must start with the page directory
    if (! $realPath || ! str_starts_with($realPath, $basePath) || ! is_file($realPath)) {
        throw PageNotFoundException::forPageNotFound();
    }

    $mime = mime_content_type($realPath) ?: 'application/octet-stream';
    return $this->response
        ->setHeader('Content-Type', $mime)
        ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
        ->setFilePath($realPath);
}
```

#### PHP: Page Blocks JSON Structure (removed)

The `blocks` JSON structure from the old system is eliminated entirely. There is no JSON payload. The `index.html` file on disk IS the page content.

#### PHP: Public Renderer (updated PublicController::show)

```php
public function show($slug)
{
    $model = new LandingPageModel();
    $page  = $model->findBySlug($slug);
    if (! $page) {
        throw PageNotFoundException::forPageNotFound();
    }

    $filePath = WRITEPATH . ($page['file_path'] ?? '') . '/index.html';
    if (! is_file($filePath)) {
        log_message('error', '[landing_page.missing_file slug=' . $slug . ']');
        throw PageNotFoundException::forPageNotFound();
    }

    $html = file_get_contents($filePath);

    // Inject hidden landing_page_id into <form id="lead-form">
    $leadFormHtml = '<input type="hidden" name="landing_page_id" value="' . $page['id'] . '">';
    $html = str_replace(
        '<form id="lead-form"',
        '<form id="lead-form"' . $leadFormHtml,
        $html
    );

    return view('public/landing_page', ['htmlContent' => $html]);
}
```

### Data Models

#### Table: `landing_pages` (modified)

| Column | Type | Constraints | Change |
|---|---|---|---|
| `id` | INTEGER | PK, unsigned, auto-increment | unchanged |
| `title` | VARCHAR(255) | NOT NULL | unchanged |
| `slug` | VARCHAR(255) | NOT NULL, UNIQUE | unchanged |
| `blocks` | TEXT | — | **REMOVED** |
| `custom_css` | TEXT | — | **REMOVED** |
| `file_path` | VARCHAR(255) | nullable | **NEW** — relative path from `writable/` (e.g., `landing_pages/meu-slug`) |
| `created_at` | DATETIME | nullable | unchanged |
| `updated_at` | DATETIME | nullable | unchanged |

#### Table: `block_templates` (dropped)

This entire table is removed by the migration. All block template data is lost (no backward compatibility per PRD).

#### Table: `leads` (unchanged)

| Column | Type | Constraints |
|---|---|---|
| `id` | INTEGER | PK, unsigned, auto-increment |
| `landing_page_id` | INTEGER | FK → landing_pages.id, CASCADE |
| `name` | VARCHAR(255) | NOT NULL |
| `email` | VARCHAR(255) | NOT NULL |
| `phone` | VARCHAR(50) | nullable |
| `message` | TEXT | nullable |
| `status` | VARCHAR(50) | NOT NULL, default 'New' |
| `created_at` | DATETIME | nullable |

#### Filesystem Layout

```
writable/
  landing_pages/
    meu-slug/
      index.html       (required — the main page HTML)
      style.css         (optional — page stylesheet)
      app.js            (optional — page JavaScript)
      images/           (optional — directory for uploaded images)
        hero.jpg
        logo.png
    outro-slug/
      index.html
      style.css
      ...
```

### API Endpoints

#### Landing Pages Resource (rewritten)

| Method | Path | Handler | Description |
|---|---|---|---|
| `GET` | `/landing-pages` | `LandingPagesController::index` | List all pages |
| `GET` | `/landing-pages/create` | `LandingPagesController::create` | Show upload form |
| `POST` | `/landing-pages` | `LandingPagesController::store` | Upload files + create record |
| `GET` | `/landing-pages/edit/(:num)` | `LandingPagesController::edit` | Show block editor |
| `POST` | `/landing-pages/update/(:num)` | `LandingPagesController::update` | Save block edits + rewrite HTML |
| `GET` | `/landing-pages/delete/(:num)` | `LandingPagesController::delete` | Delete page + remove files |

**`POST /landing-pages` Request (multipart/form-data):**

```
title:      "Campanha Trabalhista"
slug:       "campanha-trabalhista"
index_html: [file upload]     (required: .html)
style_css:  [file upload]     (optional: .css)
app_js:     [file upload]     (optional: .js)
images[]:   [multiple files]  (optional: .jpg, .png, .webp, .svg, .gif)
```

**Validation rules (updated):**
```php
$rules = [
    'title'     => 'required|max_length[255]',
    'slug'      => 'required|max_length[255]|is_unique[landing_pages.slug]',
    'index_html' => 'uploaded[index_html]|ext_in[index_html,html]|max_size[index_html,1024]',
    'style_css'  => 'if_exist|ext_in[style_css,css]|max_size[style_css,1024]',
    'app_js'     => 'if_exist|ext_in[app_js,js]|max_size[app_js,1024]',
    'images'     => 'if_exist|ext_in[images,jpg,jpeg,png,webp,svg,gif]|max_size[images,5120]',
];
```

#### Public Routes (modified)

| Method | Path | Handler | Description |
|---|---|---|---|
| `GET` | `/p/(:any)` | `PublicController::show/$1` | Serve landing page HTML |
| `POST` | `/p/(:any)/lead` | `PublicController::storeLead/$1` | Receive lead form submission |
| `GET` | `/p/(:any)/assets/(:any)` | `PublicController::asset/$1/$2` | Serve static files (CSS, JS, images) |

#### Removed Routes (old block system)

```
GET    /landing-pages/blocks          → removed
GET    /landing-pages/blocks/create   → removed
POST   /landing-pages/blocks          → removed
GET    /landing-pages/blocks/all      → removed
GET    /landing-pages/blocks/edit/(:num) → removed
POST   /landing-pages/blocks/update/(:num) → removed
GET    /landing-pages/blocks/delete/(:num) → removed
```

---

## Integration Points

No external services are integrated. All integration is within the existing CodeIgniter 4 application.

**Internal integration points:**

- The `PublicController::asset()` endpoint serves uploaded files. The request does not require authentication (public pages are public). Path traversal is prevented by resolving `realpath()` and verifying the result starts with the expected base directory.
- The `storeLead()` endpoint in `PublicController` accepts the `landing_page_id` from the hidden injected field. This field is set server-side when serving the HTML, not by the designer's form. This prevents spoofing: the hidden field's value is always the correct page ID.
- The `BlockEditorHelper` is a plain PHP helper function file (not a class), consistent with CI4's helper system. It is loaded via `helper('block_editor')` in the edit controller.

---

## Impact Analysis

| Component | Impact Type | Description and Risk | Required Action |
|---|---|---|---|
| `LandingPagesController` | Modified | `store()` rewritten to handle `multipart/form-data` file uploads. `update()` rewritten to handle block text editing. `create()` and `edit()` return new views. `delete()` must clean up files on disk. | Full rewrite of all methods |
| `PublicController` | Modified | `show()` rewritten to read and serve `index.html` from disk, inject hidden `landing_page_id`. New `asset()` method for static files. `storeLead()` may need minor update to use `landing_page_id` from POST (already supported via field). | Rewrite `show()`, add `asset()`, minor update to `storeLead()` |
| `LandingPageModel` | Modified | `allowedFields` → `['title', 'slug', 'file_path']`. `findBySlug()` is unchanged. | Update `allowedFields` |
| `BlockTemplateModel` | Removed | Entire model is deleted along with the `block_templates` table. | Delete file |
| `BlockTemplatesController` | Removed | Entire controller is deleted. All CRUD routes removed. | Delete file |
| `page-builder.js` | Removed | No longer needed — the new system uses a server-side form for upload and simple textareas for editing. | Delete file |
| `create.php` | Rewritten | Old composer UI replaced with file upload form (no CodeMirror, no preview iframe, no `page-builder.js`). `enctype="multipart/form-data"`. | Full rewrite |
| `edit.php` | Rewritten | Block editor with DOMDocument-parsed textareas instead of the old composer UI. No CodeMirror, no preview iframe. | Full rewrite |
| `index.php` | Unchanged | List view works the same — just reads title/slug from DB. | No changes |
| `public/landing_page.php` | Simplified | Removed `$heroHtml`/`$sectionsHtml`/`$customCss`. Now just outputs `$htmlContent` directly (the complete page from `index.html`). | Full rewrite |
| `style.css` | Modified | Remove page-builder-specific CSS classes (`.composer-layout`, `.block-instance`, `.hero-editor`, `.token-input`, `.preview-iframe`). Add upload-form-specific styles if needed. | Remove old classes, add simple form styles |
| `Routes.php` | Modified | Remove all `/landing-pages/blocks/*` routes. Add `/p/(:any)/assets/(:any)` route. | Update route list |
| `sidebar.php` | Modified | Remove "Block Templates" nav item from sidebar. | Remove nav link |
| Block Templates Views | Removed | `app/Views/block_templates/` directory deleted entirely. | Delete directory |
| `writable/uploads/` | Used | New `writable/landing_pages/` subdirectory created. Old `writable/uploads/` is not affected but may be reused in future. | Create directory structure |

---

## Testing Approach

### Unit Tests

**PHP (CI4 Test suite / PHPUnit):**

- `BlockEditorHelper::parse_block_delimiters()` — verify: no blocks returns empty array, one block extracts correctly, multiple blocks extracted in order, text-only extraction strips HTML tags, nested HTML inside block is correctly handled, blocks with no text return empty string.
- `BlockEditorHelper::rewrite_block_content()` — verify: single block rewritten correctly, multiple blocks rewritten independently, unchanged blocks are preserved, HTML structure outside blocks is untouched, script/style content with matching comment patterns is ignored.
- `PublicController::asset()` — verify: valid file returns correct MIME type, path traversal is blocked (`../../etc/passwd` style), non-existent file returns 404, file outside page directory is blocked.
- `LandingPagesController::store()` — verify: missing `index.html` is rejected, valid upload creates record + directory, duplicate slug is rejected, invalid file extensions rejected.
- `PublicController::show()` — verify: missing `index.html` file returns 404, missing `file_path` returns 404, valid page serves HTML with injected `landing_page_id` hidden field.

### Integration Tests

- Full upload flow (manual, browser): upload a landing page package → confirm redirect → visit `/p/{slug}` → verify HTML renders correctly, `landing_page_id` hidden field is present in `<form id="lead-form">`, CSS/JS/images load via asset endpoint.
- Block editing flow (manual, browser): upload a page with BLOCO_1 delimiters → edit block text → save → re-visit public URL → verify text change appears, rest of HTML is unchanged.
- Lead capture flow (manual, browser): submit lead form on public page → check leads table in dashboard → verify `landing_page_id` matches the correct page.
- Delete flow: delete a page → verify directory is removed from disk → verify `/p/{slug}` returns 404.

---

## Development Sequencing

### Build Order

1. **Migration** — `RefactorLandingPagesForFileUpload.php`. Drop `blocks`/`custom_css` from `landing_pages`, add `file_path` VARCHAR(255). Drop `block_templates` table completely. Run `php spark migrate`. No dependencies.

2. **`LandingPageModel`** — depends on step 1. Update `allowedFields` to `['title', 'slug', 'file_path']`.

3. **`BlockEditorHelper`** — depends on step 1 (conceptual, no code dependency). Create `app/Helpers/block_editor_helper.php` with `parse_block_delimiters()` and `rewrite_block_content()`. Both functions operate on HTML strings only — no DB dependency.

4. **`PublicController` rewrite + public view** — depends on steps 2–3. Rewrite `show()` to read from filesystem, inject `landing_page_id`. Add `asset()` method. Simplify `public/landing_page.php` to output `$htmlContent`. Update `storeLead()` to use POST `landing_page_id`.

5. **Routes update** — depends on step 4. Remove `/landing-pages/blocks/*` routes. Add `GET /p/(:any)/assets/(:any)` route. Update `Filters.php` if needed (auth filter for `/landing-pages/*` still covers the admin routes).

6. **Upload views (`create.php` + `edit.php`)** — depends on step 3. Rewrite `create.php` as file upload form with `enctype="multipart/form-data"`. Rewrite `edit.php` to load HTML, parse blocks, display textareas, and submit rewritten HTML on save.

7. **`LandingPagesController` rewrite** — depends on steps 2, 4, 6. Rewrite `store()` to handle file uploads (validate, store files on disk, insert DB record). Rewrite `update()` to handle block text editing (read HTML, rewrite blocks, save file). Rewrite `delete()` to remove files from disk before deleting DB record.

8. **Sidebar and CSS cleanup** — depends on step 7. Remove "Block Templates" link from sidebar. Remove old composer CSS classes from `style.css`. Delete `page-builder.js`. Delete `BlockTemplatesController.php`, `BlockTemplateModel.php`, and `app/Views/block_templates/` directory.

### Technical Dependencies

- SQLite `DROP COLUMN` support (3.35.0+) for clean migration. If running an older SQLite, use the table-recreation workaround already established in the existing migration pattern.
- No external dependencies introduced. CodeMirror CDN is removed (no longer needed).
- PHP `ext-dom` and `ext-libxml` are required (DOMDocument). These are typically enabled by default in PHP installations and are already available in the project's vendor environment.

---

## Monitoring and Observability

- **Missing `index.html` file**: If the `file_path` directory exists but `index.html` is absent (manual deletion or filesystem error), log an error via `log_message('error', '[landing_page.missing_file slug={slug}]')` and return 404.
- **Missing `file_path` column**: If a record has null `file_path`, log an error and return 404.
- **Lead form not found**: If the uploaded HTML has no `<form id="lead-form">`, log a warning `[landing_page.no_lead_form slug={slug}]` and serve the page as-is without injection.
- **No blocks detected in editor**: If `parse_block_delimiters` returns empty, show a warning in the edit view and fall back to raw HTML textarea. No log event needed — this is a UI concern.
- **Key log events:**
  - `[INFO] landing_page.created slug={slug} files={count}` — on successful upload and page creation.
  - `[INFO] landing_page.updated slug={slug} blocks={count}` — on successful block edit save.
  - `[INFO] landing_page.deleted slug={slug} files_removed={count}` — on page deletion with file cleanup.
  - `[WARNING] landing_page.missing_index_html slug={slug}` — if DB record exists but index.html is missing from disk.
  - `[WARNING] landing_page.no_lead_form slug={slug}` — if uploaded HTML lacks `<form id="lead-form">`.

---

## Technical Considerations

### Key Decisions

**Static file serving via PHP controller endpoint (ADR-006)**
- Decision: Serve all CSS, JS, and image files through `PublicController::asset()` at `/p/{slug}/assets/{path}`.
- Rationale: Files remain outside webroot. Full control over MIME types, caching, and access control. No symlink or copy management.
- Trade-off: Every asset request hits PHP — acceptable for typical landing pages with 3–10 assets due to browser caching.

**Database schema: single `file_path` column (ADR-007)**
- Decision: Store only a `file_path` VARCHAR pointing to the directory under `writable/`. The filesystem IS the manifest.
- Rationale: Minimal schema change. No sync between DB and filesystem. Adding new file types in the future requires zero DB changes.
- Trade-off: Cannot query DB for pages with specific files — not a use case for MVP.

**Block editor via DOMDocument (ADR-008)**
- Decision: Parse `<!-- BLOCO_N_INICIO/FIM -->` delimiters using PHP `DOMDocument` and `DOMXPath`.
- Rationale: Correctly handles arbitrary HTML structure. Can distinguish text nodes from element nodes. Handles malformed HTML gracefully.
- Trade-off: More verbose PHP code than regex (~30 lines vs ~5 lines). Slightly slower for large files — negligible for typical landing pages under 100KB.

### Known Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| **Path traversal in file serving**: Attackers request `/p/foo/assets/../../etc/passwd` | Low | Resolve `realpath()` and verify it starts with the expected base directory |
| **Cold cache performance**: First visitor after deploy loads all assets via PHP | Low | Set far-future `Cache-Control: public, max-age=31536000` headers |
| **DOMDocument mutilates HTML**: PHP's DOMDocument may reformat or change the HTML structure when saving | Medium | `DOMDocument::saveHTML()` preserves HTML structure but may add DOCTYPE and `<html>`/`<body>` wrappers. Mitigation: `substr()` the original file and only replace the text content between delimiters at the string level, avoiding `saveHTML()` entirely for the rewrite step. See implementation note. |
| **Large file uploads**: PHP upload limits may reject large packages | Medium | Validate and communicate limits client-side (JavaScript) and server-side. Set `upload_max_filesize` and `post_max_size` in CI4's `.env` if needed. |
| **Slug changes after upload**: If an admin changes the slug, the directory on disk must be renamed | Low | Phase 1 disallows slug changes after creation. Phase 2 can add directory rename logic. |

**Implementation note on DOMDocument rewrite risk**: To avoid `saveHTML()` reformatting the entire document, the rewrite strategy will NOT use `saveHTML()`. Instead, it will use string-level replacement: `parse_block_delimiters()` returns byte offsets of each block's text content in the original string. `rewrite_block_content()` uses `substr()` slicing to replace those byte ranges with new text, preserving everything else exactly as-is. DOMDocument is used only for parsing (finding comment nodes and their positions), not for serialization.

---

## Architecture Decision Records

- [ADR-005: File Upload Landing Page with Comment-Delimited Block Editing](adrs/adr-005.md) — Adopted directory-per-page file storage + HTML comment-delimited block editing over in-DB HTML storage or draft/publish workflows.
- [ADR-006: Static File Serving via PHP Controller Endpoint](adrs/adr-006.md) — All uploaded static assets (CSS, JS, images) are served through a dedicated PHP controller endpoint, keeping files outside the webroot.
- [ADR-007: Landing Pages Database Schema — file_path Column](adrs/adr-007.md) — Single `file_path` VARCHAR column replaces `blocks`/`custom_css`, pointing to the filesystem directory for each page.
- [ADR-008: Block Editor HTML Parsing via DOMDocument](adrs/adr-008.md) — DOMDocument + XPath for parsing comment-delimited blocks; string-level replacement for rewriting to avoid DOM serialization issues.
