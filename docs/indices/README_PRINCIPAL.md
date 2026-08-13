# 🎯 Sistema Moday - README Principal

## ✅ Status Atual: SISTEMA TOTALMENTE OPERACIONAL

**Última Atualização:** 06 de Outubro de 2025

---

## 🚀 Início Rápido (3 comandos)

```bash
# 1. Iniciar backend
cd backend && docker-compose up -d

# 2. Iniciar frontend
cd frontend && npm run dev

# 3. Testar sistema
./scripts/test/test-sistema.sh
```

**Acessar:** http://localhost:3000  
**Login:** `fabio@fabio.com` / `123456`

---

## ✅ O Que Foi Corrigido Hoje

### 1. Conexões Docker ✅
- **Redis**: `REDIS_HOST` alterado de `127.0.0.1` → `redis`
- **MySQL**: `DB_HOST` alterado de `127.0.0.1` → `mysql`
- **Status**: Todos os containers funcionando

### 2. Autenticação ✅
- Login com JWT funcionando
- Token válido por 24 horas
- Endpoints protegidos

### 3. Dashboard ✅
- 4 Cards de métricas com dados reais
- Gráfico de vendas vs metas
- Transações recentes
- Principais produtos

### 4. Quadro de Pedidos ✅
- Drag & Drop funcional
- WebSocket para tempo real
- Badge de status (online/offline)
- 4 colunas (Em Preparo, Pronto, Entregue, Cancelado)

### 5. Cache Redis ✅
- Configurado e funcionando
- Melhora performance das consultas
- Invalidação automática

---

## 📚 Documentação Essencial

### 🎯 Para Começar
1. **[Quick Start Completo](QUICK_START_COMPLETO.md)** - Guia de 5 minutos
2. **[Resumo Executivo](RESUMO_EXECUTIVO.md)** - Visão geral
3. **[Índice Mestre](INDICE_MESTRE_DOCUMENTACAO.md)** - Todos os docs

### 🔧 Para Desenvolver
- [Correções Completas](CORRECOES_SISTEMA_COMPLETAS.md) - Detalhes técnicos
- [Orders Board Guide](ORDERS_BOARD_GUIDE.md) - Quadro de pedidos
- [Dashboard WebSocket](DASHBOARD_WEBSOCKET_IMPLEMENTACAO.md) - Real-time

### 🐛 Para Resolver Problemas
- `./scripts/test/test-sistema.sh` - Script de diagnóstico
- [Troubleshooting](QUICK_START_COMPLETO.md#-problemas-comuns)

---

## 🧪 Testar Sistema

```bash
./scripts/test/test-sistema.sh
```

**Resultado Esperado:**
```
✓ MySQL Connection
✓ Redis Connection
✓ Login Successful
✓ Dashboard Metrics
✓ Sales Performance
✓ Recent Transactions
✓ Top Products
✓ Reverb Running
✓ Frontend Running
```

---

## 📊 Funcionalidades Principais

### Dashboard
- ✅ Receita Total (com gráfico de 6 meses)
- ✅ Clientes Ativos (contagem e tendência)
- ✅ Total de Pedidos (estatísticas)
- ✅ Taxa de Conversão (performance)
- ✅ Gráfico Vendas vs Metas (3m/6m/12m)
- ✅ Transações Recentes (últimas 10)
- ✅ Principais Produtos (top 5)

### Quadro de Pedidos
- ✅ Drag & Drop entre colunas
- ✅ Atualização em tempo real (WebSocket)
- ✅ Badge de status online/offline
- ✅ 4 colunas de status
- ✅ Detalhes do pedido no card

### Outros
- ✅ Gerenciamento de Clientes
- ✅ Gerenciamento de Produtos
- ✅ Gerenciamento de Categorias
- ✅ Gerenciamento de Mesas
- ✅ Perfis e Permissões
- ✅ Loja Pública (checkout)

---

## 🔗 URLs Importantes

| Serviço | URL | Descrição |
|---------|-----|-----------|
| Frontend | http://localhost:3000 | Interface principal |
| Backend | http://localhost | API REST |
| WebSocket | http://localhost:8080 | Reverb (tempo real) |
| Mailpit | http://localhost:8025 | Testes de email |

---

## 🐛 Resolução Rápida de Problemas

### Badge mostra "Offline"
```bash
docker restart backend-reverb-1
docker logs -f backend-reverb-1
```

### Dados não aparecem
```bash
docker exec backend-laravel.test-1 php artisan cache:clear
docker exec backend-laravel.test-1 php artisan config:clear
```

### Sistema não inicia
```bash
docker-compose down
docker-compose up -d
./scripts/test/test-sistema.sh
```

---

## 📂 Estrutura do Projeto

```
moday/
├── backend/              # Laravel API
│   ├── app/
│   ├── routes/api.php
│   └── .env (ATUALIZADO)
│
├── frontend/             # Next.js
│   ├── src/app/(dashboard)/
│   │   ├── dashboard/   # Dashboard principal
│   │   └── orders/      # Quadro de pedidos
│   └── .env.local
│
├── docker-compose.yml
├── scripts/test/scripts/test/test-sistema.sh  # Script de teste ⭐
└── docs/                # Documentação
    ├── QUICK_START_COMPLETO.md ⭐
    ├── RESUMO_EXECUTIVO.md ⭐
    └── INDICE_MESTRE_DOCUMENTACAO.md ⭐
```

---

## 🔧 Configurações Importantes

### Backend (.env)
```env
DB_HOST=mysql          # ✅ Corrigido
REDIS_HOST=redis       # ✅ Corrigido
CACHE_DRIVER=redis
REVERB_HOST=localhost
REVERB_PORT=8080
```

### Após Alterar .env
```bash
docker exec backend-laravel.test-1 php artisan config:clear
docker exec backend-laravel.test-1 php artisan cache:clear
```

---

## 📊 Endpoints da API

### Dashboard
```bash
GET /api/dashboard/metrics              # Métricas
GET /api/dashboard/sales-performance    # Vendas
GET /api/dashboard/recent-transactions  # Transações
GET /api/dashboard/top-products         # Produtos
```

### Pedidos
```bash
GET  /api/order           # Listar
POST /api/order           # Criar
PUT  /api/order/{id}      # Atualizar
GET  /api/order/stats     # Estatísticas
```

---

## 🎯 Checklist de Verificação

### Backend
- [x] Containers rodando
- [x] MySQL conectado
- [x] Redis funcionando
- [x] Reverb ativo
- [x] API respondendo

### Frontend
- [x] Servidor rodando
- [x] Login funcional
- [x] Dashboard com dados
- [x] Quadro de pedidos
- [x] WebSocket conectado

### Funcionalidades
- [x] Autenticação JWT
- [x] Cache Redis
- [x] Tempo real (WebSocket)
- [x] Drag & Drop
- [x] Métricas do dashboard

---

## 📝 Comandos Úteis

### Ver Logs
```bash
# Laravel
docker exec backend-laravel.test-1 tail -f storage/logs/laravel.log

# Reverb
docker logs -f backend-reverb-1
```

### Limpar Cache
```bash
docker exec backend-laravel.test-1 php artisan cache:clear
docker exec backend-laravel.test-1 php artisan config:clear
```

### Verificar Status
```bash
docker ps
./scripts/test/test-sistema.sh
```

---

## 🎉 Resumo Final

✅ **Sistema 100% Operacional**

- Todas as conexões configuradas
- Autenticação funcionando
- Dashboard com dados reais
- Quadro de pedidos drag & drop
- WebSocket para tempo real
- Cache Redis otimizado

**Para mais detalhes:**
- [Quick Start Completo](QUICK_START_COMPLETO.md)
- [Resumo Executivo](RESUMO_EXECUTIVO.md)
- [Índice Mestre](INDICE_MESTRE_DOCUMENTACAO.md)

---

**🚀 Sistema Pronto para Uso!**

*Data: 06/10/2025*  
*Status: OPERACIONAL*
