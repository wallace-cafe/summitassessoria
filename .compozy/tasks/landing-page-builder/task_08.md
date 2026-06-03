---
status: completed
title: "Composer views: rewrite create.php and edit.php with full builder UI"
type: frontend
complexity: high
dependencies:
  - task_05
  - task_07
---

# Task 08: Composer views: rewrite create.php and edit.php with full builder UI

## Overview

Rewrites `app/Views/landing_pages/create.php` and `app/Views/landing_pages/edit.php` to replace the plain textarea form with the full two-column block composer layout. Both views initialize CodeMirror 5 (for the CSS editor) via the dashboard layout's `js` and `css` injection sections, call `PageBuilder.init()`, and wire form submission to serialize state into hidden fields. The edit view additionally calls `PageBuilder.loadState()` to pre-populate the editor from the saved `blocks` JSON.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
1. MUST rewrite `app/Views/landing_pages/create.php` to render the two-column composer layout described in the PRD "User Experience — UI Layout" section. The left column contains the editor panel; the right column contains the live preview `<iframe id="preview-iframe">`.
2. MUST rewrite `app/Views/landing_pages/edit.php` with the same layout, pre-populating state from `$page['blocks']` and `$page['custom_css']`.
3. Both views MUST use CI4's `$this->section('css')` to inject CodeMirror 5 CSS from CDN (`https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css`).
4. Both views MUST use CI4's `$this->section('js')` to inject: CodeMirror 5 core JS, the `css` mode, the `htmlmixed` mode, and `page-builder.js` — all from CDN or `/assets/js/page-builder.js` respectively.
5. The editor panel MUST contain, in order:
   a. Page title input (`name="title"`) and slug input (`name="slug"`) with auto-slug JS wired via `PageBuilder.initSlugGenerator()`.
   b. CSS editor: a `<textarea id="css-editor" name="custom_css">` wrapped in a CodeMirror instance.
   c. Hero block section: three inputs for `titulo`, `subtitulo`, and `imagem_fundo` (URL text input).
   d. Block instances container: `<div id="block-instances-container">` where JS renders block instance cards.
   e. `<button id="add-block-btn">+ Add Block</button>` that opens the block library picker.
   f. A hidden `<textarea name="blocks" id="blocks-payload">` that `StateSerializer.serialize()` writes to before form submit.
   g. A `<button type="submit" class="btn btn-primary">Save</button>` button.
6. The form action in `create.php` MUST be `POST /landing-pages` with `csrf_field()`.
7. The form action in `edit.php` MUST be `POST /landing-pages/update/{$page['id']}` with `csrf_field()`.
8. MUST also update `LandingPagesController::store()` and `update()` to accept `blocks` (JSON string, `valid_json` rule) and `custom_css` (permit_empty) instead of `content`. See TechSpec "API Endpoints" section ("Validation rules (updated)").
9. The preview `<iframe id="preview-iframe">` MUST have `sandbox=""` attribute (no additional permissions).
10. Both views MUST set `sidebar_active` to `'landing-pages'`.
11. Both views MUST display validation errors from `session()->getFlashdata('errors')` using the `.alert.alert-error` pattern.
12. The edit view MUST call `PageBuilder.loadState(<?= json_encode($page['blocks'] ? json_decode($page['blocks'], true) : []) ?>)` in the inline script block.
</requirements>

## Subtasks

- [x] 8.1 Rewrite `app/Views/landing_pages/create.php` with the two-column composer HTML structure and all required form fields.
- [x] 8.2 Rewrite `app/Views/landing_pages/edit.php` with the same layout, adding `PageBuilder.loadState()` pre-population.
- [x] 8.3 Inject CodeMirror 5 CSS and JS (core + css-mode + htmlmixed-mode) via the `css`/`js` layout sections.
- [x] 8.4 Wire `PageBuilder.init()` and `PageBuilder.initSlugGenerator()` in the inline `js` section.
- [x] 8.5 Update `LandingPagesController::store()` and `update()` validation rules and persistence logic to use `blocks` and `custom_css` instead of `content`.

## Implementation Details

See PRD "User Experience — UI Layout" for the ASCII wireframe. See TechSpec "API Endpoints" section for updated validation rules. Do NOT duplicate the wireframe or code snippets here — reference TechSpec sections by name.

The `js` section inline script initializes CodeMirror on `#css-editor` and calls:
```javascript
// Reference only — do not copy to task file
const cssMirror = CodeMirror.fromTextArea(document.getElementById('css-editor'), { mode: 'css', lineNumbers: true });
PageBuilder.init({ cssMirror, previewIframeId: 'preview-iframe', blocksPayloadId: 'blocks-payload' });
```

The `LandingPagesController` changes (subtask 8.5) are scoped to `store()` and `update()` only — `index()`, `create()`, `edit()`, `delete()` are not modified in this task.

For `store()` and `update()`: replace `'content' => $this->request->getPost('content')` with `'blocks' => $this->request->getPost('blocks')` and `'custom_css' => $this->request->getPost('custom_css')`. Update validation rules per TechSpec.

### Relevant Files

- `app/Views/landing_pages/create.php` — rewritten (current: 39 lines plain form)
- `app/Views/landing_pages/edit.php` — rewritten (current: 39 lines plain form)
- `app/Controllers/LandingPagesController.php` — `store()` and `update()` updated (validation + persistence)
- `app/Views/layouts/dashboard.php` — reference for `section('css')` and `section('js')` injection points (lines 8 and 20)
- `public/assets/js/page-builder.js` — (task_07 output) loaded in the `js` section

### Dependent Files

- `public/assets/css/style.css` — (task_09) CSS classes used by the composer layout must exist (`.composer-layout`, `.composer-editor`, `.composer-preview`, `.block-instance`, etc.)
- `app/Views/public/landing_page.php` — (task_06 output) no dependency, but the save here produces the `blocks` JSON that task_06's renderer consumes

### Related ADRs

- [ADR-001: Block Library + Page Composer Architecture](../adrs/adr-001.md) — This task implements the per-page composer surface.
- [ADR-003: Client-Side iframe Preview via srcdoc](../adrs/adr-003.md) — The `<iframe id="preview-iframe" sandbox="">` element in these views is the target of the JS preview engine.
- [ADR-004: Database Schema — Replace `content` Column, Add `blocks` + `custom_css`](../adrs/adr-004.md) — The form fields and controller persistence changes align with the schema decision.

## Deliverables

- `app/Views/landing_pages/create.php` (rewritten)
- `app/Views/landing_pages/edit.php` (rewritten)
- `app/Controllers/LandingPagesController.php` (modified: `store()` and `update()` only)
- Unit tests with 80%+ coverage **(REQUIRED)**
- Integration tests for save flow **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `GET /landing-pages/create` returns HTTP 200 and the response contains `id="preview-iframe"`.
  - [ ] `GET /landing-pages/create` response contains `id="css-editor"`.
  - [ ] `GET /landing-pages/create` response contains `id="block-instances-container"`.
  - [ ] `GET /landing-pages/edit/1` (with an existing page) returns HTTP 200 and contains `PageBuilder.loadState(` in the response HTML.
  - [ ] `POST /landing-pages` with empty `title` returns redirect back with a `title` validation error.
  - [ ] `POST /landing-pages` with `blocks='not-json'` returns redirect back with a `blocks` validation error (invalid_json).
  - [ ] `POST /landing-pages` with valid `title`, `slug`, `blocks='{"hero":{},"instances":[]}`, `custom_css='body{}'` redirects to `/landing-pages` with success flash.
  - [ ] `POST /landing-pages/update/{id}` with valid fields updates the `blocks` and `custom_css` columns of the record.
- Integration tests:
  - [ ] Full create-to-public flow: submit the composer form with one block instance → `GET /p/{slug}` renders the block's HTML.
  - [ ] Edit an existing page: load the edit view → `PageBuilder.loadState()` is called with the existing `blocks` JSON → form submit updates the record.
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- The composer view loads CodeMirror without console errors
- `POST /landing-pages` accepts `blocks` (JSON) and `custom_css` and persists them correctly
- The edit view pre-populates from the saved `blocks` JSON
- Submitting the form from the edit view updates the page and the public URL reflects the change immediately
- Validation rejects invalid JSON in the `blocks` field with a user-visible error
