## MODIFIED Requirements

### Requirement: Store hero summary
The menu SHALL open with a store hero showing the store logo (or an initial placeholder), the store name and the average rating with the review count when the store has approved reviews. The hero SHALL show an info strip with the open/closed status, the delivery fee rule ("Grátis acima de R$ X" when a free-delivery threshold is configured, otherwise "Calculada no endereço") and pickup information (time and/or discount) when pickup is enabled. The hero background SHALL be the store's uploaded cover image when one exists. When no cover image exists, the hero SHALL use a background derived from the store's primary color and, when available, the best-selling product image (or, without best-sellers, the first product on offer).

#### Scenario: Rating shown
- **WHEN** the store has approved reviews with average 4.8 over 22 reviews
- **THEN** the hero shows "4,8" and "(22)", and activating it scrolls to the reviews section

#### Scenario: No reviews
- **WHEN** the store has no approved reviews
- **THEN** the hero shows no rating

#### Scenario: Free delivery threshold
- **WHEN** the store configures free delivery above R$ 80,00
- **THEN** the info strip shows "Grátis acima de R$ 80,00"

#### Scenario: Uploaded cover
- **WHEN** the store has a cover image and also has best-selling products
- **THEN** the hero background is the cover image, not a product image

#### Scenario: No cover
- **WHEN** the store has no cover image and has a best-selling product with an image
- **THEN** the hero background is that product's image, as before this change
