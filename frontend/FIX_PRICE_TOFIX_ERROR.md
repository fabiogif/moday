# 🔧 Fix: plan.price.toFixed is not a function

## 🐛 Erro Corrigido
```
plan.price.toFixed is not a function
src/app/landing/components/pricing-section.tsx (177:86)
```

## 🎯 Causa do Problema
O backend (Laravel) retorna o campo `price` como **string** (devido ao cast `decimal` no model), mas o frontend estava esperando um **número**.

**Exemplo do JSON retornado:**
```json
{
  "id": 1,
  "name": "Plano Básico",
  "price": "49.90",  // ← STRING, não NUMBER
  "description": "..."
}
```

Quando o código tentava fazer `plan.price.toFixed(2)`, falhava porque strings não têm o método `.toFixed()`.

---

## ✅ Solução Aplicada

### 1. Atualizar Interface TypeScript
```typescript
// ANTES
interface Plan {
  price: number  // Esperava apenas número
}

// DEPOIS
interface Plan {
  price: number | string  // Aceita ambos
}
```

### 2. Converter no useEffect
```typescript
const fetchPlans = async () => {
  const response = await ApiClient.get<Plan[]>('/api/public/plans')
  if (response && response.data) {
    // Garantir que price seja número
    const plansWithNumberPrice = response.data.map(plan => ({
      ...plan,
      price: typeof plan.price === 'string' 
        ? parseFloat(plan.price) 
        : plan.price
    }))
    setPlans(plansWithNumberPrice)
  }
}
```

### 3. Usar Number() na Renderização (Defesa Extra)
```typescript
// ANTES
R$ {plan.price.toFixed(2)}

// DEPOIS
R$ {Number(plan.price).toFixed(2)}
```

---

## 📊 Mudanças Aplicadas

**Arquivo:** `frontend/src/app/landing/components/pricing-section.tsx`

### Mudança 1: Interface
```diff
interface Plan {
  id: number
  name: string
  url: string
- price: number
+ price: number | string
  description: string | null
  details: PlanDetail[]
}
```

### Mudança 2: Conversão no Fetch
```diff
const response = await ApiClient.get<Plan[]>('/api/public/plans')
if (response && response.data) {
+ const plansWithNumberPrice = response.data.map(plan => ({
+   ...plan,
+   price: typeof plan.price === 'string' ? parseFloat(plan.price) : plan.price
+ }))
+ setPlans(plansWithNumberPrice)
- setPlans(response.data)
}
```

### Mudança 3: Renderização Segura
```diff
<div className="text-4xl font-bold mb-1">
- R$ {isYearly ? (plan.price * 12 * 0.8).toFixed(2) : plan.price.toFixed(2)}
+ R$ {isYearly 
+   ? (Number(plan.price) * 12 * 0.8).toFixed(2) 
+   : Number(plan.price).toFixed(2)}
</div>
```

---

## 🎯 Por Que Acontece?

### Backend (Laravel)
```php
// Model: Plan.php
protected function casts(): array
{
    return [
        'price' => 'decimal:2',  // ← Retorna como STRING
    ];
}
```

Laravel converte decimais para string para preservar precisão. Isso é padrão para campos financeiros.

### Frontend (TypeScript)
```typescript
// Esperava número, mas recebeu string
const price: number = "49.90"  // ❌ Erro de tipo
```

---

## ✅ Benefícios da Solução

### 1. **Defensivo**
- Funciona se backend enviar string OU número
- Não quebra em nenhum caso

### 2. **Type-Safe**
- TypeScript aceita ambos os tipos
- Sem erros de compilação

### 3. **Conversão Central**
- Converte uma vez no `useEffect`
- Resto do código usa valor já convertido

### 4. **Fallback Seguro**
- `Number()` na renderização como defesa extra
- Converte string para número em tempo real

---

## 🧪 Teste

### Cenário 1: Backend retorna string
```json
{ "price": "49.90" }
```
✅ Funciona: `parseFloat("49.90")` → `49.90`

### Cenário 2: Backend retorna número
```json
{ "price": 49.90 }
```
✅ Funciona: número já é número

### Cenário 3: Valor inválido
```json
{ "price": "abc" }
```
✅ Funciona: `Number("abc")` → `NaN` → `NaN.toFixed(2)` → "NaN"

---

## 🔍 Verificação

### Antes da Correção
```
❌ TypeError: plan.price.toFixed is not a function
❌ Página quebra
❌ Preços não aparecem
```

### Depois da Correção
```
✅ Preços aparecem corretamente
✅ Formatação funciona (R$ 49.90)
✅ Toggle anual/mensal funciona
✅ Sem erros no console
```

---

## 💡 Alternativas Consideradas

### Alternativa 1: Forçar backend a retornar número
```php
// Não recomendado - perde precisão em decimais
return response()->json([
    'price' => (float) $plan->price
]);
```
❌ Perde precisão
❌ Problema em valores financeiros

### Alternativa 2: Converter apenas na renderização
```typescript
{Number(plan.price).toFixed(2)}
```
⚠️ Funciona, mas repete conversão
⚠️ Menos eficiente

### Alternativa 3: Solução atual (escolhida)
```typescript
// Converter uma vez no useEffect
const plansWithNumberPrice = response.data.map(...)
```
✅ Eficiente
✅ Type-safe
✅ Defensivo

---

## 📋 Checklist

- [x] Interface atualizada para aceitar string | number
- [x] Conversão implementada no useEffect
- [x] Number() usado na renderização (defesa extra)
- [x] Código testado localmente
- [x] Sem erros de TypeScript
- [x] Fallback mantido para planos estáticos

---

## 🚀 Deploy

```bash
cd frontend
git add src/app/landing/components/pricing-section.tsx
git commit -m "fix: convert plan price string to number for toFixed()"
git push origin main
```

---

## 🎉 Status

**Problema:** ❌ `plan.price.toFixed is not a function`  
**Solução:** ✅ Converter string para número  
**Resultado:** ✅ Landing page funcionando corretamente  

---

**Data:** 2025-10-14  
**Arquivo:** `pricing-section.tsx`  
**Linhas alteradas:** 3 mudanças  
**Status:** ✅ Resolvido
