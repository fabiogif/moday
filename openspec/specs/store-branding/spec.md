# store-branding Specification

## Purpose
Store visual identity managed by the restaurant owner in the panel — currently the cover image of the public menu — and how it is exposed by the tenant and public store APIs.

## Requirements

### Requirement: Store cover image upload
A store owner SHALL be able to upload a cover image for the store's public menu in the company settings, next to the logo. The update endpoint (`PUT /api/tenant/{uuid}`, or `POST` with `_method=PUT` for multipart) SHALL accept `cover` as an image file (JPG, JPEG, PNG or WEBP, at most 5MB) and `remove_cover` as a boolean. Only an authenticated user of the same tenant SHALL be able to change it; another tenant's uuid SHALL respond 404. Images larger than 1920×1080 SHALL be resized before storing. Replacing or removing the cover SHALL delete the previous file; a failed upload SHALL keep the current cover. Sending neither field SHALL leave the cover unchanged.

#### Scenario: Upload a cover
- **WHEN** the owner saves the company settings with a 1.2MB JPG as `cover`
- **THEN** the system responds 200 and the tenant response includes a `cover` path under the store's covers folder

#### Scenario: Replace a cover
- **WHEN** a store with a cover uploads a new one
- **THEN** the new cover is returned and the previous file no longer exists in storage

#### Scenario: Remove a cover
- **WHEN** the owner saves with `remove_cover` true and no file
- **THEN** the tenant `cover` becomes null and the previous file is deleted

#### Scenario: Invalid file
- **WHEN** `cover` is a PDF or an image larger than 5MB
- **THEN** the system responds 422 with an error on `cover` and the current cover is kept

#### Scenario: Another tenant
- **WHEN** a user of store A sends a cover to store B's uuid
- **THEN** the system responds 404 and store B's cover is unchanged

#### Scenario: Other fields only
- **WHEN** the owner saves only the company name
- **THEN** the cover is unchanged

### Requirement: Cover exposed by the tenant and public store APIs
`GET /api/tenant/{uuid}` and the public `GET /api/store/{slug}/info` SHALL include `cover` as a public path (`/storage/...`) when the store has a cover, and `null` otherwise.

#### Scenario: Store with cover
- **WHEN** a client calls `GET /api/store/{slug}/info` for a store with a cover
- **THEN** the response includes `cover` with a `/storage/` path

#### Scenario: Store without cover
- **WHEN** a client calls `GET /api/store/{slug}/info` for a store without a cover
- **THEN** the response includes `cover: null`

### Requirement: Cover managed in the company settings panel
The company settings SHALL show a "Capa do cardápio" card in the logo step with a preview in the menu banner's proportions, a file picker accepting JPG, PNG and WEBP up to 5MB, and actions to remove the cover or cancel a pending removal. The card SHALL recommend 1600×640 px with the important content centered. Choosing an invalid file SHALL show an error and SHALL NOT be sent.

#### Scenario: Preview before saving
- **WHEN** the owner picks a valid image
- **THEN** the card shows its preview and the file is sent when the settings are saved

#### Scenario: File too large
- **WHEN** the owner picks a 7MB image
- **THEN** the panel shows that the file must be at most 5MB and nothing is uploaded

### Requirement: Store logo upload
The company settings SHALL accept a store logo as JPG, JPEG, PNG or WEBP of at most 5MB, and the update endpoint (`PUT /api/tenant/{uuid}`) SHALL apply the same rule to `logo`. Logos larger than 1024×1024 SHALL be resized, not rejected. SVG SHALL NOT be accepted. `remove_logo` SHALL clear the logo. Replacing or removing the logo SHALL delete the previous file. A file rejected while being stored (logo or cover) SHALL respond 422 with the reason on that field; when several images are sent together and one is rejected, none of them SHALL be kept and the current images SHALL stay unchanged.

#### Scenario: Large WEBP logo
- **WHEN** the owner uploads a 3MB, 2000×2000 WEBP logo
- **THEN** the system responds 200 and stores the logo resized within 1024×1024

#### Scenario: SVG or oversized logo
- **WHEN** the owner uploads an SVG logo or a 6MB PNG
- **THEN** the system responds 422 with an error on `logo` and the current logo is kept

#### Scenario: Storage rejects one of two images
- **WHEN** the owner sends a valid logo and a cover that the upload service rejects
- **THEN** the system responds 422 with the reason on `cover`, neither image is saved and no uploaded file is left in storage

#### Scenario: Replace logo
- **WHEN** a store with a logo uploads a new one
- **THEN** the previous logo file no longer exists in storage
