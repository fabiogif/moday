## ADDED Requirements

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
