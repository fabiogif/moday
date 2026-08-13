# Correção: Runtime ReferenceError - Cannot access 'productsDataAuth' before initialization

## 🐛 **Erro Identificado**
```
Runtime ReferenceError
Cannot access 'productsDataAuth' before initialization
src\app\(dashboard)\orders\new\page.tsx (188:29)
```

## 🔍 **Causa do Problema**
O erro ocorreu porque tentamos usar a variável `productsDataAuth` antes de declará-la com o hook `useAuthenticatedProducts()`.

### **❌ Ordem Incorreta (Antes)**
```typescript
// Linha 188 - Tentativa de uso
const finalProductsData = productsDataAuth || productsData; // ❌ ERRO

// Linha 200 - Declaração do hook (depois)
const { data: productsDataAuth, loading: productsLoadingAuth } = useAuthenticatedProducts();
```

## ✅ **Solução Implementada**

### **1. Reorganização dos Hooks**
```typescript
export default function NewOrderPage() {
  const router = useRouter();
  
  // ✅ TODOS OS HOOKS DECLARADOS NO INÍCIO
  const { data: clientsData, loading: clientsLoading } = useAuthenticatedClients();
  const { data: productsData, loading: productsLoading } = useProducts(); // Teste não autenticado
  const { data: productsDataAuth, loading: productsLoadingAuth } = useAuthenticatedProducts(); // Hook autenticado
  const { data: tablesData, loading: tablesLoading } = useAuthenticatedTables();
  const { mutate: createOrder, loading: creating } = useMutation();
  
  // ... resto do código
}
```

### **2. Logs de Debug Reorganizados**
```typescript
const isDelivery = form.watch("isDelivery");
const useClientAddress = form.watch("useClientAddress");
const selectedClientId = form.watch("clientId");
const watchProducts = form.watch("products");
const discountValue = form.watch("discountValue");
const discountType = form.watch("discountType");

// ✅ Debug dos hooks APÓS todas as declarações
console.log('=== COMPARAÇÃO DE HOOKS ===');
console.log('Hook NÃO autenticado:');
console.log('  - loading:', productsLoading);
console.log('  - data:', productsData);
console.log('Hook AUTENTICADO:');
console.log('  - loading:', productsLoadingAuth);
console.log('  - data:', productsDataAuth); // ✅ Agora pode ser acessada
console.log('===========================');
```

### **3. Uso Correto das Variáveis**
```typescript
// ✅ Agora funciona porque as variáveis já foram declaradas
const finalProductsData = productsDataAuth || productsData;
const finalProductsLoading = productsLoadingAuth && productsLoading;
const products = getArrayFromData(finalProductsData).filter((p: any) => p && p.id && p.name);
```

## 📋 **Regras de Hooks em React**

### **✅ Ordem Correta**
1. **Hooks sempre no topo** da função componente
2. **Declarações antes do uso** das variáveis
3. **Não usar hooks condicionalmente**
4. **Logs e processamento depois** das declarações

### **❌ Erros Comuns**
```typescript
// ❌ ERRADO - Hook após uso da variável
const result = myData || defaultData;
const { data: myData } = useMyHook();

// ❌ ERRADO - Hook condicional
if (condition) {
  const { data } = useMyHook(); // Não fazer isso
}

// ❌ ERRADO - Hook em função nested
function handleClick() {
  const { data } = useMyHook(); // Não fazer isso
}
```

### **✅ Padrão Correto**
```typescript
function MyComponent() {
  // ✅ 1. TODOS os hooks no início
  const { data: data1 } = useHook1();
  const { data: data2 } = useHook2();
  const { data: data3 } = useHook3();
  
  // ✅ 2. Estados e watches
  const watchValue = form.watch("field");
  
  // ✅ 3. Processamento e logs
  console.log('Data:', data1, data2, data3);
  const processedData = processData(data1, data2);
  
  // ✅ 4. Effects e handlers
  useEffect(() => {}, [data1]);
  
  // ✅ 5. Render
  return <div>...</div>;
}
```

## 🎯 **Resultado da Correção**

### **✅ Benefícios**
- ✅ **Erro eliminado** - não mais ReferenceError
- ✅ **Código organizado** - hooks no topo, lógica depois
- ✅ **Debug funcional** - logs mostrarão dados corretos
- ✅ **Fallback funcional** - usará dados de qualquer hook que funcionar

### **✅ Debug Agora Disponível**
```
=== COMPARAÇÃO DE HOOKS ===
Hook NÃO autenticado:
  - loading: false
  - data: [array de produtos ou null]
Hook AUTENTICADO:
  - loading: false  
  - data: [array de produtos ou null]
===========================
```

## 🚀 **Próximos Passos**

1. **✅ Erro corrigido** - aplicação não deve mais quebrar
2. **🔍 Teste o debug** - abrir console e verificar logs
3. **📊 Analisar dados** - ver qual hook está funcionando
4. **🛠️ Ajustar conforme necessário** - usar hook que funciona

---

**Status**: ✅ **ERRO CORRIGIDO**
**Aplicação**: ✅ **Funcionando sem ReferenceError**  
**Debug**: ✅ **Ativo e funcional**
**Próximo**: **Analisar logs para identificar causa dos produtos**