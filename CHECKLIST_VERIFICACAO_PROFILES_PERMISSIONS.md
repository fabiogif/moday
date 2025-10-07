# ✅ Checklist de Verificação - Sistema de Profiles e Permissions

Use este checklist para verificar se tudo está funcionando corretamente após as correções.

## 🔧 Verificação Backend

### Banco de Dados
```bash
cd /Users/fabiosantana/Documentos/projetos/moday/backend
```

- [ ] **Verificar Total de Permissões**
  ```bash
  php artisan tinker --execute="echo 'Total: ' . App\Models\Permission::count() . ' permissões' . PHP_EOL;"
  ```
  **Esperado:** `Total: 81 permissões`

- [ ] **Verificar Total de Perfis**
  ```bash
  php artisan tinker --execute="echo 'Total: ' . App\Models\Profile::count() . ' perfis' . PHP_EOL;"
  ```
  **Esperado:** `Total: 8 perfis` (ou mais)

- [ ] **Verificar Perfil Super Admin**
  ```bash
  php artisan tinker --execute="
  \$profile = App\Models\Profile::find(1);
  echo 'Nome: ' . \$profile->name . PHP_EOL;
  echo 'Permissões: ' . \$profile->permissions->count() . PHP_EOL;
  "
  ```
  **Esperado:**
  ```
  Nome: Super Admin
  Permissões: 81
  ```

- [ ] **Verificar Usuário fabio@fabio.com**
  ```bash
  php artisan tinker --execute="
  \$user = App\Models\User::where('email', 'fabio@fabio.com')->first();
  echo 'Nome: ' . \$user->name . PHP_EOL;
  echo 'Perfis: ' . \$user->profiles->pluck('name')->implode(', ') . PHP_EOL;
  echo 'Total Permissões: ' . \$user->getAllPermissions()->count() . PHP_EOL;
  "
  ```
  **Esperado:**
  ```
  Nome: Fabio
  Perfis: Super Admin
  Total Permissões: 81
  ```

### Rotas API
- [ ] **Verificar Rota de Sync de Permissões**
  ```bash
  php artisan route:list | grep "profiles.*permissions.*sync"
  ```
  **Esperado:**
  ```
  PUT  api/profiles/{profile}/permissions/sync ... syncPermissionsForProfile
  ```

### Arquivos Modificados
- [ ] `app/Http/Controllers/Api/PermissionProfileApiController.php` - Métodos usando ID ao invés de Model Binding
- [ ] `database/migrations/2025_10_03_155218_remove_status_column_from_payment_methods_table.php` - Rollback corrigido
- [ ] `database/seeders/PermissionSeeder.php` - 81 permissões incluídas

---

## 🎨 Verificação Frontend

### Preparação
```bash
cd /Users/fabiosantana/Documentos/projetos/moday/frontend
npm run dev
```

### Testes na Interface

- [ ] **Login**
  - Acessar: `http://localhost:3000/login`
  - Email: `fabio@fabio.com`
  - Senha: `123456`
  - **Resultado:** Login bem-sucedido, redirecionado para dashboard

- [ ] **Página de Usuários**
  - Acessar: `http://localhost:3000/users`
  - **Resultado:** Lista de usuários carregada sem erro
  - **Não deve mostrar:** "Acesso negado. Permissão necessária: users.index"

- [ ] **Página de Perfis**
  - Acessar: `http://localhost:3000/profiles`
  - **Resultado:** Lista de perfis carregada
  - **Deve mostrar:** Pelo menos 4 perfis (Super Admin, Admin, Gerente, Usuário)

- [ ] **Modal Vincular Permissões**
  - Em `/profiles`, clicar em "Vincular Permissões" em qualquer perfil
  - **Resultado:** Modal abre mostrando permissões
  - **Deve mostrar:** Permissões agrupadas por módulo
  - **Total visível:** 81 permissões disponíveis

- [ ] **Console do Navegador (F12)**
  - Abrir console ao clicar em "Vincular Permissões"
  - **Deve aparecer:** Logs começando com `filterPermissions -`
  - **Exemplo esperado:**
    ```
    filterPermissions - extracted permissions array, length: 81
    filterPermissions - returning all permissions, count: 81
    ```
  - **Não deve aparecer:** `allPermissions is null/undefined`

- [ ] **Vincular Permissões**
  - Selecionar algumas permissões no modal
  - Clicar em "Salvar"
  - **Resultado:** Mensagem de sucesso "Permissões vinculadas ao perfil com sucesso!"
  - **Não deve aparecer:** Erro 404 "Perfil não encontrado"

- [ ] **Página de Permissões**
  - Acessar: `http://localhost:3000/permissions`
  - **Resultado:** Lista de 81 permissões carregada
  - **Deve mostrar:** Colunas Nome, Slug, Módulo, Ação, Status

---

## 🐛 Testes de Erro (Opcional)

### Testar Perfil Inexistente
- [ ] Tentar acessar: `http://localhost:3000/profiles/999999`
  - **Resultado:** Deve mostrar erro adequado, não crash

### Testar Sem Autenticação
- [ ] Fazer logout
- [ ] Tentar acessar: `http://localhost:3000/users`
  - **Resultado:** Redirecionar para `/login`

---

## 📊 Resultado Final Esperado

### ✅ Status OK
```
✅ Backend rodando sem erros
✅ 81 permissões cadastradas
✅ 8+ perfis cadastrados
✅ Perfil Super Admin com 81 permissões
✅ Usuário fabio@fabio.com com perfil Super Admin
✅ Frontend carregando sem erros
✅ Modal de permissões mostrando todas as 81 permissões
✅ Vinculação de permissões funcionando
✅ Todas as páginas acessíveis com Super Admin
```

### ❌ Se Algum Item Falhar

#### Backend com Erro
```bash
# Verificar logs
cd backend
tail -f storage/logs/laravel.log

# Ou com Docker
docker-compose logs -f backend
```

#### Permissões Faltando
```bash
cd backend
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=AssignAllPermissionsToProfileSeeder
```

#### Usuário Sem Permissão
```bash
cd backend
php artisan db:seed --class=UsersTableSeeder
php artisan db:seed --class=AssignAllPermissionsToProfileSeeder
```

#### Frontend Não Carrega Permissões
1. Abrir Console do Navegador (F12)
2. Verificar se há erros de rede (tab Network)
3. Verificar se backend está respondendo: `http://localhost/api/permissions`
4. Verificar logs no console começando com `filterPermissions -`

---

## 📝 Documentação de Referência

Se precisar de mais informações, consulte:

1. **INSTRUCOES_FINAIS_PROFILES_PERMISSIONS.md** - Instruções passo a passo
2. **CORRECAO_FINAL_PROFILES_PERMISSIONS.md** - Detalhes técnicos das correções
3. **RESUMO_CORRECOES_FINAIS_COMPLETO.md** - Resumo executivo completo

---

## 🎉 Tudo Funcionando?

Se todos os itens acima estiverem ✅ marcados, o sistema está funcionando corretamente!

Você pode agora:
- ✨ Criar novos perfis
- ✨ Vincular permissões a perfis
- ✨ Criar novos usuários
- ✨ Vincular perfis a usuários
- ✨ Acessar todas as funcionalidades do sistema

**Parabéns! 🚀**

---

**Última atualização:** 2025-01-XX  
**Versão:** 1.0  
**Status:** Concluído
