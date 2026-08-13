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

After implementation, from inside the app directory you touched (`backend/` or `frontend/`):

graphify update .

(AST-only, no API cost, when only code changed. See CLAUDE.md for the per-app graphify-out/ layout — there is no multi-repo merge in this project.)
