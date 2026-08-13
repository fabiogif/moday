# Melhoria: Validação e Exibição de Erros

## Problema Anterior

Ao tentar adicionar uma permissão com slug duplicado, a mensagem de erro era exibida de forma confusa:

```
"ApiClient: Erro HTTP 422: Este slug já está em uso. (e mais 1 erro)"
```

Isso dificultava a compreensão do usuário sobre quais campos exatamente estavam com erro.

## Causa

Os erros de validação do Laravel (HTTP 422) retornam múltiplos campos em formato de objeto, mas o frontend não estava tratando adequadamente esses erros para extrair e exibir as mensagens individualmente.

### Formato de Resposta do Backend (422):

```json
{
  "success": false,
  "message": "Erro de validação",
  "errors": {
    "slug": ["Este slug já está em uso."],
    "name": ["O nome já foi cadastrado."]
  }
}
```

## Solução Implementada

### 1. Melhorias no Hook `useMutation`

**Arquivo:** `frontend/src/hooks/use-authenticated-api.ts`

Melhorias implementadas:

- **Extração Inteligente de Erros**: Detecta erros do Laravel em `err.errors` ou `err.data`
- **Formatação de Mensagens**: Extrai todas as mensagens de validação e as separa por quebra de linha
- **Propagação de Erros**: Lança exceção com mensagem formatada para captura na camada da página

**Código:**

```typescript
try {
  // ... requisição
} catch (err: any) {
  let errorMessage = 'Erro na requisição'
  
  // Tratar erros de validação (HTTP 422)
  if (err.errors || err.data) {
    const validationErrors = err.errors || err.data || {}
    const errorMessages: string[] = []
    
    Object.entries(validationErrors).forEach(([field, messages]) => {
      if (Array.isArray(messages)) {
        messages.forEach(msg => errorMessages.push(msg))
      } else if (typeof messages === 'string') {
        errorMessages.push(messages)
      }
    })
    
    if (errorMessages.length > 0) {
      errorMessage = errorMessages.join('\n')
    }
  }
  
  throw new Error(errorMessage)
}
```

### 2. Melhorias na Página de Permissões

**Arquivo:** `frontend/src/app/(dashboard)/permissions/page.tsx`

Melhorias implementadas:

- **Detecção de Múltiplas Mensagens**: Identifica quando há múltiplas mensagens separadas por `\n`
- **Exibição Individual**: Cada mensagem é exibida em um toast separado
- **Mensagens Limpas**: Remove texto técnico e exibe apenas as mensagens de validação

**Código:**

```typescript
const handleAddPermission = async (permissionData: PermissionFormValues) => {
  try {
    const result = await createPermission(...)
    
    if (result) {
      toast.success('Permissão criada com sucesso!')
      await refetch()
    }
  } catch (error: any) {
    const errorMessage = error.message || 'Erro ao criar permissão'
    
    // Se houver múltiplas mensagens (separadas por \n), exibir uma por vez
    if (errorMessage.includes('\n')) {
      const messages = errorMessage.split('\n')
      messages.forEach(msg => {
        if (msg.trim()) {
          toast.error(msg.trim())
        }
      })
    } else {
      toast.error(errorMessage)
    }
  }
}
```

## Resultado

### Antes:
```
🔴 Toast: "ApiClient: Erro HTTP 422: Este slug já está em uso. (e mais 1 erro)"
```

### Depois:
```
🔴 Toast 1: "Este slug já está em uso."
🔴 Toast 2: "O nome já foi cadastrado."
```

## Formatos de Erros Suportados

A solução suporta diferentes formatos de erro do Laravel:

1. **Array de mensagens por campo:**
   ```json
   { "field": ["Erro 1", "Erro 2"] }
   ```

2. **String simples por campo:**
   ```json
   { "field": "Mensagem de erro" }
   ```

3. **Erro único:**
   ```json
   { "message": "Mensagem de erro" }
   ```

4. **Erro com múltiplos campos:**
   ```json
   {
     "errors": {
       "field1": ["Erro 1"],
       "field2": ["Erro 2"]
     }
   }
   ```

## Arquivos Modificados

1. `frontend/src/hooks/use-authenticated-api.ts`
   - Hook `useMutation` melhorado
   - Extração e formatação de erros de validação
   - Lançamento de exceções com mensagens formatadas

2. `frontend/src/app/(dashboard)/permissions/page.tsx`
   - `handleAddPermission`: Tratamento de múltiplas mensagens
   - `handleEditPermission`: Tratamento de múltiplas mensagens
   - `handleDeletePermission`: Tratamento de erro aprimorado

## Benefícios

✅ Mensagens de erro mais claras e amigáveis  
✅ Cada erro de validação exibido separadamente  
✅ Melhor experiência do usuário (UX)  
✅ Fácil identificação de problemas de validação  
✅ Consistência em toda a aplicação  
✅ Suporte para múltiplos idiomas (se necessário no futuro)  

## Testando a Melhoria

1. Acesse `http://localhost:3001/permissions`
2. Clique em "Cadastrar primeira permissão" ou no botão de adicionar
3. Tente criar uma permissão com dados inválidos:
   - Nome: (deixe vazio ou use um já existente)
   - Slug: `users.index` (já existe)
   - Descrição: (opcional)
4. Clique em "Criar Permissão"
5. Observe as mensagens de erro:
   - Cada erro em um toast separado
   - Mensagens claras e diretas
   - Sem texto técnico confuso
6. Corrija os erros e tente novamente
7. Sucesso deve exibir: "Permissão criada com sucesso!"

## Aplicação em Outros Módulos

Esta melhoria pode ser facilmente aplicada em outros módulos:

- **Usuários**: Criação/edição de usuários
- **Perfis**: Criação/edição de perfis
- **Produtos**: Criação/edição de produtos
- **Clientes**: Criação/edição de clientes
- **Pedidos**: Criação/edição de pedidos

Basta seguir o mesmo padrão de tratamento de erros implementado na página de permissões.
