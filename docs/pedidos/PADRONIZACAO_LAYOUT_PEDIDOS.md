# Padronização de Layout - Páginas de Pedidos

## Objetivo

Ajustar o layout das páginas de pedidos para seguir o mesmo padrão da página "Novo Produto":
- Cabeçalho com botão voltar + título + descrição
- Cards organizados em grid
- Botões de ação no rodapé

## Padrão de Referência (Novo Produto)

```jsx
<div className="flex flex-col gap-6 p-6">
  {/* Cabeçalho */}
  <div className="flex items-center gap-4">
    <Button variant="ghost" size="icon" onClick={() => router.back()}>
      <ArrowLeft className="h-4 w-4" />
    </Button>
    <div>
      <h1 className="text-3xl font-bold">Título da Página</h1>
      <p className="text-muted-foreground">Descrição da página</p>
    </div>
  </div>

  {/* Formulário */}
  <Form {...form}>
    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
      {/* Grid de Cards */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Seção</CardTitle>
            <CardDescription>Descrição da seção</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Campos do formulário */}
          </CardContent>
        </Card>
      </div>

      {/* Botões de Ação */}
      <div className="flex justify-end gap-4">
        <Button type="button" variant="outline" onClick={() => router.back()}>
          Cancelar
        </Button>
        <Button type="submit" disabled={loading}>
          {loading ? "Salvando..." : "Salvar"}
        </Button>
      </div>
    </form>
  </Form>
</div>
```

## Páginas a Serem Ajustadas

### 1. Novo Pedido
**Arquivo:** `frontend/src/app/(dashboard)/orders/new/page.tsx`

**Estrutura Atual:** Precisa de ajustes
**Estrutura Nova:** Aplicar padrão acima

### 2. Editar Pedido  
**Arquivo:** `frontend/src/app/(dashboard)/orders/edit/[id]/page.tsx`

**Estrutura Atual:** Precisa de ajustes
**Estrutura Nova:** Aplicar padrão acima

## Mudanças Específicas

### Cabeçalho

**Antes:**
```jsx
<div className="...">
  <h2>Título</h2>
  <Button>Voltar</Button>
</div>
```

**Depois:**
```jsx
<div className="flex items-center gap-4">
  <Button variant="ghost" size="icon" onClick={() => router.back()}>
    <ArrowLeft className="h-4 w-4" />
  </Button>
  <div>
    <h1 className="text-3xl font-bold">Novo Pedido</h1>
    <p className="text-muted-foreground">Crie um novo pedido para um cliente</p>
  </div>
</div>
```

### Cards

**Organizar em seções lógicas:**

1. **Informações do Cliente**
   - Título: "Cliente"
   - Descrição: "Selecione o cliente para este pedido"
   - Campos: Cliente, Switch Delivery/Mesa

2. **Produtos**
   - Título: "Produtos"
   - Descrição: "Adicione produtos ao pedido"
   - Campos: Lista de produtos

3. **Resumo e Pagamento**
   - Título: "Resumo"
   - Descrição: "Desconto, total e forma de pagamento"
   - Campos: Desconto, Total, Forma de Pagamento

4. **Entrega** (se delivery)
   - Título: "Endereço de Entrega"
   - Descrição: "Informações de entrega"
   - Campos: Endereço completo

### Botões

**Posição:** Final da página, alinhados à direita

```jsx
<div className="flex justify-end gap-4">
  <Button type="button" variant="outline" onClick={() => router.back()}>
    Cancelar
  </Button>
  <Button type="submit" disabled={creating}>
    {creating ? "Criando..." : "Criar Pedido"}
  </Button>
</div>
```

## Imports Necessários

Adicionar se não existir:
```typescript
import { ArrowLeft } from "lucide-react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
```

## Benefícios

✅ **Consistência Visual** - Mesma aparência em todas as páginas
✅ **UX Melhorada** - Botão voltar padronizado
✅ **Organização** - Cards claramente separados
✅ **Responsivo** - Grid adapta para mobile
✅ **Profissional** - Layout moderno e limpo

## Implementação

Devido ao tamanho dos arquivos, a implementação será feita em etapas:

1. ✅ Documentação criada
2. 🔄 Ajustar `orders/new/page.tsx`
3. 🔄 Ajustar `orders/edit/[id]/page.tsx`
4. 🔄 Testar responsividade
5. 🔄 Validar com usuário

## Exemplo Visual

```
┌────────────────────────────────────────────────────────┐
│ ← │ Novo Pedido                                        │
│   │ Crie um novo pedido para um cliente                │
├────────────────────────────────────────────────────────┤
│                                                         │
│ ┌──────────────────┐  ┌──────────────────┐             │
│ │ Cliente          │  │ Produtos         │             │
│ │ Selecione o...   │  │ Adicione prod... │             │
│ │                  │  │                  │             │
│ │ [campos]         │  │ [lista]          │             │
│ └──────────────────┘  └──────────────────┘             │
│                                                         │
│ ┌──────────────────┐  ┌──────────────────┐             │
│ │ Resumo           │  │ Entrega          │             │
│ │ Desconto...      │  │ Endereço...      │             │
│ │                  │  │                  │             │
│ │ [campos]         │  │ [campos]         │             │
│ └──────────────────┘  └──────────────────┘             │
│                                                         │
│                          [Cancelar] [Criar Pedido]     │
└────────────────────────────────────────────────────────┘
```

## Status

📝 **DOCUMENTAÇÃO COMPLETA**
🔄 **AGUARDANDO IMPLEMENTAÇÃO**

Este documento serve como guia para a implementação. Os arquivos são grandes e complexos, então a implementação será feita com cuidado para não quebrar funcionalidades existentes.

