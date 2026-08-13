# Quick Start - Sistema Moday

## Configuração Rápida do Ambiente

### 1. Pré-requisitos

- Docker & Docker Compose
- PHP 8.3+
- Node.js 18+
- pnpm (ou npm/yarn)
- Redis (via Docker)

### 2. Iniciar Services Docker

```bash
cd backend
docker-compose up -d mysql redis
```

Verificar se os serviços estão rodando:
```bash
docker ps | grep -E "mysql|redis"
```

### 3. Configurar Backend

```bash
cd backend

# Instalar dependências
composer install

# Verificar/Atualizar .env
cat .env | grep -E "REDIS_HOST|DB_HOST"
# Deve mostrar:
# DB_HOST=127.0.0.1
# REDIS_HOST=127.0.0.1

# Se necessário, atualizar:
sed -i '' 's/REDIS_HOST=redis/REDIS_HOST=127.0.0.1/g' .env
sed -i '' 's/DB_HOST=mysql/DB_HOST=127.0.0.1/g' .env

# Limpar e reconstruir cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache

# Rodar migrações (se necessário)
php artisan migrate

# Iniciar servidor
php artisan serve --port=8000
```

### 4. Configurar Frontend

```bash
cd frontend

# Instalar dependências
pnpm install

# Verificar .env.local
cat .env.local
# Deve conter:
# NEXT_PUBLIC_API_URL=http://localhost:8000

# Limpar cache Next.js (se houver problemas)
rm -rf .next

# Iniciar servidor de desenvolvimento
pnpm dev
```

### 5. Testar o Sistema

#### Testar Backend
```bash
# Testar Redis
php artisan tinker --execute="use Illuminate\Support\Facades\Redis; Redis::set('test', 'ok'); echo Redis::get('test');"

# Testar login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"fabio@fabio.com","password":"123456"}' | jq
```

#### Testar Frontend
1. Abrir navegador em http://localhost:3000
2. Fazer login com:
   - Email: `fabio@fabio.com`
   - Senha: `123456`
3. Verificar dashboard com métricas

### 6. Verificar Saúde do Sistema

```bash
# Backend
curl http://localhost:8000/api/dashboard/metrics \
  -H "Authorization: Bearer {seu-token}" | jq

# Frontend (dev server)
curl http://localhost:3000 -I

# Redis
redis-cli ping
# Resposta: PONG

# MySQL
docker exec -it backend-mysql-1 mysql -usail -ppassword -e "SELECT 1;"
```

## Estrutura de Portas

| Serviço          | Porta | URL                    |
|------------------|-------|------------------------|
| Frontend (Next)  | 3000  | http://localhost:3000  |
| Backend (Laravel)| 8000  | http://localhost:8000  |
| MySQL            | 3306  | localhost:3306         |
| Redis            | 6379  | localhost:6379         |
| Reverb (WebSocket)| 8080 | ws://localhost:8080    |
| Mailpit (SMTP)   | 1025  | localhost:1025         |
| Mailpit (Web)    | 8025  | http://localhost:8025  |

## Comandos Úteis

### Backend
```bash
# Ver rotas disponíveis
php artisan route:list

# Limpar todos os caches
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Recriar banco de dados
php artisan migrate:fresh --seed

# Criar usuário admin
php artisan tinker --execute="
    \$user = App\Models\User::create([
        'name' => 'Admin',
        'email' => 'admin@admin.com',
        'password' => bcrypt('admin123'),
        'tenant_id' => 1,
        'is_active' => true
    ]);
    echo 'Usuário criado: ' . \$user->email;
"
```

### Frontend
```bash
# Build para produção
pnpm build

# Rodar em produção
pnpm start

# Verificar erros de lint
pnpm lint

# Atualizar dependências
pnpm update
```

### Docker
```bash
# Ver logs
docker-compose logs -f redis
docker-compose logs -f mysql

# Reiniciar serviço
docker-compose restart redis

# Parar tudo
docker-compose down

# Limpar volumes (CUIDADO: apaga dados)
docker-compose down -v
```

## Troubleshooting Rápido

### Erro: Redis connection failed
```bash
# Verificar se Redis está rodando
docker ps | grep redis

# Verificar configuração
cat backend/.env | grep REDIS_HOST
# Deve ser: 127.0.0.1 (não "redis")

# Limpar cache
php artisan config:clear && php artisan cache:clear
```

### Erro: Login failed
```bash
# Verificar se backend está rodando
curl http://localhost:8000/api/auth/login -I

# Verificar .env do frontend
cat frontend/.env.local | grep NEXT_PUBLIC_API_URL
# Deve ser: http://localhost:8000
```

### Erro: Module not found (Next.js)
```bash
cd frontend
rm -rf .next
rm -rf node_modules
pnpm install
pnpm dev
```

### Erro: MySQL connection refused
```bash
# Verificar se MySQL está rodando
docker ps | grep mysql

# Verificar senha
docker exec -it backend-mysql-1 mysql -usail -ppassword

# Verificar .env
cat backend/.env | grep DB_
```

## Endpoints Principais

### Autenticação
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Dados do usuário
- `POST /api/auth/refresh` - Refresh token

### Dashboard
- `GET /api/dashboard/metrics` - Métricas gerais
- `GET /api/dashboard/sales-performance` - Vendas
- `GET /api/dashboard/recent-transactions` - Transações
- `GET /api/dashboard/top-products` - Top produtos

### Recursos
- `GET /api/product` - Lista produtos
- `GET /api/order` - Lista pedidos
- `GET /api/client` - Lista clientes
- `GET /api/table` - Lista mesas
- `GET /api/users` - Lista usuários
- `GET /api/profiles` - Lista perfis

## Credenciais Padrão

```
# Usuário Admin
Email: fabio@fabio.com
Senha: 123456

# Tenant
Nome: Empresa Dev
CNPJ: 07768662000155
```

## Próximos Passos

1. ✅ Backend e Frontend rodando
2. ✅ Redis e MySQL conectados
3. ✅ Login funcionando
4. ✅ Dashboard com métricas
5. 🔄 Configurar Reverb (WebSocket)
6. 🔄 Configurar upload de imagens
7. 🔄 Implementar testes automatizados

## Links Úteis

- Backend API: http://localhost:8000
- Frontend: http://localhost:3000
- Mailpit: http://localhost:8025
- Redis Commander: Instalar com `docker run -d -p 8081:8081 rediscommander/redis-commander`

## Suporte

Para problemas ou dúvidas:
1. Verificar logs: `docker-compose logs -f`
2. Verificar documentação detalhada em `RESUMO_CORRECOES_COMPLETO.md`
3. Verificar correções anteriores em `REDIS_CONNECTION_FIX.md`
