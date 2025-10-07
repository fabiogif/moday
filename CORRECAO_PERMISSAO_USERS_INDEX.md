# Correção do Erro: Acesso Negado - users.index

## Problema
O usuário está recebendo o erro: **"Acesso negado. Permissão necessária: users.index"**

Isso significa que o usuário autenticado não possui a permissão `users.index` necessária para listar usuários.

## Causa
O sistema utiliza ACL (Access Control List) com middleware `acl.permission:users.index` na rota de listagem de usuários. O usuário precisa ter esta permissão atribuída diretamente ou através de um perfil/role.

## Solução 1: Usando o Comando Artisan (Recomendado)

Criei um comando Artisan para atribuir todas as permissões ao usuário:

```bash
cd backend
php artisan user:assign-all-permissions teste@example.com
```

Este comando irá:
1. Buscar o usuário pelo email
2. Buscar todas as permissões do tenant do usuário
3. Remover permissões antigas
4. Atribuir todas as permissões disponíveis ao usuário
5. Exibir um resumo das permissões atribuídas

## Solução 2: Usando SQL Direto

Se preferir executar SQL diretamente no banco de dados, use o arquivo criado:

```bash
# O arquivo SQL está em:
backend/fix_user_permissions.sql

# Execute no MySQL:
mysql -u seu_usuario -p seu_banco < backend/fix_user_permissions.sql
```

## Solução 3: Usando o Seeder

Execute o seeder que já existe para atribuir permissões ao usuário de teste:

```bash
cd backend
php artisan db:seed --class=SimpleTestUserSeeder
```

## Verificação

Após executar qualquer uma das soluções acima, verifique se as permissões foram atribuídas:

```bash
cd backend
php artisan tinker
```

Então execute no tinker:

```php
$user = User::where('email', 'teste@example.com')->first();
$user->permissions()->count(); // Deve retornar um número > 0
$user->permissions()->where('slug', 'users.index')->exists(); // Deve retornar true
```

## Permissões do Módulo Users

O sistema possui as seguintes permissões para o módulo de usuários:

- `users.index` - Listar usuários
- `users.create` - Criar usuários
- `users.show` - Visualizar detalhes do usuário
- `users.update` - Editar usuários
- `users.delete` - Excluir usuários
- `users.manage` - Gerenciar todos os aspectos de usuários

## Estrutura de Permissões

```
┌──────────────┐
│    User      │
├──────────────┤
│ Permissões   │◄─── Permissões diretas (tabela user_permissions)
│ Diretas      │
└──────────────┘

┌──────────────┐
│    User      │
├──────────────┤
│    Roles     │◄─── Permissões via Roles (tabela role_user)
└──────────────┘
        │
        ▼
┌──────────────┐
│    Role      │
├──────────────┤
│ Permissões   │◄─── Permissões do role (tabela role_permissions)
└──────────────┘
```

O usuário pode ter permissões de duas formas:
1. **Diretas**: Atribuídas diretamente ao usuário
2. **Via Role**: Herdadas de roles atribuídos ao usuário

## Como Iniciar o Banco de Dados (se necessário)

Se o banco de dados não estiver rodando, execute:

```bash
cd backend
docker-compose up -d
```

Ou use um dos scripts disponíveis:
```bash
./setup-docker.sh
```

## Testando a Correção

Após atribuir as permissões:

1. Faça login novamente no sistema
2. Acesse a página de usuários: `http://localhost:3001/users`
3. A lista de usuários deve carregar corretamente

## Logs para Debug

Se o problema persistir, verifique os logs:

```bash
tail -f backend/storage/logs/laravel.log
```

## Arquivo Criado

- `backend/app/Console/Commands/AssignAllPermissionsToUser.php` - Comando para atribuir permissões
- `backend/fix_user_permissions.sql` - Script SQL alternativo
