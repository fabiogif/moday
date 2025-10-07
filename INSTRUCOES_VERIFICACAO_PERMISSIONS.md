# Instruções para Verificação das Alterações

## Arquivos Alterados

### Frontend
```
frontend/src/app/(dashboard)/permissions/components/
├── data-table.tsx (+543/-240)
└── permission-form-dialog.tsx (+140/original)

frontend/src/app/(dashboard)/permissions/
└── page.tsx (+124/-original)
```

## Como Testar

### 1. Testar Criação de Permissão

**Passos:**
1. Acesse `/permissions` no frontend
2. Clique em "Nova Permissão"
3. Preencha os campos:
   - **Nome**: "Visualizar Pedidos"
   - **Módulo**: "orders"
   - **Ação**: "view"
   - **Recurso**: "order"
   - **Slug**: (deixe vazio para testar geração automática)
   - **Descrição**: "Permite visualizar a lista de pedidos"
4. Clique em "Criar Permissão"

**Resultado Esperado:**
- ✅ Permissão criada com sucesso
- ✅ Slug gerado automaticamente (ex: "orders.view")
- ✅ Toast de sucesso exibido
- ✅ Lista atualizada com a nova permissão

### 2. Testar Validação de Campos Obrigatórios

**Passos:**
1. Clique em "Nova Permissão"
2. Preencha apenas o campo **Nome**
3. Deixe **Módulo**, **Ação** e **Recurso** vazios
4. Tente salvar

**Resultado Esperado:**
- ❌ Validação impede o envio
- ❌ Mensagens de erro aparecem:
  - "Módulo é obrigatório"
  - "Ação é obrigatória"
  - "Recurso é obrigatório"

### 3. Testar Edição de Permissão

**Passos:**
1. Clique no menu ⋮ de uma permissão existente
2. Selecione "Editar"
3. Verifique que todos os campos estão preenchidos
4. Altere a descrição
5. Clique em "Atualizar Permissão"

**Resultado Esperado:**
- ✅ Formulário carrega com dados corretos
- ✅ Campos module, action, resource aparecem e estão preenchidos
- ✅ Permissão atualizada com sucesso
- ✅ Toast de sucesso exibido

### 4. Testar Vinculação de Permissões ao Perfil

**Passos:**
1. Acesse `/profiles` no frontend
2. Clique no menu ⋮ de um perfil
3. Selecione "Vincular Permissões"
4. Verifique que as permissões são carregadas
5. Selecione algumas permissões
6. Clique em "Salvar Permissões"

**Resultado Esperado:**
- ✅ Dialog abre corretamente
- ✅ Permissões são listadas e agrupadas por módulo
- ✅ Busca funciona (filtro por nome, slug, módulo)
- ✅ Seleção/deseleção funciona
- ✅ Vinculação é salva com sucesso
- ✅ Toast de sucesso exibido

### 5. Testar Slug Duplicado

**Passos:**
1. Crie uma permissão com slug "test.permission"
2. Tente criar outra permissão com o mesmo slug
3. Salve

**Resultado Esperado:**
- ❌ Backend retorna erro 422
- ❌ Mensagem: "Este slug já está em uso"
- ❌ Frontend exibe toast de erro

## Verificações Visuais

### Formulário de Permissão deve mostrar (em ordem):
```
1. Nome                [input text] *obrigatório
2. Módulo              [input text] *obrigatório
3. Ação                [input text] *obrigatório
4. Recurso             [input text] *obrigatório
5. Slug (Opcional)     [input text]
   ℹ️ Se não informado, o slug será gerado automaticamente
6. Descrição (Opcional) [textarea]
```

### Dialog de Vincular Permissões deve mostrar:
```
- Título: "Vincular Permissões ao Perfil"
- Subtítulo: "Selecione as permissões para o perfil [Nome do Perfil]"
- Campo de busca
- Botões: "Selecionar Todas" e "Limpar Seleção"
- Contador: "X de Y selecionadas"
- Lista agrupada por módulo
- Botões: "Cancelar" e "Salvar Permissões"
```

## Checklist de Funcionalidades

- [ ] Criar permissão com todos os campos
- [ ] Criar permissão sem slug (gera automaticamente)
- [ ] Editar permissão existente
- [ ] Excluir permissão
- [ ] Validação de campos obrigatórios funciona
- [ ] Validação de slug duplicado funciona
- [ ] Vincular permissões ao perfil
- [ ] Buscar permissões no dialog de vinculação
- [ ] Selecionar/deselecionar permissões
- [ ] Mensagens de erro são exibidas corretamente
- [ ] Mensagens de sucesso são exibidas corretamente

## Verificações Técnicas

### Requests que devem funcionar:

1. **Criar Permissão**
```bash
curl -X POST http://localhost/api/permissions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Visualizar Produtos",
    "module": "products",
    "action": "view",
    "resource": "product",
    "description": "Ver lista de produtos"
  }'
```

2. **Vincular Permissões**
```bash
curl -X PUT http://localhost/api/profiles/1/permissions/sync \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "permission_ids": [1, 2, 3]
  }'
```

## Problemas Conhecidos (Não Relacionados)

⚠️ Existe um erro de build no arquivo `src/app/api/categories/[id]/route.ts` que não está relacionado às nossas alterações. Esse erro pode ser ignorado para os testes das permissões.

## Limpeza de Console.log

Todos os `console.log` de debug foram removidos dos seguintes arquivos:
- ✅ assign-permissions-dialog.tsx
- ✅ permission-form-dialog.tsx
- ✅ data-table.tsx

## Documentação Gerada

1. `CORRECOES_FINAIS_PERMISSIONS.md` - Documentação completa do sistema
2. `RESUMO_ALTERACOES_PERMISSIONS.md` - Resumo das alterações
3. `INSTRUCOES_VERIFICACAO_PERMISSIONS.md` - Este arquivo

## Próximos Passos Sugeridos

1. ✅ Implementar página de Usuários
2. ✅ Adicionar vinculação de Perfil a Usuário
3. ✅ Implementar guards de permissão no frontend
4. ✅ Adicionar middleware de permissão nas rotas protegidas
5. ✅ Implementar auditoria de alterações

## Suporte

Se encontrar problemas, verifique:
1. Backend está rodando?
2. Migrations foram executadas?
3. Seeders foram executados?
4. Token de autenticação é válido?
5. Tenant_id está correto?

## Exemplo de Permissões Completas

```json
{
  "name": "Criar Usuários",
  "module": "users",
  "action": "create",
  "resource": "user",
  "slug": "users.create",
  "description": "Permite criar novos usuários no sistema",
  "is_active": true
}
```

```json
{
  "name": "Editar Produtos",
  "module": "products",
  "action": "edit",
  "resource": "product",
  "slug": "products.edit",
  "description": "Permite editar produtos existentes",
  "is_active": true
}
```

```json
{
  "name": "Excluir Clientes",
  "module": "clients",
  "action": "delete",
  "resource": "client",
  "slug": "clients.delete",
  "description": "Permite excluir clientes do sistema",
  "is_active": true
}
```
