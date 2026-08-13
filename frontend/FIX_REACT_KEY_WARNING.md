# 🔧 Fix: React Key Prop Warning

## ⚠️ Warning Corrigido
```
Warning: Each child in a list should have a unique "key" prop.
Check the render method of `PricingSection`.
```

## 🎯 Causa do Problema
A lista de detalhes dos planos (`plan.details.map`) estava usando apenas `detail.id` como key, mas esse ID pode não ser único entre diferentes planos.

**Exemplo do problema:**
```javascript
// Plano 1
{ id: 1, details: [{ id: 1, name: "Feature A" }] }

// Plano 2  
{ id: 2, details: [{ id: 1, name: "Feature B" }] }  // ← Mesmo ID!
```

React precisa de keys **globalmente únicos** dentro do contexto da lista, não apenas dentro de cada plano.

---

## ✅ Solução Aplicada

### Antes (Problema)
```typescript
{plan.details.map((detail) => (
  <li key={detail.id}>  // ← Pode não ser único
    <Check />
    <span>{detail.name}</span>
  </li>
))}
```

### Depois (Corrigido)
```typescript
{plan.details.map((detail, detailIndex) => (
  <li key={`${plan.id}-${detail.id}-${detailIndex}`}>  // ← Único
    <Check />
    <span>{detail.name}</span>
  </li>
))}
```

---

## 📊 Estrutura da Key

A nova key combina 3 valores:
```
key = `${plan.id}-${detail.id}-${detailIndex}`
```

### Exemplo:
- Plano 1, Detail 1, Index 0 → `"1-1-0"`
- Plano 1, Detail 2, Index 1 → `"1-2-1"`
- Plano 2, Detail 1, Index 0 → `"2-1-0"` ✅ Diferente!

---

## ✅ Por Que Esta Solução?

### 1. **Garante Unicidade**
- Combinação de plan.id + detail.id + index é sempre única
- Funciona mesmo se detail.id se repetir entre planos

### 2. **Estável**
- Key não muda a menos que os dados mudem
- React pode otimizar renderização

### 3. **Semântica**
- Representa a relação real: plano → detalhe → posição

### 4. **Backward Compatible**
- Funciona se detail.id for único
- Funciona se detail.id NÃO for único

---

## 🔍 Alternativas Consideradas

### Alternativa 1: Apenas index
```typescript
key={detailIndex}
```
❌ Ruim: Index pode mudar se lista for reordenada
❌ React pode perder estado dos componentes

### Alternativa 2: Apenas detail.id
```typescript
key={detail.id}
```
❌ Original: Não garante unicidade entre planos

### Alternativa 3: plan.id + detail.id
```typescript
key={`${plan.id}-${detail.id}`}
```
⚠️  Quase bom, mas e se houver duplicatas dentro do mesmo plano?

### Alternativa 4: Solução atual (escolhida)
```typescript
key={`${plan.id}-${detail.id}-${detailIndex}`}
```
✅ Garante unicidade absoluta
✅ Inclui index como fallback
✅ Semântico e explícito

---

## 📋 Mudança Aplicada

**Arquivo:** `frontend/src/app/landing/components/pricing-section.tsx`

```diff
<ul role="list" className="space-y-3 text-sm">
- {plan.details.map((detail) => (
-   <li key={detail.id} className="flex items-center gap-3">
+ {plan.details.map((detail, detailIndex) => (
+   <li key={`${plan.id}-${detail.id}-${detailIndex}`} className="flex items-center gap-3">
      <Check className="text-muted-foreground size-4 flex-shrink-0" strokeWidth={2.5} />
      <span>{detail.name}</span>
    </li>
  ))}
</ul>
```

---

## ✅ Resultado

### Antes
```
⚠️  Warning no console
⚠️  Possíveis problemas de renderização
⚠️  React pode confundir elementos
```

### Depois
```
✅ Sem warnings
✅ Keys únicas e estáveis
✅ Renderização otimizada
✅ Comportamento previsível
```

---

## 🧪 Verificação

### Console do Browser
```javascript
// Antes
Warning: Each child in a list should have a unique "key" prop.

// Depois
(sem warnings) ✅
```

### React DevTools
```
Antes: Possíveis duplicatas destacadas
Depois: Todas as keys únicas ✅
```

---

## 📚 Best Practices

### ✅ Boas Práticas de Keys

1. **Use IDs únicos quando disponível**
   ```typescript
   key={item.id}  // Se ID for globalmente único
   ```

2. **Combine múltiplos valores se necessário**
   ```typescript
   key={`${parent.id}-${child.id}`}
   ```

3. **Use index como último recurso**
   ```typescript
   key={index}  // Apenas se lista for estática
   ```

4. **Evite valores computados**
   ```typescript
   key={Math.random()}  // ❌ NUNCA faça isso!
   ```

### ❌ Anti-patterns

```typescript
// ❌ Ruim - Muda a cada render
key={Math.random()}

// ❌ Ruim - Não é único
key={item.type}

// ❌ Ruim - Index em lista dinâmica
key={index}

// ✅ Bom - ID único e estável
key={item.id}

// ✅ Bom - Combinação única
key={`${parentId}-${childId}`}
```

---

## 💡 Quando Usar Cada Abordagem

### Cenário 1: IDs Únicos Disponíveis
```typescript
// Backend garante IDs únicos
{items.map(item => (
  <div key={item.id}>...</div>
))}
```

### Cenário 2: IDs Podem Duplicar
```typescript
// IDs únicos por categoria
{categories.map(cat => (
  cat.items.map(item => (
    <div key={`${cat.id}-${item.id}`}>...</div>
  ))
))}
```

### Cenário 3: Lista Estática
```typescript
// Lista nunca muda ordem
const STATIC_ITEMS = ['A', 'B', 'C']
{STATIC_ITEMS.map((item, i) => (
  <div key={i}>...</div>
))}
```

---

## 🚀 Deploy

```bash
cd frontend
git add src/app/landing/components/pricing-section.tsx
git commit -m "fix: ensure unique keys for plan details list"
git push origin main
```

---

## 🎉 Status

**Warning:** ⚠️  Each child needs unique key  
**Solução:** ✅ Composite key (plan+detail+index)  
**Resultado:** ✅ Sem warnings, renderização otimizada  

---

**Data:** 2025-10-14  
**Arquivo:** `pricing-section.tsx`  
**Linha:** 209  
**Status:** ✅ Resolvido
