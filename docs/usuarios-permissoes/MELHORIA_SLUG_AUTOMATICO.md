# Melhoria: Geração Automática de Slug para Permissions e Roles

## Objetivo

Facilitar o cadastro de permissões e roles tornando o campo slug opcional, com geração automática baseada no nome fornecido pelo usuário.

## Problema Anterior

O campo slug era obrigatório tanto para Permissions quanto para Roles, exigindo que o usuário digitasse manualmente um identificador único para cada registro. Isso:
- Tornava o cadastro mais demorado
- Aumentava a possibilidade de erros de digitação
- Poderia gerar slugs inconsistentes
- Não seguia o padrão do Permission (que já tinha auto-geração)

## Solução Implementada

### Backend

#### 1. Role Model (`app/Models/Role.php`)

Adicionado evento `boot()` com geração automática de slug:

```php
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($model) {
        if (empty($model->slug)) {
            $model->slug = Str::slug($model->name);
        }
    });
}
```

**Como funciona:**
- Quando um Role é criado (`creating`), verifica se o slug está vazio
- Se estiver vazio, gera automaticamente usando `Str::slug()` do Laravel
- Se o slug for fornecido, mantém o valor informado

#### 2. RoleStoreRequest (`app/Http/Requests/RoleStoreRequest.php`)

Alterado o campo slug de `required` para `nullable`:

```php
'slug' => [
    'nullable',  // Antes era 'required'
    'string',
    'min:3',
    'max:255',
    'regex:/^[a-z0-9\-_]+$/',
    Rule::unique('roles', 'slug')->where('tenant_id', $tenantId)
],
```

Removida a mensagem de erro `slug.required`.

#### 3. RoleUpdateRequest (`app/Http/Requests/RoleUpdateRequest.php`)

Mesma alteração aplicada ao request de atualização.

#### 4. Permission Model

Já possuía a funcionalidade de geração automática de slug. Nenhuma alteração foi necessária.

### Frontend

#### 1. PermissionFormDialog

**Arquivo:** `src/app/(dashboard)/permissions/components/permission-form-dialog.tsx`

Alterações:
- Adicionado import de `FormDescription`
- Label alterado de "Slug" para "Slug (Opcional)"
- Adicionada mensagem informativa abaixo do campo:

```tsx
<FormField
  control={form.control}
  name="slug"
  render={({ field }) => (
    <FormItem>
      <FormLabel>Slug (Opcional)</FormLabel>
      <FormControl>
        <Input placeholder="create_users" {...field} />
      </FormControl>
      <FormDescription className="text-xs text-muted-foreground">
        Se não informado, o slug será gerado automaticamente a partir do nome.
      </FormDescription>
      <FormMessage />
    </FormItem>
  )}
/>
```

#### 2. DataTable (Permissions)

**Arquivo:** `src/app/(dashboard)/permissions/components/data-table.tsx`

Mesmas alterações aplicadas ao formulário de edição inline.

## Regras de Geração do Slug

O Laravel utiliza a função `Str::slug()` que:

✓ Converte todo o texto para minúsculas  
✓ Substitui espaços por hífens (-)  
✓ Remove caracteres especiais  
✓ Remove acentos e caracteres não-ASCII  
✓ Remove múltiplos hífens consecutivos  

### Exemplos de Conversão:

| Nome Original | Slug Gerado |
|--------------|-------------|
| Visualizar Produtos | visualizar-produtos |
| Criar Usuários | criar-usuarios |
| Admin Geral | admin-geral |
| Super Admin! | super-admin |
| Gerenciar_Perfis | gerenciar-perfis |
| Sistema@Admin | sistemadmin |

## Exemplos de Uso

### Exemplo 1: Criar Permissão SEM slug

**Entrada:**
```json
{
  "name": "Visualizar Produtos",
  "description": "Permite visualizar a lista de produtos"
}
```

**Resultado no Banco:**
```json
{
  "name": "Visualizar Produtos",
  "slug": "visualizar-produtos",
  "description": "Permite visualizar a lista de produtos"
}
```

### Exemplo 2: Criar Permissão COM slug customizado

**Entrada:**
```json
{
  "name": "Visualizar Produtos",
  "slug": "products.view",
  "description": "Permite visualizar a lista de produtos"
}
```

**Resultado no Banco:**
```json
{
  "name": "Visualizar Produtos",
  "slug": "products.view",
  "description": "Permite visualizar a lista de produtos"
}
```

### Exemplo 3: Criar Role SEM slug

**Entrada:**
```json
{
  "name": "Super Admin",
  "level": 5,
  "description": "Administrador do sistema"
}
```

**Resultado no Banco:**
```json
{
  "name": "Super Admin",
  "slug": "super-admin",
  "level": 5,
  "description": "Administrador do sistema"
}
```

## Arquivos Modificados

### Backend:
- `app/Models/Role.php` - Adicionado método `boot()` com auto-geração
- `app/Http/Requests/RoleStoreRequest.php` - Slug tornado opcional
- `app/Http/Requests/RoleUpdateRequest.php` - Slug tornado opcional

### Frontend:
- `src/app/(dashboard)/permissions/components/permission-form-dialog.tsx` - Mensagem informativa
- `src/app/(dashboard)/permissions/components/data-table.tsx` - Mensagem informativa

## Benefícios

✅ **Cadastro Mais Rápido** - Menos campos obrigatórios para preencher  
✅ **Menos Erros** - Reduz erros de digitação no slug  
✅ **Padronização** - Slugs gerados seguem sempre o mesmo padrão  
✅ **Flexibilidade** - Ainda permite customização quando necessário  
✅ **Melhor UX** - Interface mais amigável com dicas visuais  
✅ **Consistência** - Permissions e Roles funcionam da mesma forma  

## Como Testar

### Teste 1: Permissão sem slug

1. Acesse `http://localhost:3001/permissions`
2. Clique em "Cadastrar primeira permissão" ou botão de adicionar
3. Preencha:
   - Nome: "Teste Automático"
   - Slug: (deixe vazio)
   - Descrição: "Teste de geração automática"
4. Clique em "Criar Permissão"
5. **Resultado esperado:** Permissão criada com slug `teste-automatico`

### Teste 2: Permissão com slug customizado

1. Preencha:
   - Nome: "Teste Custom"
   - Slug: "custom.test"
   - Descrição: "Teste com slug customizado"
2. Clique em "Criar Permissão"
3. **Resultado esperado:** Permissão criada com slug `custom.test`

### Teste 3: Verificar mensagem informativa

1. Ao abrir o formulário de criar/editar permissão
2. Observe abaixo do campo Slug
3. **Resultado esperado:** Deve exibir a mensagem:
   > "Se não informado, o slug será gerado automaticamente a partir do nome."

## Observações

- A validação de unicidade do slug continua ativa
- Se houver conflito de slug gerado automaticamente, o backend retornará erro de validação
- Nesse caso, o usuário pode informar um slug customizado
- O slug pode conter letras minúsculas, números, hífens (-) e underscores (_)
- A geração automática funciona apenas na criação, não na atualização

## Compatibilidade

✅ Totalmente compatível com registros existentes  
✅ Não quebra funcionalidades anteriores  
✅ Registros antigos com slugs customizados permanecem inalterados  
✅ Funciona com multi-tenancy (validação por tenant)  
