## ADDED Requirements

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
