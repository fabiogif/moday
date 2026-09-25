# Proposal

## Why

The store logo upload had three different contracts: the panel accepted JPG/PNG/WEBP up to 5MB, `UpdateTenantRequest` accepted those plus SVG up to 5MB but rejected images wider or taller than 1024px, and `FileUploadService`'s `logo` config allowed only 1MB and no WEBP. A WEBP logo, or any logo between 1MB and 5MB, passed validation and then failed inside the upload service — surfacing as an HTTP 500. The old logo file was also never deleted on replace/remove (fixed with the cover change).

## What Changes

- One contract for the logo in panel, request and service: JPG, PNG or WEBP, at most 5MB. SVG is no longer accepted (user-uploaded SVG can carry script). Large images are resized to fit 1024×1024 instead of being rejected.
- A file rejected inside `FileUploadService` (logo or cover) responds 422 with the reason on the field, instead of 500. When the logo and the cover are sent together and one fails, the file already uploaded in that request is discarded and both current images are kept.
- New `files:prune-tenant-images` command lists (and with `--delete` removes) logo/cover files no tenant references — the files left behind by the old deletion bug. Report-only by default; files modified in the last 24h are skipped.

## Capabilities

### New Capabilities
- (none)

### Modified Capabilities
- `store-branding`: adds "Store logo upload".

## Impact

- Backend: `UpdateTenantRequest`, `FileUploadService` (`logo` config), `TenantService` (upload errors → `ValidationException`, cleanup of partial uploads), `TenantApiController::update` (422 on `ValidationException`), new `PruneTenantImages` command; tests in `TenantApiControllerUpdateTest` and `PruneTenantImagesCommandTest`.
- Frontend: company settings logo picker checks the same types/size as the cover (`IMAGE_UPLOAD_ACCEPT` / `IMAGE_UPLOAD_MAX_SIZE`).
- Existing GIF/SVG logos stay as they are; only new uploads are restricted.
- Running the prune command in production is a separate, manual step (report first, delete after review).
