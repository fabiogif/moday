## ADDED Requirements

### Requirement: Customer session is per store
The menu SHALL keep a customer's login only for the store where the customer logged in or registered. On another store's menu the customer SHALL be treated as not logged in (the optional sign-up question is shown and no session data is used). Logging out SHALL end the session only for the current store. A session stored before this requirement, without a store, SHALL be discarded.

#### Scenario: Other store
- **WHEN** a customer registered on store A opens store B's menu in the same browser
- **THEN** store B treats the customer as not logged in

#### Scenario: Same store
- **WHEN** the customer returns to store A's menu
- **THEN** the customer is still logged in there

#### Scenario: Logout
- **WHEN** a customer logged in to stores A and B logs out on store A
- **THEN** the session on store B is kept

#### Scenario: Legacy session
- **WHEN** the browser has a customer session saved before sessions were per store
- **THEN** that session is discarded and the customer is treated as not logged in
