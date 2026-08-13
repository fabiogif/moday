# Checklist de Migração: Roles → Profiles

## ✅ Pré-Execução

- [ ] Fazer backup completo do banco de dados
- [ ] Confirmar que está em ambiente de desenvolvimento/teste
- [ ] Verificar que backend está rodando
- [ ] Verificar que banco de dados está acessível
- [ ] Ler documentação completa em `MIGRACAO_ROLES_TO_PROFILES.md`

## ✅ Execução

### Passo 1: Teste em Modo Dry-Run
```bash
cd backend
php artisan migrate:roles-to-profiles --dry-run
```

- [ ] Comando executado sem erros
- [ ] Verificar quantidade de Profiles que serão criados
- [ ] Verificar quantidade de permissões a migrar
- [ ] Verificar quantidade de usuários afetados
- [ ] Anotar números para comparação posterior

### Passo 2: Executar Migração Real
```bash
php artisan migrate:roles-to-profiles
```

- [ ] Comando executado com sucesso
- [ ] Verificar mensagens de sucesso
- [ ] Anotar relatório final exibido
- [ ] Verificar se não houve erros

### Passo 3: Verificação no Banco de Dados
```sql
-- Ver Profiles criados
SELECT COUNT(*) FROM profiles;

-- Ver usuários com Profiles
SELECT COUNT(*) FROM user_profiles;

-- Ver permissões em Profiles
SELECT COUNT(*) FROM permission_profile;
```

- [ ] Número de Profiles corresponde ao esperado
- [ ] Usuários foram associados corretamente
- [ ] Permissões foram migradas

## ✅ Testes

### Login e Autenticação
- [ ] Fazer login com usuário admin
- [ ] Fazer login com usuário normal
- [ ] Verificar que sessão é criada corretamente
- [ ] Logout funciona

### Permissões
- [ ] Admin consegue acessar todas as páginas
- [ ] Usuário normal tem acesso limitado
- [ ] Páginas protegidas bloqueiam acesso não autorizado
- [ ] Mensagem de "Acesso negado" aparece quando apropriado

### Página de Usuários
- [ ] Lista de usuários carrega
- [ ] Perfis são exibidos corretamente
- [ ] Vincular perfil funciona
- [ ] Desvincular perfil funciona (se implementado)
- [ ] Criar novo usuário funciona
- [ ] Editar usuário funciona
- [ ] Excluir usuário funciona

### API Endpoints
```bash
# Testar alguns endpoints
curl -H "Authorization: Bearer TOKEN" http://localhost:8000/api/users
curl -H "Authorization: Bearer TOKEN" http://localhost:8000/api/profiles
curl -H "Authorization: Bearer TOKEN" http://localhost:8000/api/permissions
```

- [ ] Endpoint `/api/users` funciona
- [ ] Endpoint `/api/profiles` funciona
- [ ] Endpoint `/api/permissions` funciona
- [ ] ACL middleware funciona corretamente

### Métodos do User Model
```bash
php artisan tinker

$user = User::where('email', 'teste@example.com')->first();
$user->hasProfile('admin');           // deve funcionar
$user->isAdmin();                      // deve funcionar
$user->getAllPermissions();            // deve retornar permissões
$user->hasPermissionTo('users.index'); // deve funcionar
```

- [ ] `hasProfile()` funciona
- [ ] `isAdmin()` funciona
- [ ] `isSuperAdmin()` funciona
- [ ] `getAllPermissions()` retorna correto
- [ ] `hasPermissionTo()` funciona
- [ ] Métodos deprecated ainda funcionam (fallback)

## ✅ Limpeza (Após Confirmação)

⚠️ **Execute apenas após confirmar que TUDO está funcionando!**

### Passo 1: Comentar Rotas de Roles

**Arquivo:** `routes/api.php`

- [ ] Comentar toda a seção `Route::prefix('role')->group(...)`
- [ ] Testar que aplicação ainda funciona
- [ ] Verificar que nenhuma página quebrou

### Passo 2: Atualizar Frontend (se houver páginas de Roles)

- [ ] Remover link para `/roles` do menu (se existir)
- [ ] Remover página de roles (se existir)
- [ ] Verificar que nenhum componente usa RoleAPI

### Passo 3: Logs e Monitoramento

- [ ] Verificar logs em `storage/logs/laravel.log`
- [ ] Não há erros relacionados a Roles
- [ ] Não há warnings sobre métodos deprecated

## ✅ Opcional: Remover Dados de Roles

⚠️ **Execute apenas se tiver CERTEZA ABSOLUTA!**

```sql
-- Backup antes de deletar
SELECT * FROM roles INTO OUTFILE '/tmp/roles_backup.csv';
SELECT * FROM role_user INTO OUTFILE '/tmp/role_user_backup.csv';
SELECT * FROM role_permissions INTO OUTFILE '/tmp/role_permissions_backup.csv';

-- Deletar dados
DELETE FROM role_user;
DELETE FROM role_permissions;
DELETE FROM roles;
```

- [ ] Backup criado
- [ ] Dados de Roles removidos
- [ ] Aplicação ainda funciona
- [ ] Testes passam

## ✅ Documentação

- [ ] Atualizar README se necessário
- [ ] Documentar mudança no changelog
- [ ] Informar equipe sobre a mudança
- [ ] Atualizar documentação da API (se houver)

## 🚨 Rollback (Se Necessário)

Se algo der errado:

```bash
# Restaurar backup do banco
mysql -u root -p database_name < backup.sql
```

- [ ] Backup restaurado
- [ ] Aplicação funcionando novamente
- [ ] Investigar causa do problema
- [ ] Documentar o que deu errado

## 📊 Métricas de Sucesso

Registre os números antes e depois:

### Antes da Migração
- Roles cadastrados: _______
- Usuários com Roles: _______
- Permissões em Roles: _______

### Depois da Migração
- Profiles criados: _______
- Usuários com Profiles: _______
- Permissões em Profiles: _______

### Verificação
- [ ] Números batem (mesma quantidade)
- [ ] Todos os usuários mantêm seus acessos
- [ ] Nenhuma permissão foi perdida

## 📝 Notas

Use este espaço para anotar observações durante a migração:

```
Data: __________
Executor: __________

Observações:
- 
- 
- 

Problemas encontrados:
- 
- 

Soluções aplicadas:
- 
- 
```

## ✅ Conclusão

- [ ] Migração concluída com sucesso
- [ ] Todos os testes passaram
- [ ] Equipe informada
- [ ] Documentação atualizada
- [ ] Sistema em produção estável
