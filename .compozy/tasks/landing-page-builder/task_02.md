---
status: completed
title: BlockEditorHelper for HTML Parsing
type: backend
complexity: low
dependencies: []
---

# Task 02: BlockEditorHelper for HTML Parsing

## Overview

Create a standalone PHP helper file (`app/Helpers/block_editor_helper.php`) with two functions: `parse_block_delimiters()` to detect `<!-- BLOCO_N_INICIO -->` / `<!-- BLOCO_N_FIM -->` comment pairs in uploaded HTML and extract their text content, and `rewrite_block_content()` to replace block text in the HTML while preserving all structure outside the delimiters. This helper is used by the edit view (task 04) and the LandingPagesController (task 05).

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- MUST use PHP `DOMDocument` with `libxml` error suppression for parsing (per ADR-008)
- MUST use `DOMXPath` to query for HTML comment nodes
- MUST detect comments matching `BLOCO_\d+_INICIO` and `BLOCO_\d+_FIM` patterns
- MUST extract only text content (not HTML markup) from between delimiter pairs
- MUST return block data as an array with block number, start offset, end offset, and text content
- MUST implement `rewrite_block_content()` using string-level replacement (substr-based slicing) to avoid DOM serialization reformatting issues
- MUST handle edge cases: no delimiters found, unclosed delimiters, nested HTML inside blocks
- MUST load helper via CI4's `helper('block_editor')` in controllers
</requirements>

## Subtasks

- [x] 2.1 Create `app/Helpers/block_editor_helper.php` with `parse_block_delimiters()`
- [x] 2.2 Implement `rewrite_block_content()` using string-level replacement
- [x] 2.3 Write unit tests for both functions covering all edge cases

## Implementation Details

See TechSpec "Core Interfaces — BlockEditorHelper" section for the function signatures and approach. The key design decision (ADR-008) is to use DOMDocument only for PARSING (finding comment positions) and plain string operations for REWRITING (to avoid `saveHTML()` reformatting the entire file).

### Relevant Files
- `app/Helpers/block_editor_helper.php` — new file to create
- No existing files to modify — this is a brand-new standalone helper

### Dependent Files
- `app/Views/landing_pages/edit.php` (task 04) — calls `parse_block_delimiters()` to render block textareas
- `app/Controllers/LandingPagesController.php` (task 05) — calls `rewrite_block_content()` on save

### Related ADRs
- [ADR-008: Block Editor HTML Parsing via DOMDocument](../adrs/adr-008.md) — Rationale for DOMDocument over regex or string operations

## Deliverables

- New helper file: `app/Helpers/block_editor_helper.php`
- Unit tests with 80%+ coverage **(REQUIRED)**
- Integration tests **(REQUIRED)**

## Tests

- Unit tests:
  - [ ] `parse_block_delimiters()` with no block comments returns empty array
  - [ ] `parse_block_delimiters()` with one block extracts its text content correctly
  - [ ] `parse_block_delimiters()` with multiple blocks returns them in document order
  - [ ] `parse_block_delimiters()` correctly strips HTML tags, returning only text content
  - [ ] `parse_block_delimiters()` handles empty block (no text between delimiters)
  - [ ] `rewrite_block_content()` replaces text for a single block correctly
  - [ ] `rewrite_block_content()` rewrites multiple blocks independently
  - [ ] `rewrite_block_content()` leaves HTML outside delimiters completely untouched
  - [ ] `rewrite_block_content()` handles blocks with no changes (identity)
  - [ ] Blocks with different numbering (`BLOCO_1` vs `BLOCO_10`) are correctly distinguished
- Integration tests:
  - [ ] Full round-trip: parse HTML with 3 blocks → rewrite block 2 → verify only block 2 changed
- Test coverage target: >=80%
- All tests must pass

## Success Criteria

- All tests passing
- Test coverage >=80%
- `parse_block_delimiters()` correctly identifies all block delimiters in a realistic HTML file
- `rewrite_block_content()` modifies only the target block text without altering any other part of the HTML
