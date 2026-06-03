---
status: completed
title: Dynamic Servicing Upgrades and Video MIME Map
type: backend
complexity: medium
dependencies:
  - task_02
---

# Task 3: Dynamic Servicing Upgrades and Video MIME Map

## Overview
This task enhances the dynamic asset resolution engine to serve media from the consolidated `/assets/` directory. It maps video file extensions to their correct MIME types for playback and streams them to client browsers.

<critical>
- ALWAYS READ the PRD and TechSpec before starting
- REFERENCE TECHSPEC for implementation details — do not duplicate here
- FOCUS ON "WHAT" — describe what needs to be accomplished, not how
- MINIMIZE CODE — show code only to illustrate current structure or problem areas
- TESTS REQUIRED — every task MUST include tests in deliverables
</critical>

<requirements>
- `PublicController::asset` MUST support loading files under the consolidated `/assets/` flat directory.
- The MIME type map inside `PublicController::asset` MUST support `.mp4`, `.webm`, and `.ogg` video extensions.
- Dynamic serving MUST correctly output standard MIME type headers (`video/mp4`, `video/webm`, `video/ogg`) along with standard caching headers.
- Safe dynamic fallback using `mime_content_type()` MUST be used for unrecognized file types.
</requirements>

## Subtasks
- [x] 3.1 Update the `$mimeMap` static array in `PublicController::asset()` to include mappings for video extensions.
- [x] 3.2 Verify that the asset resolver maps `/p/{slug}/assets/{filename}` to the flat filesystem path properly.
- [x] 3.3 Add unit and integration tests in `tests/PublicAndLeadsTest.php` verifying video serving, header validation, and fallback mechanisms.

## Implementation Details
Upgrade MIME configuration and route serving mapping inside the dynamic asset controller. See TechSpec 'System Architecture - Component Overview' and 'MIME Determination' for specifications.

### Relevant Files
- `app/Controllers/PublicController.php` — Class dynamically resolving assets and setting HTTP response headers.
- `tests/PublicAndLeadsTest.php` — Test suite for verifying dynamic public rendering and resource delivery.

### Dependent Files
- None.

### Related ADRs
- [ADR-001: Unified Asset Pipeline for Landing Pages](../adrs/adr-001.md) — Unified dynamic mapping and asset serving.
- [ADR-002: Dynamic Flat Asset Serving and Validation Strategy](../adrs/adr-002.md) — Decisions on route parameters and static MIME mappings.

## Deliverables
- Upgraded `$mimeMap` and file path validation engine.
- Dynamic resource streaming integration tests verifying images and videos.
- 100% successful browser dynamic resolution headers.

## Tests
- Unit tests:
  - [x] MIME mapping resolution: Assert that passing video extensions to MIME resolver yields correct content types (`video/mp4`, etc.).
  - [x] Fallback check: Assert that unknown formats invoke dynamic fallback.
- Integration tests:
  - [x] Dynamic streaming: Perform a mock GET request for a served video and assert response status 200, correct length, and MIME type header.
- Test coverage target: >=80%
- All tests must pass

## Success Criteria
- All tests passing
- Test coverage >=80%
- Browsers correctly load and play served video and image assets dynamically from `/assets/`.
- Dynamic headers output correct standard HTTP content-types.
- Caching headers are set successfully for served assets.
