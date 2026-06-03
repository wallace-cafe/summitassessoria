---
status: completed
title: "CSS additions: composer-specific classes in style.css"
type: frontend
complexity: medium
dependencies:
  - task_08
---

# Task 09: CSS additions: composer-specific classes in style.css

## Overview

Appends all composer-specific CSS classes to `public/assets/css/style.css`, using the project's existing CSS custom properties. This task makes the two-column composer layout, block instance cards, hero editor section, token inputs, preview iframe, and block picker modal visually consistent with the dashboard's Binance-style dark theme.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
1. MUST append new classes to `public/assets/css/style.css` without modifying any existing rules.
2. MUST use existing CSS custom properties: `--canvas-dark`, `--surface-card-dark`, `--hairline-on-dark`, `--primary`, `--text-primary`, `--text-secondary`, `--error`, `--success`.
3. MUST define the following classes (minimum):
   - `.composer-layout` — two-column grid/flex layout: left editor panel, right preview panel.
   - `.composer-editor` — left panel: scrollable, fixed or proportional width, padding.
   - `.composer-preview` — right panel: sticky, fills viewport height, contains the iframe.
   - `.preview-iframe` — fills its container, `border: none`, `background: white`.
   - `.hero-editor` — section card for the Hero block inputs, visually distinct from other sections.
   - `.block-instance` — card wrapper for each block instance in the list (border, padding, margin-bottom).
   - `.block-instance-header` — row with block name and action buttons (Up, Down, Remove).
   - `.block-instance-actions` — button group for Up/Down/Remove, right-aligned.
   - `.token-inputs` — container for the token input fields within a block instance.
   - `.token-label` — label for each token input, derived from token name.
   - `.add-block-btn` — styled "Add Block" button (uses `.btn` base, distinct color or dashed border).
   - `.block-picker-modal` — overlay modal for the block library picker.
   - `.block-picker-list` — scrollable list of available block templates inside the modal.
   - `.block-picker-item` — individual block template item in the picker list (clickable row).
   - `.preview-toggle` — toggle button row (Edit | Full Preview) positioned above the iframe.
4. All new classes MUST be responsive: on screens narrower than 900px, `.composer-layout` stacks columns vertically (editor on top, preview below).
5. MUST NOT override or conflict with any existing class in `style.css`.
6. SHOULD use CSS Grid for `.composer-layout` (e.g., `grid-template-columns: 1fr 1fr`) with a fallback to flexbox if needed.
7. `.block-instance` hover state SHOULD display a subtle border highlight using `--primary` or `--hairline-on-dark`.
8. The block picker modal (`.block-picker-modal`) MUST include a dark overlay and a centered content panel consistent with the dashboard dark theme.
</requirements>

## Subtasks

- [x] 9.1 Define `.composer-layout`, `.composer-editor`, `.composer-preview` with the two-column grid/flex layout and responsive stacking below 900px.
- [x] 9.2 Define `.preview-iframe` and `.preview-toggle` for the iframe container and mode switch.
- [x] 9.3 Define `.hero-editor` as a visually distinct section card within the editor panel.
- [x] 9.4 Define `.block-instance`, `.block-instance-header`, `.block-instance-actions`, `.token-inputs`, `.token-label` for the block instance cards.
- [x] 9.5 Define `.add-block-btn`, `.block-picker-modal`, `.block-picker-list`, `.block-picker-item` for the block library picker UI.
- [x] 9.6 Verify no visual regressions on existing dashboard pages (landing pages index, leads, dashboard).

## Implementation Details

Append all new rules at the end of `public/assets/css/style.css` under a clearly marked section comment: `/* === Page Builder Composer === */`.

The file currently ends after `.lead-form` styles (325 lines). All additions go after line 325.

The two-column layout should give the editor panel approximately 45% width and the preview panel 55%, allowing adequate space for the preview iframe. On mobile (< 900px), both panels stack full-width.

Block instance cards use `--surface-card-dark` as background and `--hairline-on-dark` as border, matching the `.card` style. The ▲/▼ action buttons reuse `.btn-sm`.

The block picker modal uses a fixed-position overlay with `background: rgba(0,0,0,0.6)` and a centered panel using `--surface-card-dark` background.

### Relevant Files

- `public/assets/css/style.css` — modified: new rules appended after line 325
- `app/Views/landing_pages/create.php` — (task_08 output) HTML structure that these CSS classes must style
- `app/Views/landing_pages/edit.php` — same as create.php
- `public/assets/js/page-builder.js` — (task_07 output) generates `.block-instance` DOM elements with these class names

### Dependent Files

No downstream files depend on this task's output — the CSS is the final layer.

### Related ADRs

No ADRs apply directly to CSS styling decisions.

## Deliverables

- `public/assets/css/style.css` (modified: ~80-120 new lines appended)
- Visual verification that composer layout renders correctly in the browser **(REQUIRED)**
- Unit tests with 80%+ coverage **(REQUIRED)**
- Integration tests for responsive layout and no regressions **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `public/assets/css/style.css` contains the `.composer-layout` rule.
  - [ ] `public/assets/css/style.css` contains the `.block-instance` rule.
  - [ ] `public/assets/css/style.css` contains the `.preview-iframe` rule.
  - [ ] `public/assets/css/style.css` contains the `.block-picker-modal` rule.
  - [ ] None of the new rules override existing rules (no duplicate selectors with the same specificity for classes like `.card`, `.form-group`, `.btn`).
- Integration tests:
  - [ ] `GET /landing-pages/create` page in a browser at 1280px wide renders the two-column layout (editor left, preview right) with no horizontal overflow.
  - [ ] `GET /landing-pages/create` page in a browser at 800px wide renders the stacked single-column layout (editor above preview).
  - [ ] `GET /landing-pages` (index page) renders without visual regressions after the CSS addition.
  - [ ] `GET /dashboard` renders without visual regressions after the CSS addition.
  - [ ] A block instance card rendered by `page-builder.js` displays with the correct background, border, and action buttons styled as expected.
  - [ ] The block picker modal overlay appears centered with a dark background when triggered.
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- Two-column composer layout is visually consistent with the dashboard dark theme
- Responsive stacking works correctly at < 900px viewport width
- No existing dashboard page (landing-pages index, leads, dashboard) shows visual regressions
- Block instance cards, hero editor section, and picker modal are visually distinct and usable
- All new CSS uses the project's existing custom properties (`--canvas-dark`, `--primary`, etc.)
