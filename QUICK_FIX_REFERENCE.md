# 🚀 Quick Fix Reference - Dashboard Cards

## ⚡ TL;DR

**Problema**: Cards do dashboard não exibem dados  
**Causa**: Token não sincronizado entre AuthContext e apiClient  
**Solução**: Adicionar `apiClient.setToken()` em todos os pontos de autenticação

## 🔧 Arquivos Alterados (2)

### 1. `/frontend/src/lib/api-client.ts`
```typescript
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
//                                                                      ^^^^^ Adicionado
```

### 2. `/frontend/src/contexts/auth-context.tsx`
```typescript
// 1. Import
import { apiClient } from '@/lib/api-client'

// 2. Ao restaurar sessão (useEffect)
apiClient.setToken(savedToken)

// 3. Ao fazer login
const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
const response = await fetch(`${apiUrl}/api/auth/login`, ...)
const result = await response.json()
const data = result.data  // ← Nota: result.data, não data direto
apiClient.setToken(data.token)

// 4. Ao fazer logout
apiClient.clearToken()

// 5. Ao atualizar token
apiClient.setToken(tokenValue)
```

## ✅ Teste Rápido

```bash
# 1. Testar backend
./test-dashboard-auth.sh

# 2. Testar frontend
# - Abrir http://localhost:3000/login
# - Login: fabio@fabio.com / 123456
# - Ir para http://localhost:3000/dashboard
# - Verificar 4 cards com dados
```

## 📊 Dados Esperados

- Receita Total: R$ 12,00
- Clientes Ativos: 2
- Total de Pedidos: 2
- Taxa de Conversão: 8.3%
- Top Produto: Suco de Laranja 300ml

## 🔍 Debug

### Console deve mostrar:
```
AuthContext: Login bem-sucedido
AuthContext: Token recebido? true
ApiClient: Token definido: Sim
```

### Se não funcionar:
1. Limpar cache (DevTools > Application > Clear site data)
2. Verificar .env.local tem `NEXT_PUBLIC_API_URL=http://localhost:8000`
3. Verificar backend rodando em http://localhost:8000
4. Verificar frontend rodando em http://localhost:3000

## 📝 Checklist

- [x] apiClient.ts com porta :8000
- [x] auth-context.tsx importa apiClient
- [x] 4 pontos de sincronização adicionados
- [x] URL do login corrigida
- [x] Estrutura de resposta corrigida
- [ ] Testar no navegador

---
**Fix completo em**: `DASHBOARD_CARDS_COMPLETE_FIX.md`
