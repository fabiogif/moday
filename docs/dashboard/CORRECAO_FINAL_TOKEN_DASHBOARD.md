# Correção Final - Sincronização de Token e Dashboard

## Problema Identificado

Os cards de estatísticas do dashboard não estavam exibindo dados, mesmo com os endpoints do backend funcionando corretamente.

## Causa Raiz

1. **Falta de Sincronização do Token**: O `AuthContext` armazenava o token no `localStorage`, mas não sincronizava com o `apiClient`.
2. **URL Incorreta no Login**: O login estava usando URL relativa `/api/auth/login` que ia para o Next.js ao invés do Laravel backend.
3. **Estrutura de Resposta**: O código estava lendo `data.token` ao invés de `result.data.token`.
4. **Fallback da API URL**: O apiClient tinha fallback para `http://localhost` sem porta.

## Correções Aplicadas

### 1. api-client.ts
- Alterado fallback de `http://localhost` para `http://localhost:8000`

### 2. auth-context.tsx
- Importado `apiClient`
- Sincronização do token em todos os pontos:
  - Ao restaurar sessão: `apiClient.setToken(savedToken)`
  - Ao fazer login: `apiClient.setToken(data.token)`
  - Ao fazer logout: `apiClient.clearToken()`
  - Ao atualizar token: `apiClient.setToken(tokenValue)`
- Corrigida URL do login para usar `${apiUrl}/api/auth/login`
- Corrigida leitura da resposta: `result.data.token` ao invés de `data.token`

## Arquivos Modificados

1. `/frontend/src/lib/api-client.ts`
2. `/frontend/src/contexts/auth-context.tsx`

## Como Testar

1. Limpar cache do navegador
2. Fazer login com: `fabio@fabio.com` / `123456`
3. Acessar dashboard em `http://localhost:3000/dashboard`
4. Verificar que os 4 cards aparecem com dados

## Status

✅ Correções aplicadas
🔄 Aguardando restart do Next.js dev server para testar
