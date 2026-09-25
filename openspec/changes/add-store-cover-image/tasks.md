# Tasks

## 1. Backend: data and upload

- [x] 1.1 Migration `add_cover_to_tenants_table`: nullable string `cover` after `logo`, with `down()` dropping it; add `cover` to `Tenant::$fillable`; verify with `php artisan migrate` and `migrate:rollback --step=1` locally
- [x] 1.2 Add the `cover` upload config to `FileUploadService` (disk `logos`, `tenants/{tenant_uuid}/covers`, `jpg,jpeg,png,webp`, 5MB, fit 1920×1080, quality 85); verify with a `FileUploadServiceTest` case that uploads a 3000×1200 PNG and stores it resized within 1920×1080
- [x] 1.3 `UpdateTenantRequest`: `cover` (`sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120`) and `remove_cover` (`sometimes|boolean`)
- [x] 1.4 Move logo replacement from `TenantApiController::update` into `TenantService` as a helper used for `logo` and `cover` (upload new → delete old only after success; `remove_<field>` deletes and nulls); controller passes the files and no longer uses `Storage`; verify the existing `TenantApiControllerUpdateTest` still passes
- [x] 1.5 Return `cover` in `TenantResource` and in `PublicStoreService::getStoreInfo` via `ImageHelper::publicAssetPath($tenant->cover, 'logos')`
- [x] 1.6 Feature tests (`TenantApiControllerUpdateTest`, `PublicStoreControllerTest`): upload cover → 200 with `/storage/` path; replace deletes the old file (`Storage::fake`); `remove_cover` nulls and deletes; PDF and >5MB → 422 and cover kept; other tenant's uuid → 404; saving only `name` keeps the cover; logo upload and `remove_logo` still work; `store/{slug}/info` returns `cover` path or `null`
- [x] 1.7 Run the tenant and public store test files, `composer run ci:architecture` (no new violations) and `graphify update .` in `backend/`

## 2. Frontend: panel

- [x] 2.1 Extract the logo card's preview/select/remove/cancel behavior from `(dashboard)/settings/company/page.tsx` into an image upload field component (reuse an existing one if found in `components/`), keeping the logo card identical
- [x] 2.2 Add the "Capa do cardápio" card to the step (renamed "Logo e capa"): wide preview in the hero proportions, JPG/PNG/WEBP up to 5MB, help text "1600×640 px, conteúdo importante no centro"; send `cover`/`remove_cover` in the same multipart request as the logo
- [x] 2.3 Tests in `settings/company/__tests__/company-settings.test.tsx`: picking a cover sends `cover` in the FormData; removing sends `remove_cover`; a 7MB file shows the error and is not sent; logo behavior unchanged

## 3. Frontend: menu

- [x] 3.1 Add `cover?: string | null` to the menu's `StoreInfo`; pass `resolveImageUrl(storeInfo.cover) ?? resolveImageUrl((topSellers[0] ?? offerProducts[0])?.image)` to `StoreHero`
- [x] 3.2 Tests in `store/[slug]/__tests__/page.test.tsx`: with `cover` the hero image is the cover even with best-sellers; without `cover` it is the best-seller image
- [x] 3.3 Run the full frontend Jest suite, type-check and ESLint on touched files; `graphify update .` in `frontend/`

## 4. Verification (before any push)

- [x] 4.1 Locally, upload a cover in the panel and check the menu hero in a real browser at 390×844, 768×1024 and 1366×800 (image shown, no horizontal scroll, text legible over it); remove it and check the fallback returns
- [x] 4.2 Confirm the mobile app does not break with the new `cover` field in `store/{slug}/info` (or does not consume that endpoint)

## Notes (implementation)

- 1.1 verified with `migrate` / `migrate:rollback --step=1` on a throwaway SQLite database, not on the local Docker MySQL: that database belongs to the old `backend_moday` checkout the Docker container serves and is dozens of migrations behind `backend/`.
- 1.4 also fixes a pre-existing bug: the old logo was never deleted on replace (looked it up on the `public` disk) or on `remove_logo` (default disk). Deletion now uses the `logos` disk for both images. A `logo`/`cover` value that is not an uploaded file is ignored; the path only changes by upload or `remove_*`.
- 1.5 also needed `PublicStoreInfoResource` (the public info response is built there, not from the service array directly).
- 2.1 reuses the existing `ImageDropzone` (new optional `ariaLabel` / `invalidTypeMessage` props, defaults unchanged for products) for the cover picker; the shared logo/cover state lives in `settings/company/use-image-field.ts`. The logo card markup is unchanged.
- 2.1 also fixes a pre-existing bug: the panel sent `remove_logo=true`, which Laravel's `boolean` rule rejects (422); both images now send `remove_*=1`.
- 4.1 ran `backend/` on port 8001 against an isolated seeded SQLite database and a second frontend dev server on 3002 (headless Chromium, CORS check disabled for the local run): upload in the panel, cover served, menu hero at 390×844 / 768×1024 / 1366×800 with no horizontal scroll, removal deletes the file and the hero falls back — 12/12 checks.
- 4.2 neither `moday_mobile/` nor `mobile/` calls `store/{slug}/info` or the tenant API.
