# RestTec (albatecrest)

Monorepo GitLab do produto **RestTec**:

- `backend/` — API Laravel (deploy Docker em `:8002`)
- `frontend/` — app Next.js (deploy systemd em `:3002`)

## CI/CD

Runner tag `oracle-prod`:

| Job | Quando |
|-----|--------|
| `backend:test` / `backend:deploy` | mudanças em `backend/**` ou `.gitlab-ci.yml` |
| `frontend:test` / `frontend:deploy` | mudanças em `frontend/**` ou `.gitlab-ci.yml` |

Deploy automático apenas em `main`.

## Local

```bash
# Backend
cd backend && cp .env.production.example .env
composer install && php artisan serve

# Frontend
cd frontend && cp .env.production.example .env.local
npm ci && npm run dev
```

## Relação com Moday (GitHub)

O histórico deste repo diverge do GitHub Moday. Sincronização de backend continua **cirúrgica**. O frontend neste repositório é a fonte RestTec no GitLab.
