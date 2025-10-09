# Moday - AI Coding Agent Instructions

## Architecture Overview

**Moday** is a full-stack business management system with separated frontend (Next.js) and backend (Laravel) services:

- **Backend** (`/backend/`): Laravel 11 API with JWT auth, Docker Sail environment, and repository pattern
- **Frontend** (`/frontend/`): Next.js 15 ShadCN dashboard with shadcn/ui components and TypeScript
- **Base Frontend** (`/base_frontend/`): ShadCN template foundation (reference only)

## Development Environment

### Docker Setup (Backend)
```bash
cd backend
./setup-docker.sh    # First-time setup
./start-docker.bat   # Windows start (or start-docker.ps1)
sail up -d           # Alternative Docker startup
```

### Frontend Development
```bash
cd frontend
npm run dev          # Development server on localhost:3000
npm run test         # Jest tests
```

## Backend Patterns (Laravel)

### Repository Pattern
- **Base**: All repositories extend `BaseRepository` with standard CRUD operations
- **Location**: `app/Repositories/` with corresponding `contracts/` interfaces
- **Usage**: Controllers inject repositories, not models directly

### API Structure
- **Routes**: `routes/api.php` with comprehensive Swagger documentation
- **Controllers**: Namespace `App\Http\Controllers\Api\*ApiController`
- **Resources**: API responses use Laravel Resources for data transformation
- **Auth**: JWT-based authentication via `tymon/jwt-auth`

### Key Architectural Components
- **Services**: Business logic in `app/Services/` (e.g., `AuthService`)
- **DTOs**: Data Transfer Objects in `app/DTO/`
- **Helpers**: Custom functions in `app/Helpers/functions.php` (auto-loaded)
- **Observers**: Model events in `app/Observers/`

## Frontend Patterns (Next.js)

### Authentication Flow
- **Middleware**: `src/middleware.ts` handles route protection
- **Protected Routes**: `/dashboard-2`, `/users`, `/orders`, `/categories`, etc.
- **Public Routes**: `/login`, `/sign-up-3`, `/forgot-password-3`
- **Token Storage**: Cookies with `auth-token` key

### Component Architecture
- **UI Components**: ShadCN components in `src/components/ui/`
- **Layout**: App Router structure in `src/app/` with route groups `(auth)`, `(dashboard)`
- **State Management**: Custom hooks with React state + Zustand for global state
- **API Integration**: Custom `useApi` hook in `src/hooks/use-api.ts` with caching

### Key Patterns
- **Data Tables**: Use `@tanstack/react-table` with ShadCN DataTable patterns
- **Forms**: React Hook Form + Zod validation + ShadCN form components  
- **API Calls**: Centralized in `src/lib/api-client.ts` with consistent error handling
- **Auto-Refresh**: Global refresh hooks (e.g., `useOrderRefresh`) for real-time updates

## Development Workflows

### Backend Testing
```bash
cd backend
sail test                    # PHPUnit tests
sail php artisan test       # Alternative test command
```

### API Documentation
- **Swagger**: Auto-generated at `/api/documentation` endpoint
- **Collections**: Postman collection in `backend/Api laravel Padrão.postman_collection.json`

### Common Issues & Solutions
- **CORS**: Configure in `bootstrap/app.php` for Laravel 11, not kernel
- **Client NULL errors**: Use optional chaining (`?.`) and multiple fallbacks for nested object access
- **Grid refresh**: Implement global state triggers for automatic UI updates after mutations
- **Docker MySQL**: Use `fix-wsl-docker.sh` for WSL compatibility issues

## File Naming Conventions

### Backend
- **Controllers**: `*ApiController.php` (e.g., `OrderApiController`)
- **Repositories**: `*Repository.php` with matching interface in `contracts/`
- **Models**: PascalCase with UUIDs as primary keys
- **Migrations**: Timestamp prefixed with descriptive names

### Frontend  
- **Components**: kebab-case files, PascalCase exports
- **Pages**: `page.tsx` in route directories
- **Hooks**: `use-*.ts` pattern
- **Types**: Centralized in `src/types/` with domain-specific files

## Integration Points

### API Communication
- **Base URL**: Environment-specific (localhost:80 for Docker)
- **Error Handling**: Consistent JSON responses with message/data structure
- **Validation**: Laravel Form Requests with detailed error responses

### Database
- **Primary Keys**: UUIDs for all entities, not auto-increment IDs
- **Relationships**: Standard Eloquent relationships with proper foreign key constraints
- **Seeders**: Available for development data in `database/seeders/`

## Project-Specific Conventions

- **Multi-tenant**: Tenant-aware models and queries (check `TenantRepository`)
- **Permissions**: ACL system with role-based permissions (see `PermissionRepository`)
- **Caching**: Redis integration for performance optimization
- **Queue System**: Background job processing for heavy operations
- **File Storage**: Configured for local development with Docker volume mounts