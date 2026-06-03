# Technical Specification: Unified Asset Pipeline for Landing Pages

## Executive Summary

This technical specification details the transition from an image-only upload pipeline to a unified media asset pipeline for the landing page builder. We are replacing the previous `images/` directory layout with a flat, unified `assets/` directory to accommodate both image and video assets.

The key architectural decision is to utilize CodeIgniter's native file validation library in a single upload controller while maintaining the existing dynamic asset resolution routes. The primary technical trade-off of this approach is simplicity and low overhead: by storing files flat in the local filesystem without keeping individual asset metadata in a database, we ensure maximum speed, though we trade off advanced capabilities like search and paging of individual assets.

## System Architecture

### Component Overview

The media pipeline consists of three main components:

1. **Upload Component (`LandingPagesController`)**:
   - Validates the incoming files for format and size limits.
   - Creates the flat `assets/` directory inside the page directory if it does not exist.
   - Moves uploaded files directly into the `assets/` folder.

2. **UI Component (`landing_pages/create.php`)**:
   - Replaces the visual references from "Images" to "Assets."
   - Supports selecting and uploading multiple files of both image and video types.

3. **Asset Serving Component (`PublicController`)**:
   - Processes file serving requests under `p/{slug}/assets/{filename}`.
   - Automatically determines MIME types and outputs appropriate caching and playback headers.

```mermaid
graph TD
    UI[landing_pages/create.php] -->|POST assets[]| Controller[LandingPagesController::store]
    Controller -->|Save flat| Storage[writable/landing_pages/slug/assets/]
    Visitor[Site Visitor Browser] -->|Request p/slug/assets/file| Public[PublicController::asset]
    Public -->|Read file| Storage
    Public -->|Stream response with MIME| Visitor
```

## Implementation Design

### Core Interfaces

To model the asset structure across component boundaries, the system defines the following Core Asset interface using a Go struct definition for standard component interoperability:

```go
package asset

// Asset represents a dynamic media resource associated with a landing page.
type Asset struct {
	Name     string `json:"name"`
	MimeType string `json:"mime_type"`
	Size     int64  `json:"size"`
	FilePath string `json:"file_path"`
}

// Service defines the contract for storing and resolving landing page assets.
type Service interface {
	Store(pageSlug string, file []byte, name string) (*Asset, error)
	Resolve(pageSlug string, name string) (*Asset, error)
}
```

In PHP, the backend utilizes CodeIgniter's built-in request validation for the assets:

```php
$rules = [
    'assets' => 'if_exist|ext_in[assets,jpg,jpeg,png,webp,svg,gif,mp4,webm,ogg]|max_size[assets,51200]',
];
```

### Data Models

No new database tables or schemas are required for this feature, adhering strictly to YAGNI. The landing page record in the database stores only the base `file_path` (e.g., `landing_pages/{slug}`), and assets are discovered and loaded dynamically from the filesystem within that path.

### API Endpoints

The asset management functionality uses the following routes:

#### 1. Page Creation Form
* **Method**: `GET`
* **Path**: `/landing-pages/create`
* **Description**: Renders the form to create a new page, updated with the new "Assets" upload field.

#### 2. Save Page and Assets
* **Method**: `POST`
* **Path**: `/landing-pages`
* **Description**: Receives title, slug, HTML, CSS, JS, and `assets[]` files.
* **Payload**: `multipart/form-data` with `assets[]` as an array of files.
* **Response**: `302 Redirect` to `/landing-pages` on success.

#### 3. Serve Dynamic Asset
* **Method**: `GET`
* **Path**: `/p/(:any)/assets/(:any)`
* **Description**: Serves a dynamic media file directly to the browser.
* **Response**: Binary stream of the file with `Content-Type` matching the asset MIME type and long-lived cache headers.

## Integration Points

No external third-party services are integrated for this feature. All assets are stored on the local server filesystem.

## Impact Analysis

| Component | Impact Type | Description and Risk | Required Action |
|-----------|-------------|---------------------|-----------------|
| `app/Views/landing_pages/create.php` | Modified | Front-end input rename and accept type enlargement. Low risk. | Rename field from `images[]` to `assets[]` and update helper text. |
| `app/Controllers/LandingPagesController.php` | Modified | Change validation rule from `images` to `assets`, expand file size rules, and change the saving subdirectory. Medium risk. | Implement updated validation and save to `/assets/` instead of `/images/`. |
| `app/Controllers/PublicController.php` | Modified | Add support for video MIME types and serve from `/assets/`. Low risk. | Add video extensions to `$mimeMap` and update path checking. |

## Testing Approach

### Unit Tests

- **Upload Validation Tests**: Write unit tests passing files larger than 50MB or with unapproved extensions (e.g., `.exe`, `.zip`) to ensure the validator rejects them.
- **MIME Helper Tests**: Test that MIME types are resolved accurately for `.mp4`, `.webm`, and `.ogg` files.

### Integration Tests

- **End-to-End Asset Upload**: Mock a `POST` request to `/landing-pages` with a sample video file and verify that it is written flat to the `/assets/` directory.
- **Dynamic Playback Test**: Perform an HTTP GET request to `/p/{slug}/assets/{video.mp4}` and assert that it returns `HTTP 200 OK` with the exact header `Content-Type: video/mp4`.

## Development Sequencing

### Build Order

1. **Step 1: Frontend Update**
   - Update `app/Views/landing_pages/create.php` to rename "Images" to "Assets" and expand allowed file formats. No dependencies.
2. **Step 2: Controller Validation & Storage**
   - Update `LandingPagesController::store()` to apply the new 50MB and video-allowed validation rules and save files to the flat `assets/` directory. Depends on **Step 1**.
3. **Step 3: Dynamic Servicing & MIME Map**
   - Extend the `$mimeMap` array inside `PublicController::asset()` to correctly map video extensions and allow serving from the new flat `assets/` directory. Depends on **Step 2**.

### Technical Dependencies

No blocking external infrastructure or dependencies exist. The feature utilizes built-in CodeIgniter libraries.

## Monitoring and Observability

- **Validation Failure Logs**: Log any occurrences where users attempt to upload files exceeding 50MB.
- **Asset Serving Errors**: Log failures where requested files under `/assets/` are not found on the disk.

## Technical Considerations

### Key Decisions

- **Decision**: Serve video files directly through PHP using `PublicController::asset` rather than static webserver mapping.
- **Rationale**: Keeps the simple dynamic asset routing framework intact and enforces authorization filters in the future if required.
- **Trade-offs**: Slightly higher CPU usage for PHP file streaming compared to direct Nginx/Apache static serving.

### Known Risks

- **Server Space Depletion**: Users uploading large videos (50MB each) could fill the server disk.
- **Mitigation**: Standard disk-monitoring alerts and maximum file limits of 50MB.

## Architecture Decision Records

- [ADR-001: Unified Asset Pipeline for Landing Pages](adrs/adr-001.md) — Consolidated all media under the `assets/` subfolder and added video upload support.
- [ADR-002: Dynamic Flat Asset Serving and Validation Strategy](adrs/adr-002.md) — Standardized flat directory routing, CodeIgniter framework validation rules, and explicit video MIME types.
