# public-store-menu Specification

## Purpose
Defines what the customer sees and can do on the public menu screen of a store (`/store/{slug}`, before checkout): store summary, featured coupons, product showcases, category navigation, search, product rows and the cart bar.

## Requirements

### Requirement: Store hero summary
The menu SHALL open with a store hero showing the store logo (or an initial placeholder), the store name and the average rating with the review count when the store has approved reviews. The hero SHALL show an info strip with the open/closed status, the delivery fee rule ("Grátis acima de R$ X" when a free-delivery threshold is configured, otherwise "Calculada no endereço") and pickup information (time and/or discount) when pickup is enabled. When no cover image exists, the hero SHALL use a background derived from the store's primary color and, when available, the best-selling product image.

#### Scenario: Rating shown
- **WHEN** the store has approved reviews with average 4.8 over 22 reviews
- **THEN** the hero shows "4,8" and "(22)", and activating it scrolls to the reviews section

#### Scenario: No reviews
- **WHEN** the store has no approved reviews
- **THEN** the hero shows no rating

#### Scenario: Free delivery threshold
- **WHEN** the store configures free delivery above R$ 80,00
- **THEN** the info strip shows "Grátis acima de R$ 80,00"

### Requirement: Orders shortcut on the menu
The menu hero SHALL show a "Meus pedidos" shortcut that leads to the store's order tracking page (`/store/{slug}/track`), which works without login.

#### Scenario: Open my orders from the menu
- **WHEN** the customer activates "Meus pedidos" on the menu
- **THEN** the store's order tracking page opens

### Requirement: Share store link
The hero SHALL offer a share action. It SHALL use the device's native share when available and otherwise copy the menu URL to the clipboard and confirm it to the customer.

#### Scenario: Browser without native share
- **WHEN** the customer activates share in a browser without native share support
- **THEN** the menu URL is copied and a confirmation message is shown

### Requirement: Featured coupons strip
When the store has active featured coupons, the menu SHALL list them in a horizontal strip showing each coupon's discount highlight and name. Activating a coupon SHALL copy its code and confirm it. When there are no coupon slides, the strip SHALL NOT be rendered.

#### Scenario: Copy coupon code
- **WHEN** the customer activates a coupon card with code "MODAY5"
- **THEN** "MODAY5" is copied and a confirmation message is shown

#### Scenario: No coupons
- **WHEN** the promotions endpoint returns no coupon slides or fails
- **THEN** no coupon strip is shown and the rest of the menu renders normally

### Requirement: Offers and best-sellers showcases
With an empty search, the menu SHALL show an "Ofertas" showcase with the discounted products ordered by highest discount percentage, and a "Preferidos" showcase with products that have sales ordered by quantity sold, each card showing its rank number (1, 2, 3...). A showcase with no products SHALL NOT be rendered. For a product with variations, the displayed price SHALL be prefixed by "a partir de" and SHALL equal the product price plus the cheapest variation price.

#### Scenario: Offer ordering
- **WHEN** products have discounts of 25%, 50% and 40%
- **THEN** "Ofertas" lists them in the order 50%, 40%, 25%, each with its discount pill

#### Scenario: Price from variations
- **WHEN** a product costs R$ 30,00 and its variations add R$ 5,00 and R$ 10,00
- **THEN** its card shows "a partir de" and R$ 35,00

#### Scenario: No sales yet
- **WHEN** no product has sales
- **THEN** the "Preferidos" showcase is not shown

### Requirement: Category sections and tabs
The menu SHALL list every category as a titled section containing its products, in category order, instead of filtering by category. A category navigation bar SHALL be sticky while scrolling the list; activating a tab SHALL scroll to that category's section without hiding its title under the sticky header, and the active tab SHALL follow the section currently in view. A category menu button SHALL open the full list of categories and navigate the same way.

#### Scenario: Navigate by tab
- **WHEN** the customer activates the "Bebidas" tab
- **THEN** the page scrolls so the "Bebidas" section title is visible below the sticky header, and "Bebidas" is marked as the current tab

#### Scenario: Scroll updates tab
- **WHEN** the customer scrolls until the "Combos" section is at the top of the list
- **THEN** the "Combos" tab becomes the current tab

### Requirement: Menu search
The sticky header SHALL contain a search field labeled with the store name. While the search has text, the menu SHALL replace the category sections and showcases with a single list of products whose name or description contains the text (case-insensitive), and SHALL show an empty-state message when nothing matches. Clearing the search SHALL restore the sections.

#### Scenario: Search results
- **WHEN** the customer types "yakisoba"
- **THEN** only products whose name or description contains "yakisoba" are listed, without category sections

#### Scenario: No match
- **WHEN** the search matches no product
- **THEN** the message "Nenhum produto encontrado para sua busca." is shown

### Requirement: Product row
Each product row SHALL show the name, up to two lines of description, the current price and, when discounted, the original price struck through and the discount percentage; the product image or a placeholder illustration SHALL appear on the right. Activating the row SHALL open the product details. The row SHALL have an add button over the image that adds the product directly when it has no variations or optionals, and opens the product details otherwise. A product with zero stock SHALL be shown as sold out and SHALL NOT be addable.

#### Scenario: Quick add simple product
- **WHEN** the customer activates the add button of a product without variations or optionals
- **THEN** the product is added to the cart and the product details do not open

#### Scenario: Quick add customizable product
- **WHEN** the customer activates the add button of a product with variations
- **THEN** the product details open

#### Scenario: Sold out
- **WHEN** a product has zero stock
- **THEN** its row shows "Esgotado" and no add button

### Requirement: Menu cart bar
While the cart has items on the menu step, the menu SHALL show a bottom cart bar with the cart total without delivery, the item count and a "Ver carrinho" action that opens the cart summary. Above it the bar SHALL show at most one informative message, in this priority: amount missing to free delivery (when configured and not reached), savings from discounted items in the cart. The delivery minimum order SHALL NOT be shown on the menu (deferred to a future feature). The informative message SHALL NOT block any action. The order progress stepper SHALL NOT be shown on the menu step.

#### Scenario: Below free delivery
- **WHEN** the store configures free delivery above R$ 80,00 and the cart total is R$ 50,00
- **THEN** the bar shows "Faltam R$ 30,00 para entrega grátis"

#### Scenario: Savings
- **WHEN** free delivery is not configured and the cart has items saving R$ 12,75 in total
- **THEN** the bar shows "Você economiza R$ 12,75"

#### Scenario: Open cart
- **WHEN** the customer activates "Ver carrinho"
- **THEN** the cart summary opens
