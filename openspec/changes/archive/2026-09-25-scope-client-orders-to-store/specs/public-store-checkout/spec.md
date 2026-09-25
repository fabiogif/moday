## ADDED Requirements

### Requirement: Customer orders scoped to the store
`GET /store/{slug}/orders` SHALL return the authenticated customer's orders only when the customer belongs to the store `{slug}`. It SHALL respond 401 without order data when there is no valid customer session or when the customer belongs to another store.

#### Scenario: Session from another store
- **WHEN** a customer logged in to store A calls `GET /store/B/orders`
- **THEN** the system responds 401 and returns no orders

#### Scenario: Own store
- **WHEN** a customer logged in to store A calls `GET /store/A/orders`
- **THEN** the system responds 200 with that customer's orders
