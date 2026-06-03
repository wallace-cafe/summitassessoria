---
status: completed
title: Frontend "Assets" Interface Update
type: frontend
complexity: low
dependencies: []
---

# Task 1: Frontend "Assets" Interface Update

## Overview
This task renames all frontend visual and functional references from "Images" to "Assets" on the landing page creation form. It updates the file picker to accept both image and common video file formats, preparing the UI for the unified pipeline.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- The HTML file input MUST be renamed from `images[]` to `assets[]`.
- The label text MUST change from "Images" to "Assets."
- The `accept` attribute MUST be updated to support images (`.jpg, .jpeg, .png, .webp, .svg, .gif`) and video formats (`.mp4, .webm, .ogg`).
- Helper/small text MUST display the new 50MB file size limit and list supported video types.
</requirements>

## Subtasks
- [x] 1.1 Update the label in `app/Views/landing_pages/create.php` from "Images" to "Assets".
- [x] 1.2 Update the upload input element `name` and `id` attributes to `assets[]` and `assets`.
- [x] 1.3 Update the `accept` attribute to include both images and standard video file extensions.
- [x] 1.4 Adjust the helper text to guide users about the 50MB limit and video compatibility.

## Implementation Details
Modify the front-end layout to receive the unified pipeline inputs. See TechSpec 'System Architecture - Component Overview' for details.

### Relevant Files
- `app/Views/landing_pages/create.php` — View file containing the page creation form field and file selector.

### Dependent Files
- `app/Controllers/LandingPagesController.php` — Will receive the renamed post payload `assets[]` instead of `images[]`.

### Related ADRs
- [ADR-001: Unified Asset Pipeline for Landing Pages](../adrs/adr-001.md) — Establishes renaming to assets and video format expansion.

## Deliverables
- Updated frontend view file for page creation.
- Browser test verifying field label and file input accept attributes.
- Unit tests for form rendering verified.

## Tests
- Unit tests:
  - [x] Form rendering: Verify that the view renders the "Assets" input with name `assets[]` and correct `accept` extensions.
- Integration tests:
  - [x] Page form checks: Ensure the creation route returns HTTP 200 with the upgraded asset field visible.
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- The landing page builder creation UI shows "Assets" instead of "Images".
- Users can select standard video formats (`.mp4, .webm, .ogg`) from their file system.

