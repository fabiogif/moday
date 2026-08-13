# Correções de Erros de Carregamento da Landing Page

## Problemas Identificados

1. **Erro 404 em arquivos estáticos** (`_next/static/chunks/*.js`)
2. **MIME type incorreto** (retornando HTML ao invés de CSS/JS)
3. **Favicon não encontrado** (erro 500)

## Correções Aplicadas

### 1. Configuração do Next.js (`next.config.ts`)

**Problema**: `output: 'standalone'` estava sempre ativo, causando problemas no desenvolvimento.

**Solução**: Configurado para usar standalone apenas em produção:
```typescript
...(process.env.NODE_ENV === 'production' ? { output: 'standalone' } : {})
```

**Benefício**: 
- Desenvolvimento funciona normalmente sem problemas de servidor estático
- Produção mantém o modo standalone para deploy otimizado

### 2. Headers para Arquivos Estáticos

Adicionado cache headers específicos para arquivos estáticos:
```typescript
{
  source: '/_next/static/(.*)',
  headers: [
    {
      key: 'Cache-Control',
      value: 'public, max-age=31536000, immutable',
    },
  ],
}
```

### 3. Favicon

**Problema**: Favicon não existia, causando erro 500.

**Solução**: 
- Atualizado `layout.tsx` para usar múltiplos formatos de ícone
- Configurado fallback para SVG

## Como Resolver os Erros

### Passo 1: Limpar Cache
```bash
cd frontend
rm -rf .next
```

### Passo 2: Reinstalar Dependências (se necessário)
```bash
pnpm install
```

### Passo 3: Iniciar Servidor de Desenvolvimento
```bash
pnpm dev
```

### Passo 4: Verificar se está funcionando
- Acesse `http://localhost:3000`
- Verifique o console do navegador (F12)
- Não deve haver erros 404 ou MIME type

## Verificações

✅ Configuração do Next.js ajustada
✅ Headers de cache configurados
✅ Favicon configurado com fallback
✅ Modo standalone apenas em produção

## Próximos Passos

1. Reiniciar o servidor de desenvolvimento
2. Limpar cache do navegador (Ctrl+Shift+R ou Cmd+Shift+R)
3. Verificar se os erros desapareceram

