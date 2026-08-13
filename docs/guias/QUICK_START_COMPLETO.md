# 🚀 Quick Start - Sistema Moday

## 📋 Pré-requisitos

- Docker & Docker Compose instalados
- Node.js 18+ instalado
- Git instalado
- Portas disponíveis: 3000, 80, 8080, 3306, 6379

---

## ⚡ Início Rápido (5 minutos)

### 1. Iniciar Backend (Docker)

```bash
cd backend
docker-compose up -d
```

**Aguardar todos os containers iniciarem** (verificar com `docker ps`)

### 2. Limpar Cache e Configuração

```bash
docker exec backend-laravel.test-1 php artisan config:clear
docker exec backend-laravel.test-1 php artisan cache:clear
```

### 3. Iniciar Frontend

```bash
cd frontend
npm install  # Primeira vez apenas
npm run dev
```

### 4. Acessar o Sistema

Abra o navegador em: **http://localhost:3000**

**Credenciais:**
- Email: `fabio@fabio.com`
- Senha: `123456`

---

## ✅ Verificação Rápida

Execute o script de teste:

```bash
./test-sistema.sh
```

**Todos os itens devem mostrar ✓ (check verde)**

---

## 🔍 Verificar Status dos Containers

```bash
docker ps --format "table {{.Names}}\t{{.Status}}"
```

**Containers necessários:**
- ✅ backend-laravel.test-1
- ✅ backend-mysql-1
- ✅ backend-redis-1
- ✅ backend-reverb-1

---

## 🐛 Problemas Comuns

### Frontend não inicia

```bash
cd frontend
rm -rf .next node_modules
npm install
npm run dev
```

### Backend não responde

```bash
docker-compose down
docker-compose up -d
docker exec backend-laravel.test-1 php artisan config:clear
```

### Erro de permissão

```bash
cd backend
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Badge mostra "Offline"

1. Verificar Reverb:
```bash
docker logs backend-reverb-1
```

2. Reiniciar Reverb:
```bash
docker restart backend-reverb-1
```

---

## 📱 URLs de Acesso

| Serviço | URL | Descrição |
|---------|-----|-----------|
| Frontend | http://localhost:3000 | Interface do usuário |
| Backend | http://localhost | API REST |
| WebSocket | http://localhost:8080 | Reverb (tempo real) |
| Mailpit | http://localhost:8025 | Teste de emails |

---

## 🧪 Testar Endpoints

### Login
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"fabio@fabio.com","password":"123456"}'
```

### Dashboard (com token)
```bash
TOKEN="seu_token_aqui"
curl -X GET http://localhost/api/dashboard/metrics \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📊 Funcionalidades Principais

### 1. Dashboard
- 4 cards de métricas
- Gráfico de vendas vs metas
- Transações recentes
- Top produtos

### 2. Quadro de Pedidos
- Arrastar e soltar cards
- 4 colunas (Em Preparo, Pronto, Entregue, Cancelado)
- Atualização em tempo real
- Badge de status online/offline

### 3. Gerenciamento
- Clientes
- Produtos
- Categorias
- Mesas
- Usuários
- Permissões

---

## 🔄 Comandos Úteis

### Backend

```bash
# Limpar cache
docker exec backend-laravel.test-1 php artisan cache:clear

# Ver logs
docker exec backend-laravel.test-1 tail -f storage/logs/laravel.log

# Acessar container
docker exec -it backend-laravel.test-1 bash

# Rodar migrations
docker exec backend-laravel.test-1 php artisan migrate

# Rodar seeders
docker exec backend-laravel.test-1 php artisan db:seed
```

### Frontend

```bash
# Limpar build
rm -rf .next

# Rebuild
npm run build

# Modo produção
npm run start

# Verificar erros
npm run lint
```

### Docker

```bash
# Parar todos
docker-compose down

# Iniciar todos
docker-compose up -d

# Ver logs
docker-compose logs -f

# Reiniciar um serviço
docker restart backend-laravel.test-1

# Remover volumes (CUIDADO: apaga dados)
docker-compose down -v
```

---

## 🎯 Próximos Passos

1. **Personalizar Dashboard**
   - Editar: `frontend/src/app/(dashboard)/dashboard/components/`

2. **Adicionar Funcionalidades**
   - Backend: `backend/app/Http/Controllers/Api/`
   - Frontend: `frontend/src/app/(dashboard)/`

3. **Configurar Produção**
   - Trocar credenciais
   - Configurar domínio
   - Habilitar HTTPS
   - Configurar backup

---

## 📚 Documentação

- [Resumo Executivo](RESUMO_EXECUTIVO.md) - Visão geral do sistema
- [Correções Completas](CORRECOES_SISTEMA_COMPLETAS.md) - Detalhes técnicos
- [Script de Teste](test-sistema.sh) - Testes automatizados

---

## 🆘 Suporte

### Logs Importantes

```bash
# Laravel
docker exec backend-laravel.test-1 tail -f storage/logs/laravel.log

# Reverb
docker logs -f backend-reverb-1

# MySQL
docker logs -f backend-mysql-1

# Redis
docker logs -f backend-redis-1
```

### Verificar Conexões

```bash
# MySQL
docker exec backend-laravel.test-1 php artisan tinker --execute="DB::connection()->getPdo();"

# Redis
docker exec backend-laravel.test-1 php artisan tinker --execute="Redis::ping();"
```

---

## ⚙️ Configurações Importantes

### `.env` Backend

```env
# Database
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis

# Reverb
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

### `.env.local` Frontend

```env
NEXT_PUBLIC_API_URL=http://localhost
NEXT_PUBLIC_WS_URL=http://localhost:8080
```

---

## 🎨 Estrutura de Pastas

```
moday/
├── backend/              # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   ├── Requests/
│   │   │   └── Responses/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   └── Models/
│   ├── routes/
│   │   └── api.php
│   └── .env
│
├── frontend/             # Next.js
│   ├── src/
│   │   ├── app/
│   │   │   └── (dashboard)/
│   │   │       ├── dashboard/
│   │   │       └── orders/
│   │   ├── components/
│   │   ├── contexts/
│   │   └── hooks/
│   └── .env.local
│
└── docker-compose.yml
```

---

## ✨ Dicas

### Performance

1. **Cache Redis**: Configurado automaticamente
2. **Lazy Loading**: Componentes carregados sob demanda
3. **WebSocket**: Atualizações eficientes em tempo real

### Desenvolvimento

1. **Hot Reload**: Frontend atualiza automaticamente
2. **Debug**: Logs detalhados disponíveis
3. **Testes**: Script automatizado para verificação

### Segurança

1. **JWT Tokens**: Expiração de 24h
2. **CORS**: Configurado para desenvolvimento
3. **Middleware**: Autenticação em rotas protegidas

---

**🎉 Sistema Pronto para Uso!**

Para mais informações, consulte a [Documentação Completa](RESUMO_EXECUTIVO.md).
