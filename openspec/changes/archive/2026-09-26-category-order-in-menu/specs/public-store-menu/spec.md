# Spec Delta

## MODIFIED Requirements

### Requirement: Category sections and tabs
The menu SHALL list every category as a titled section containing its products, ordered primarily by the category's numerical display order in ascending order, and secondarily alphabetically by category name when display orders are equal. Products without any category SHALL appear in a final "Outros" section at the end of the menu. A category navigation bar SHALL be sticky while scrolling the list; activating a tab SHALL scroll to that category's section without hiding its title under the sticky header, and the active tab SHALL follow the section currently in view. A category menu button SHALL open the full list of categories in the same display order and navigate the same way.

#### Scenario: Sections ordered by category display order
- **WHEN** category "Bebidas" has order 10 and category "Pizzas" has order 1
- **THEN** the "Pizzas" section and tab appear before the "Bebidas" section and tab

#### Scenario: Equal order fallback to alphabetical
- **WHEN** categories "Sobremesas" and "Entradas" both have order 0
- **THEN** "Entradas" appears before "Sobremesas"

#### Scenario: Navigate by tab
- **WHEN** the customer activates the "Bebidas" tab
- **THEN** the page scrolls so the "Bebidas" section title is visible below the sticky header, and "Bebidas" is marked as the current tab

#### Scenario: Scroll updates tab
- **WHEN** the customer scrolls until the "Combos" section is at the top of the list
- **THEN** the "Combos" tab becomes the current tab
