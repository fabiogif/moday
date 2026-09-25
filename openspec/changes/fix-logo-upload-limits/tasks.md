# Tasks

## 1. Backend

- [x] 1.1 `UpdateTenantRequest`: `logo` → `sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120` (no SVG, no `dimensions`)
- [x] 1.2 `FileUploadService` `logo` config: 5MB, `jpg,jpeg,png,webp`
- [x] 1.3 `TenantService`: wrap `uploadFile` failures in `ValidationException` on the field; delete images already uploaded in the request before rethrowing; `TenantApiController::update` returns `validationError` for `ValidationException`
- [x] 1.4 Tests (`TenantApiControllerUpdateTest`): 3MB 2000×2000 WEBP logo → 200, resized ≤ 1024; SVG and 6MB → 422 and logo unchanged; service rejects the cover while a valid logo is sent → 422 on `cover`, nothing saved, storage empty
- [x] 1.5 `files:prune-tenant-images` command (report by default, `--delete`, `--min-age`) with `PruneTenantImagesCommandTest`: reports without deleting, deletes only unreferenced logos/covers, keeps referenced ones (relative path and legacy URL) and recent files

## 2. Frontend

- [x] 2.1 Company settings: logo picker uses the same `IMAGE_UPLOAD_ACCEPT` / `IMAGE_UPLOAD_MAX_SIZE` as the cover (rejects SVG/GIF before upload)

## 3. Verification

- [x] 3.1 Full backend suite (1524 passed, 13 skipped; one Distribtec test flaked once and passed 3/3 in isolation and on rerun), `audit:layers` 77 (baseline); full frontend suite (871 passed)
- [ ] 3.2 Production: run `php artisan files:prune-tenant-images` (report only), review the list, then run with `--delete` — manual, after deploy
