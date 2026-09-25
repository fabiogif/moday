# Spec Delta

## MODIFIED Requirements

### Requirement: Paired sales order and operational order
Each accepted public order SHALL produce two records sharing the same order number (`identify`):
- a sales order with status `orcamento`;
- an operational order with `origin = store`, the tenant's initial order status (falling back to "Em Preparo" when none is marked initial), the payment method name and uuid, coupon data, delivery data when `is_delivery` is true, the delivery notes as comment, the customer's WhatsApp notification preference (default true), and one product line per distinct product with quantities summed.

Orders originating from external marketplace integrations (such as iFood) SHALL have `origin = 'ifood'` and SHALL link to the external order reference while producing the same operational order representation on the kitchen board.

#### Scenario: Same product twice
- **WHEN** the order lists the same product in two lines with quantities 1 and 2
- **THEN** the operational order has a single line for that product with quantity 3

#### Scenario: Tenant with custom initial status
- **WHEN** the tenant has an order status marked as initial
- **THEN** the operational order starts in that status

#### Scenario: Order imported from iFood marketplace
- **WHEN** an order is ingested from the iFood integration
- **THEN** the operational order is created with `origin = 'ifood'`, the initial status, and appears in the kitchen board alongside store and PDV orders
