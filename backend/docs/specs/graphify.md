# Graphify First Workflow

Graphify is the primary source for understanding the architecture.

Before reading source code, always execute:

graphify query "<request>"

or

graphify explain "<concept>"

or

graphify path "<A>" "<B>"

Never explore source files before Graphify.

---

## Discovery

Identify:

- Controllers
- Services
- Repositories
- Models
- Routes
- Middleware
- Form Requests
- Policies
- Resources
- React Pages
- Components
- Hooks
- API Services

---

## Dependency Analysis

Always identify:

- incoming dependencies
- outgoing dependencies
- related modules

---

## Impact Analysis

Before implementing:

- affected files
- affected APIs
- affected database
- frontend impact
- security impact
- performance impact

---

After implementation:

graphify extract . --code-only