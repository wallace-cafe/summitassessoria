---
status: completed
title: "page-builder.js: client-side composer JS module"
type: frontend
complexity: high
dependencies:
  - task_04
---

# Task 07: page-builder.js: client-side composer JS module

## Overview

Creates `public/assets/js/page-builder.js`, the self-contained vanilla JavaScript module that drives the entire landing page composer UI. It manages the editor state (hero config, block instances, custom CSS), renders the live iframe preview via `srcdoc`, handles block library fetching and picking, token input rendering, block reordering, and serializes the state for form submission.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
1. MUST be written in plain vanilla JavaScript (ES6+) with no external runtime dependencies (no jQuery, no React, no Alpine). CodeMirror 5 instances are passed in as arguments or initialized externally in the view.
2. MUST expose a single `PageBuilder` object (or module pattern) on `window.PageBuilder` with at minimum: `init(config)`, `getState()`, and `serialize()`.
3. MUST manage an internal state object matching the shape defined in TechSpec "Core Interfaces" section ("JavaScript: Core State Shape"): `title`, `slug`, `customCss`, `hero` (titulo/subtitulo/imagemFundo), and `instances` (ordered array).
4. MUST implement `SlugGenerator`: converts a title string to a URL-safe slug (lowercase, accented chars normalized via `normalize('NFD')` + removing diacritics, spaces → hyphens, strip non-alphanumeric/hyphen, collapse consecutive hyphens).
5. MUST implement `TokenExtractor.extract(html)`: extracts `%TOKEN%` token names using regex `/\%([A-Z0-9_]+)\%/g`, returns unique names. MUST produce identical output to `BlockTemplateModel::extractTokens()` for the same input.
6. MUST implement `PreviewEngine.buildDocument(state)`: assembles a complete HTML document string from the current state (Hero HTML + block instances with token substitution + custom CSS in `<style>`) and assigns it to `document.getElementById('preview-iframe').srcdoc`. MUST debounce at ~150ms after the last triggering input event.
7. Token substitution in `PreviewEngine` MUST use the same algorithm as `BlockTemplateModel::substituteTokens()`: replace each `%TOKEN%` with its value, then remove any remaining `%TOKEN%` patterns.
8. MUST implement `BlockLibraryPicker`: fetches `GET /landing-pages/blocks/all` (once on init, cached), renders a modal or inline panel listing block templates, and on selection calls `addBlockInstance(template)`.
9. `addBlockInstance(template)` MUST: generate a unique local `id` (e.g., `Date.now()` string), store `blockTemplateId`, `blockName`, `htmlSnapshot` (from `template.html_template`), `tokens` (object keyed by token name, all values empty string initially), and `position` (append at end).
10. MUST implement `moveBlockUp(instanceId)` and `moveBlockDown(instanceId)` which swap `position` values of adjacent instances and re-render the block list DOM.
11. MUST implement `removeBlockInstance(instanceId)` which removes the instance from state and removes its DOM element.
12. MUST implement `StateSerializer.serialize()`: returns a JSON string of the full state in the format expected by the server (see TechSpec "API Endpoints" section, `POST /landing-pages` request body). This JSON string is written into the hidden `<textarea name="blocks">` before form submit.
13. On any state change (CSS editor, Hero fields, token inputs, reorder), MUST trigger the debounced preview refresh.
14. For token inputs, tokens whose name starts with `IMAGEM_` MUST render as `<input type="url">` instead of `<input type="text">`.
</requirements>

## Subtasks

- [x] 7.1 Create `public/assets/js/page-builder.js` with module boilerplate and state initialization.
- [x] 7.2 Implement `SlugGenerator` (title → slug conversion with accent normalization).
- [x] 7.3 Implement `TokenExtractor.extract(html)` matching the PHP token regex.
- [x] 7.4 Implement `PreviewEngine.buildDocument(state)` with debounced `srcdoc` injection.
- [x] 7.5 Implement `BlockLibraryPicker` with `fetch('/landing-pages/blocks/all')`, caching, and selection callback.
- [x] 7.6 Implement `addBlockInstance`, `moveBlockUp`, `moveBlockDown`, `removeBlockInstance` with DOM updates.
- [x] 7.7 Implement `StateSerializer.serialize()` and wire it to the form's `submit` event.

## Implementation Details

See TechSpec "Core Interfaces" section ("JavaScript: Core State Shape") for the state structure. See TechSpec "Technical Considerations — Key Decisions" for the rationale behind the single-form-POST approach and the `srcdoc` preview strategy.

The file is self-contained: no imports, no build step. It uses `document.addEventListener('DOMContentLoaded', ...)` as its entry point. The view (task_08) initializes CodeMirror instances and calls `PageBuilder.init({ cssMirror, htmlMirror, formId, previewIframeId })`.

The iframe must have `sandbox=""` to isolate the preview from the dashboard frame. Do not add `allow-same-origin` to the sandbox.

The block list DOM is rendered inside a container element (e.g., `id="block-instances-container"`). Each block instance renders as a `<div class="block-instance" data-instance-id="...">` containing: the block name heading, token inputs, and Up/Down/Remove buttons.

`StateSerializer.serialize()` must produce the `blocks` JSON string that matches the PHP decode structure in TechSpec "Data Models — blocks JSON Schema".

### Relevant Files

- `public/assets/js/app.js` — reference for existing JS conventions (IIFE, `window.App`, `DOMContentLoaded`)
- `app/Views/layouts/dashboard.php` — JS injection point: `renderSection('js')` after `app.js` (line 20)
- `app/Controllers/BlockTemplatesController.php` — (task_04 output) provides `GET /landing-pages/blocks/all` endpoint consumed here

### Dependent Files

- `app/Views/landing_pages/create.php` — (task_08) initializes `PageBuilder.init()` and provides the DOM elements this module expects
- `app/Views/landing_pages/edit.php` — (task_08) same as create.php, plus calls `PageBuilder.loadState(existingBlocks)` to pre-populate
- `public/assets/css/style.css` — (task_09) CSS classes used by the block instance DOM elements must exist

### Related ADRs

- [ADR-003: Client-Side iframe Preview via srcdoc](../adrs/adr-003.md) — This task implements the JS side of the preview engine described in ADR-003.
- [ADR-002: Block Instance Storage as HTML Snapshot](../adrs/adr-002.md) — `addBlockInstance` stores `htmlSnapshot` from `template.html_template`, implementing the snapshot contract.

## Deliverables

- `public/assets/js/page-builder.js` (new file, ~300–400 lines)
- Unit tests (browser console / Vitest or manual) with 80%+ coverage **(REQUIRED)**
- Integration tests for block picker and preview **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `SlugGenerator.generate('Campanha Trabalhista')` returns `'campanha-trabalhista'`.
  - [ ] `SlugGenerator.generate('Direito Empresarial — São Paulo')` returns `'direito-empresarial-sao-paulo'` (accent normalized, em-dash stripped).
  - [ ] `SlugGenerator.generate('HERO  block')` returns `'hero-block'` (uppercase lowercased, consecutive spaces collapsed).
  - [ ] `TokenExtractor.extract('<p>%NOME% %CIDADE%</p>')` returns `['NOME', 'CIDADE']`.
  - [ ] `TokenExtractor.extract('<p>%TITULO% %TITULO%</p>')` returns `['TITULO']` (no duplicates).
  - [ ] `TokenExtractor.extract('<p>%nome%</p>')` returns `[]` (lowercase not matched).
  - [ ] `PreviewEngine.substituteTokens('%NOME%', {NOME: 'João'})` returns `'João'`.
  - [ ] `PreviewEngine.substituteTokens('%NOME% %CIDADE%', {NOME: 'João'})` returns `'João '` (missing token removed).
  - [ ] `StateSerializer.serialize()` with one block instance produces a valid JSON string parseable by `JSON.parse`.
  - [ ] `StateSerializer.serialize()` output contains `hero`, `instances` keys at the top level.
  - [ ] A token whose name starts with `IMAGEM_` renders an `<input type="url">` element in the block instance DOM.
  - [ ] `moveBlockUp(id)` on the first block in the list has no effect (already at position 0).
  - [ ] `moveBlockDown(id)` on the last block in the list has no effect.
- Integration tests:
  - [ ] `BlockLibraryPicker` fetches `GET /landing-pages/blocks/all` exactly once and caches the result for subsequent picker opens.
  - [ ] Selecting a block from the picker adds a new `.block-instance` DOM element with the correct block name heading and one input per token.
  - [ ] Changing a token input value triggers the debounced preview refresh (iframe `srcdoc` is updated within 200ms).
  - [ ] Clicking "Remove" on a block instance removes its DOM element and removes it from `PageBuilder.getState().instances`.
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- `PageBuilder.init()` runs without errors in the browser console when the composer view is loaded
- Block templates load from the API and can be added to the page
- Token inputs render correctly (text for regular tokens, URL input for `IMAGEM_*` tokens)
- Live preview iframe updates within 200ms of any input change
- `StateSerializer.serialize()` produces JSON that the PHP controller can `json_decode` without errors
- Up/Down/Remove controls correctly modify the instance list and DOM
