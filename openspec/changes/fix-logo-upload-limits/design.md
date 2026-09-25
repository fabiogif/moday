# Design

## Context

- Validation: `UpdateTenantRequest` (`logo`: `image|mimes:jpeg,png,jpg,webp,svg|max:5120|dimensions:max_width=1024,max_height=1024`).
- Upload: `FileUploadService::uploadFile($file, 'logo', …)` validates size/extension with its own config and throws a plain `Exception`; `TenantApiController::update` caught every exception with `ApiResponseClass::rollback` (500).
- Resizing: `FileUploadService::processFile` already scales images down to the config's `max_width`/`max_height`.

## Decisions

1. **Single contract, enforced in three places.** Request, service config and panel all say JPG/PNG/WEBP ≤ 5MB. The `dimensions` rule is dropped because the service resizes — rejecting a 2000px logo was never necessary.
2. **Drop SVG.** The panel never offered it, and serving user-uploaded SVG from our domain is an XSS vector. Existing SVG logos are left untouched.
3. **Service errors become field errors in `TenantService`.** The service wraps `uploadFile` failures in `ValidationException::withMessages([$field => …])`; the controller returns `ApiResponseClass::validationError` for `ValidationException` (same pattern as `DeliveryRouteController`). `FileUploadService` itself is unchanged, so product and visit uploads keep their behavior.
4. **No orphan on partial failure.** Images uploaded earlier in the same request are deleted before rethrowing, and the tenant is not updated.
5. **Prune command is report-first.** `--delete` is required to remove anything, recent files (default 24h) are skipped to avoid racing an in-flight upload, and references are normalized with `ImageHelper::normalizeToStoragePath` so legacy URL values still count as referenced.

## Risks / Trade-offs

- [A corrupted image with a valid PNG header still passes both checks and is stored] → Not a 500; out of scope.
- [Deleting unreferenced files is irreversible] → Report-only default; production run only after the list is reviewed.
