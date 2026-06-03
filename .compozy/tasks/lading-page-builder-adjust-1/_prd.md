# Product Requirements Document: Unified Asset Pipeline for Landing Pages

## Overview

The landing page builder currently restricts media uploads to image files, which are saved in isolated `images/` directories. This prevents marketing and content teams from utilizing rich media such as product demonstration videos or animated clips on landing pages. 

This document defines the requirements to expand this functionality to a unified asset pipeline. By replacing the "Images" concept with "Assets," users will be able to upload both images and videos in a single interface. All uploaded media will be saved inside a consolidated `assets/` directory and served dynamically to support modern, high-converting landing page designs.

## Goals

- **Rich Media Enablement**: Allow content creators to upload and attach both images and video formats (up to 50MB) to landing page projects.
- **Simplified Storage Architecture**: Consolidate all project media under a single `assets/` folder to reduce directory hierarchy.
- **MIME Type Reliability**: Ensure the dynamic serving engine correctly determines and serves images and video content types to prevent render or playback failures.

## User Stories

- **As a marketer**, I want to upload high-quality demonstration videos alongside standard graphics during page creation so that I can create visually engaging and informative landing pages.
- **As a page editor**, I want to see a single, simple upload field for all media types so that I do not have to worry about where different files are saved.
- **As a site visitor**, I want videos and images on the landing page to load seamlessly and play smoothly in my browser.

## Core Features

### 1. Unified Asset Upload Field
- **Description**: A single drag-and-drop or file upload selector on the landing page creation form (`/landing-pages/create`).
- **Behavior**: Labeled as "Assets" instead of "Images." It accepts multiple files and supports both standard image formats and common video formats.
- **Formats**: 
  - Images: `.jpg`, `.jpeg`, `.png`, `.webp`, `.svg`, `.gif`
  - Videos: `.mp4`, `.webm`, `.ogg`
- **Upload Limit**: Maximum file size of 50MB per file to accommodate high-resolution videos.

### 2. Consolidated Asset Storage
- **Description**: A single subfolder named `assets/` within the landing page's main directory.
- **Behavior**: All uploaded files from the unified input are saved here. The old `images/` folder is no longer created or utilized for new pages.

### 3. Dynamic Video & Image Serving
- **Description**: An upgraded resource serving engine in `PublicController`.
- **Behavior**: Correctly maps, resolves, and serves files located under `/assets/`. It automatically detects and sets the correct HTTP `Content-Type` headers for served video and image extensions.

## User Experience

- **Creation Interface**: When creating a landing page, the user sees an upload section labeled **Assets (optional, images or videos up to 50MB)**.
- **Validation Errors**: Clear, real-time feedback if a file exceeds 50MB or contains an unsupported extension.
- **Page Preview**: Uploaded images and videos render correctly in both preview mode and live production pages when referenced via the `assets/` path.

## High-Level Technical Constraints

- **MIME Determination**: The system must accurately output standard browser-compatible MIME types (e.g., `video/mp4`, `image/webp`) for all served files.
- **No Complex Migrations**: Existing pages can continue utilizing their old folder structure (hybrid compatibility) or require manual recreation to transition to the new `assets/` format.

## Non-Goals (Out of Scope)

- **Video Transcoding**: The system will not compress, convert, or transcode uploaded videos automatically (users must optimize their videos prior to upload).
- **Visual Video Block Customization**: The block editor interface remains focused on editing image elements. Videos are placed and configured via direct HTML edits.
- **Dynamic File Management Gallery**: Building a full media-manager library UI for searching or deleting individual assets after upload is deferred to a future phase.

## Phased Rollout Plan

### MVP (Phase 1)
- Unified "Assets" upload field supporting up to 50MB.
- Automatic routing and dynamic rendering of images and videos from `/assets/` folders.
- Basic error messages for size and type validation.

## Success Metrics

- **Zero Asset Delivery Failures**: All uploaded media formats play back or render in the browser with 100% success.
- **Improved Page Engagement**: Ability to measure higher landing page conversion rates due to rich video content.

## Risks and Mitigations

- **Risk: Server Storage Exhaustion**  
  *Mitigation*: The 50MB file upload limit is strictly enforced, and only specified image/video extensions are allowed.
- **Risk: Poor Video Playback Performance**  
  *Mitigation*: Leverage browser-side caching headers (`Cache-Control: public, max-age=31536000`) for all served assets.

## Architecture Decision Records

- [ADR-001: Unified Asset Pipeline for Landing Pages](adrs/adr-001.md) — Consolidated all media under the `assets/` subfolder and added video upload support.

## Open Questions

- None at this time.
