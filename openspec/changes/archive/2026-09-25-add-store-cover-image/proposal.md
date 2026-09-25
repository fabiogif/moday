# Proposal

## Why

The menu hero (the large banner at the top of `/store/{slug}`) has no image of its own: it shows the photo of the best-selling product, or of the first product on offer, and falls back to a purple gradient. The restaurant owner cannot choose it — the banner changes on its own whenever the best-seller changes, and a restaurant cannot show its façade, dining room or signature dish. The logo already has an upload in the company settings; the cover is the missing half of the store's visual identity.

## What Changes

- New optional **cover image** per store, uploaded in the panel's company settings next to the logo (preview, replace, remove).
- `PUT/POST /api/tenant/{uuid}` accepts `cover` (image file) and `remove_cover` (boolean), with the same ownership checks as the logo. Replacing or removing deletes the previous file.
- `GET /api/tenant/{uuid}` and the public `GET /api/store/{slug}/info` return `cover` (public path, or null).
- The menu hero uses the store's cover when set; otherwise it keeps today's behavior (best-seller image → first offer image → gradient).
- The image replacement logic (upload, delete old file, remove flag) moves from `TenantApiController::update` into `TenantService`, shared by logo and cover, so the controller stays thin and the two images behave the same.

## Capabilities

### New Capabilities
- `store-branding`: upload, replacement and removal of the store's cover image in company settings, and its exposure in the tenant and public store APIs.

### Modified Capabilities
- `public-store-menu`: "Store hero summary" uses the uploaded cover first, before the product-image fallback.

## Impact

- Database: migration adding nullable `tenants.cover` (string). Additive; no data backfill.
- Backend: `Tenant` model (`$fillable`), `UpdateTenantRequest`, `FileUploadService` (new `cover` upload config), `TenantService`, `TenantApiController`, `TenantResource`, `PublicStoreService::getStoreInfo`; feature tests in `tests/Feature/Tenant/TenantApiControllerUpdateTest.php` and `tests/Feature/PublicStoreControllerTest.php`.
- Frontend: `(dashboard)/settings/company/page.tsx` (cover card in the "Logo" step), store menu `store/[slug]/page.tsx` (hero source) and its `StoreInfo` type.
- Public API: `store/{slug}/info` gains a field (additive, backward compatible). No mobile impact expected — confirm the mobile app ignores unknown fields.
- Out of scope (follow-up): the existing logo upload accepts WEBP and up to 5MB in the panel and in `UpdateTenantRequest`, but `FileUploadService`'s `logo` config allows neither WEBP nor more than 1MB, so such uploads fail with a 500. Worth a separate fix; this change does not repeat the mismatch for the cover.
