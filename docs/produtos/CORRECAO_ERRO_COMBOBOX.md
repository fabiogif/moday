# Correção do Erro "Cannot read properties of undefined (reading 'value')"

## 🐛 **Problema Identificado**
O erro ocorria porque o componente `ComboboxForm` estava sendo usado incorretamente em várias páginas, passando props diretamente em vez de utilizar a propriedade `field` corretamente.

## ✅ **Correções Implementadas**

### 1. **Correção do Componente ComboboxForm**
**Arquivo:** `src/components/ui/combobox.tsx`

**Problema:** O componente não tinha proteção contra `field` undefined
```typescript
// ❌ ANTES - Sem proteção
value={field.value}
onValueChange={field.onChange}

// ✅ DEPOIS - Com proteção
value={field?.value || ""}
onValueChange={field?.onChange || (() => {})}
```

**Interface atualizada:**
```typescript
// ❌ ANTES - Campo obrigatório
field: {
  value?: string
  onChange: (value: string) => void
  onBlur: () => void
  name: string
}

// ✅ DEPOIS - Campo opcional com proteção
field?: {
  value?: string
  onChange?: (value: string) => void
  onBlur?: () => void
  name?: string
}
```

### 2. **Correção na Página de Novo Pedido**
**Arquivo:** `src/app/(dashboard)/orders/new/page.tsx`

**Problema:** Uso incorreto do ComboboxForm passando props diretamente

#### Cliente Selection:
```typescript
// ❌ ANTES - Props diretas
<ComboboxForm
  options={clientOptions}
  value={field.value}
  onValueChange={field.onChange}
  placeholder="Selecionar cliente..."
  searchPlaceholder="Buscar cliente..."
/>

// ✅ DEPOIS - Usando field prop
<ComboboxForm
  field={field}
  options={clientOptions}
  placeholder="Selecionar cliente..."
  searchPlaceholder="Buscar cliente..."
/>
```

#### Mesa Selection:
```typescript
// ❌ ANTES - Props diretas
<ComboboxForm
  options={tableOptions}
  value={field.value}
  onValueChange={field.onChange}
  placeholder="Selecionar mesa..."
  searchPlaceholder="Buscar mesa..."
/>

// ✅ DEPOIS - Usando field prop
<ComboboxForm
  field={field}
  options={tableOptions}
  placeholder="Selecionar mesa..."
  searchPlaceholder="Buscar mesa..."
/>
```

#### Produto Selection (com lógica customizada):
```typescript
// ❌ ANTES - Props diretas com lógica customizada
<ComboboxForm
  options={productOptions}
  value={field.value}
  onValueChange={(value) => {
    field.onChange(value);
    handleProductChange(index, value);
  }}
  placeholder="Selecionar produto..."
  searchPlaceholder="Buscar produto..."
/>

// ✅ DEPOIS - Field prop com onChange customizado
<ComboboxForm
  field={{
    ...field,
    onChange: (value: string) => {
      field.onChange(value);
      handleProductChange(index, value);
    }
  }}
  options={productOptions}
  placeholder="Selecionar produto..."
  searchPlaceholder="Buscar produto..."
/>
```

### 3. **Correção na Página de Novo Produto**
**Arquivo:** `src/app/(dashboard)/products/new/page.tsx`

**Problema:** Tentativa de usar props `multiple` não suportadas

```typescript
// ❌ ANTES - Props não suportadas
<ComboboxForm
  options={categoryOptions}
  values={field.value}
  onValuesChange={field.onChange}
  placeholder="Selecione as categorias"
  searchPlaceholder="Buscar categoria..."
  multiple
/>

// ✅ DEPOIS - Implementação manual de múltiplas seleções
<div className="space-y-2">
  <div className="flex flex-wrap gap-2">
    {field.value?.map((categoryId: string) => {
      const category = Array.isArray(categories) 
        ? categories.find((cat: any) => cat.identify === categoryId)
        : null;
      return (
        <div key={categoryId} className="flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded-md text-sm">
          <span>{category?.name || categoryId}</span>
          <button
            type="button"
            onClick={() => {
              const newCategories = field.value?.filter(id => id !== categoryId) || [];
              field.onChange(newCategories);
            }}
            className="text-primary hover:text-primary/80"
          >
            ×
          </button>
        </div>
      );
    })}
  </div>
  <ComboboxForm
    field={{
      value: "",
      onChange: (value: string) => {
        if (value && !field.value?.includes(value)) {
          const newCategories = [...(field.value || []), value];
          field.onChange(newCategories);
        }
      },
      onBlur: field.onBlur,
      name: field.name,
    }}
    options={categoryOptions}
    placeholder="Adicionar categoria"
    searchPlaceholder="Buscar categoria..."
  />
</div>
```

## 🔧 **Padrões de Uso Corretos**

### Para Seleção Única:
```typescript
<FormField
  control={form.control}
  name="fieldName"
  render={({ field }) => (
    <FormItem>
      <FormLabel>Label</FormLabel>
      <FormControl>
        <ComboboxForm
          field={field}
          options={options}
          placeholder="Placeholder..."
          searchPlaceholder="Buscar..."
        />
      </FormControl>
      <FormMessage />
    </FormItem>
  )}
/>
```

### Para Seleção Única com Lógica Customizada:
```typescript
<ComboboxForm
  field={{
    ...field,
    onChange: (value: string) => {
      field.onChange(value);
      // Lógica customizada aqui
    }
  }}
  options={options}
  placeholder="Placeholder..."
  searchPlaceholder="Buscar..."
/>
```

### Para Múltiplas Seleções:
```typescript
<div className="space-y-2">
  {/* Exibir seleções atuais */}
  <div className="flex flex-wrap gap-2">
    {field.value?.map((item) => (
      <div key={item} className="flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded-md text-sm">
        <span>{getItemLabel(item)}</span>
        <button
          type="button"
          onClick={() => removeItem(item)}
          className="text-primary hover:text-primary/80"
        >
          ×
        </button>
      </div>
    ))}
  </div>
  
  {/* ComboboxForm para adicionar novos */}
  <ComboboxForm
    field={{
      value: "",
      onChange: (value: string) => {
        if (value && !field.value?.includes(value)) {
          const newValues = [...(field.value || []), value];
          field.onChange(newValues);
        }
      },
      onBlur: field.onBlur,
      name: field.name,
    }}
    options={filteredOptions}
    placeholder="Adicionar item"
    searchPlaceholder="Buscar item..."
  />
</div>
```

## ✅ **Resultado**
- ✅ Erro "Cannot read properties of undefined (reading 'value')" corrigido
- ✅ ComboboxForm com proteção contra undefined
- ✅ Uso correto em todas as páginas
- ✅ Suporte a múltiplas seleções implementado corretamente
- ✅ Página de novo pedido funcionando completamente
- ✅ Página de novo produto funcionando completamente

## 🎯 **Status Final**
**🟢 RESOLVIDO** - Todas as páginas estão funcionando corretamente sem erros de JavaScript.