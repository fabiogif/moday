# Moday Rules

Before implementing any feature:

1. Discover the complete flow using Graphify.
2. Identify all dependencies.
3. Produce an impact analysis.
4. Search for reusable implementations.
5. Only then implement.

---

Always preserve:

Routes

↓

Controller

↓

FormRequest

↓

Service

↓

Repository

↓

Model

↓

Resource

Never bypass this architecture.

---

Before creating:

- Controller
- Service
- Repository
- Component
- Hook
- Utility
- Modal

Search for an existing implementation.

Prefer extending existing modules.

Never duplicate business logic.

Always follow the existing Design System.

If changes affect:

- authentication
- authorization
- database schema
- public APIs
- multiple modules

Stop after the impact analysis and explain the risks before continuing.

Definition of Done:

✓ Architecture preserved

✓ No duplicated code

✓ Existing patterns reused

✓ Design System respected

✓ Graphify updated (`graphify update .` no app tocado — sem merge-graphs, cada app tem seu próprio `graphify-out/`)

✓ `docs/specs/audit.md` applied

✓ Medium/High followed `docs/specs/engineering-protocol.md`