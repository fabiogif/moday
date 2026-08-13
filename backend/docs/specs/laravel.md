# Laravel Standards

Controllers

- Thin Controllers
- No business logic

Business Rules

- Services only

Persistence

- Repository only

Validation

- FormRequest only

Responses

- API Resources

Always use Dependency Injection.

Never instantiate Services manually.

Always reuse existing Services.

Avoid duplicated business logic.

Prefer eager loading.

Avoid N+1 queries.

Always preserve the existing architecture.