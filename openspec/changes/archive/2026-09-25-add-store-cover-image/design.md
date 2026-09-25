# Design

## Context

- Hero: `store/[slug]/components/store-hero.tsx` already takes `coverImageUrl` and renders it with `next/image` (`fill`, `object-cover`) under a dark gradient; `page.tsx` passes `resolveImageUrl((topSellers[0] ?? offerProducts[0])?.image)`. Height: `h-44` (176px) on phones, `sm:h-56`, `lg:h-72` (288px) on desktop, full width — roughly 2.2:1 on a phone and 4:1 on desktop, so the image is cropped around its center.
- Logo upload today: `(dashboard)/settings/company/page.tsx` step "Logo" sends `multipart` `POST /api/tenant/{uuid}` with `_method=PUT`, `logo` and/or `remove_logo`. `TenantApiController::update` checks ownership (`AuthTenantService` + uuid → 404 for another tenant), deletes the old file on the `public` disk from the stored URL path and calls `FileUploadService::uploadFile($file, 'logo', $uuid)` (disk `logos`, `tenants/{uuid}/logos`, resize to 1024px). `TenantService::update` handles `remove_logo`. `UpdateTenantRequest` validates `logo` and `remove_logo`.
- Public info: `PublicStoreService::getStoreInfo` returns `logo` via `ImageHelper::publicAssetPath($tenant->logo, 'logos')`. No cache on this payload.
- Disks: `logos` is `storage/app/public/logos` locally (or S3 when `FILESYSTEM_PUBLIC_DRIVER=s3`), served under `/storage/logos/...`.

## Goals / Non-Goals

**Goals:**
- Store owner can upload, replace and remove a cover image; the menu shows it.
- Logo and cover share one server-side replacement path.
- Menus of stores without a cover look exactly as today.

**Non-Goals:**
- Cropping/focal-point editor in the panel (the recommended aspect ratio and centered cropping are enough for a first version).
- Multiple covers / carousel, or per-device images.
- Fixing the pre-existing logo WEBP/size mismatch (proposal follow-up).
- Changing the menu for the mobile app.

## Decisions

1. **Column `tenants.cover` (nullable string), storing the path like `logo`.** Mirrors the logo; no JSON in `settings`, because the file lifecycle (delete on replace/remove) and resource formatting already work per column.
2. **Upload config `cover` in `FileUploadService`, on the existing `logos` disk under `tenants/{tenant_uuid}/covers`.** Reuses a disk already configured for local and S3 (no new disk, no deploy config). Limits: `jpg, jpeg, png, webp`, max 5MB, resized to fit 1920×1080, quality 85. `UpdateTenantRequest`: `cover` → `sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120` (no `dimensions` rule — the service resizes), `remove_cover` → `sometimes|boolean`. Panel, request and service accept the same types and size, avoiding the logo's mismatch.
3. **Move image replacement into `TenantService`.** A private helper handles one field (`logo` or `cover`): upload the new file and delete the previous one, or delete and null it when `remove_<field>` is set. `TenantApiController::update` passes the validated data plus the uploaded files and no longer touches `Storage`. The old file is deleted only after the new upload succeeds, so a failed upload keeps the current image. Rejected: copying the logo block in the controller for the cover (duplication, logic in the controller).
4. **Hero source order on the menu: `storeInfo.cover` → best-seller image → first offer image → gradient.** Only `page.tsx` changes; `StoreHero` already supports `coverImageUrl`. The cover is the LCP element on the menu, so it keeps `priority`.
5. **Panel UI in the existing "Logo" step (renamed "Logo e capa").** A second card "Capa do cardápio" with a wide preview in the hero's proportions, "Remover capa"/"Cancelar remoção", and help text recommending 1600×640 px with the important content centered. The logo card's preview/select/remove behavior is reused by extracting it into a small image upload field used by both cards, instead of duplicating ~100 lines. The form sends `cover`/`remove_cover` in the same multipart request as the logo.

## Risks / Trade-offs

- [Refactoring the logo path while adding the cover] → Covered by the existing `TenantApiControllerUpdateTest` plus new tests for logo replace/remove; behavior kept identical.
- [Large images slow the menu] → Server resizes to 1920px and compresses; `next/image` serves responsive sizes.
- [Important content cropped on phones] → Help text recommends the ratio and centered content; hero keeps its gradient overlay for legibility.
- [Public payload change] → Additive field; web menu reads it; mobile app assumed to ignore unknown fields (verify).

## Migration Plan

1. Deploy backend (migration adds a nullable column; safe with the old frontend).
2. Deploy frontend.
Rollback: revert the frontend first, then the backend; the migration `down()` drops the column (cover files remain in storage and can be removed manually).
