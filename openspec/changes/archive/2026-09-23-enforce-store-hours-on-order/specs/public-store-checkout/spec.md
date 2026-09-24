# Spec Delta

## RENAMED Requirements

- FROM: `### Requirement: Opening hours are informational`
- TO: `### Requirement: Opening hours gate public orders`

## MODIFIED Requirements

### Requirement: Opening hours gate public orders
`GET /store/{slug}/is-open` SHALL return `is_open`, `is_always_open`, `current_time`, `current_day` and the active opening periods grouped by day (up to two periods per day, each with `start`, `end`, `delivery_type`). It SHALL accept an optional `delivery_type` query parameter (`delivery` or `pickup`); when given, `is_open` SHALL consider only periods of that type or `both`, and when absent any period counts. Opening hours SHALL be evaluated in the server's timezone.

The menu SHALL disable starting checkout while the store is closed, treating it as open while the status is loading, when the status request fails, or when no hours are configured. When the customer confirms the shipping step, the menu SHALL check `is-open` for the chosen shipping method and SHALL NOT advance if it is closed, showing a message that names the shipping method.

The order submission API SHALL reject a public order with 422 and an error on `shipping_method` when the store has at least one active opening period, is not marked always open, and no active period of today matching the order's `shipping_method` (or typed `both`) contains the current time.

#### Scenario: Menu while closed
- **WHEN** the customer opens the menu of a store that is closed
- **THEN** the button to start checkout is disabled

#### Scenario: No hours configured
- **WHEN** the store has no active opening hours
- **THEN** the menu allows checkout and the order submission API accepts the order

#### Scenario: Store closed
- **WHEN** the store is outside its opening hours
- **THEN** `is-open` returns `is_open: false`

#### Scenario: Order while closed
- **WHEN** a valid order is submitted while the store is outside all of today's periods
- **THEN** the system responds 422 with an error on `shipping_method` saying the store is closed, and no order is created

#### Scenario: Always open
- **WHEN** the store is marked always open and an order is submitted at any time
- **THEN** the order is accepted

#### Scenario: Delivery closed, pickup open
- **WHEN** the only period covering the current time is typed `pickup` and a `delivery` order is submitted
- **THEN** the system responds 422 on `shipping_method` saying delivery is not available right now, and a `pickup` order at the same time is accepted

#### Scenario: Period typed both
- **WHEN** the period covering the current time is typed `both`
- **THEN** both `delivery` and `pickup` orders are accepted

#### Scenario: Second period of the day
- **WHEN** the day has periods 11:00–14:00 and 18:00–23:00 and an order is submitted at 19:00
- **THEN** the order is accepted

#### Scenario: is-open filtered by shipping method
- **WHEN** the only current period is typed `pickup` and the menu calls `is-open?delivery_type=delivery`
- **THEN** the response has `is_open: false`, while `is-open?delivery_type=pickup` and `is-open` without the parameter return `is_open: true`

#### Scenario: Shipping step blocked in the menu
- **WHEN** the customer chooses delivery and confirms the shipping step while delivery is closed
- **THEN** the menu stays on the shipping step and shows a message that delivery is not available right now
