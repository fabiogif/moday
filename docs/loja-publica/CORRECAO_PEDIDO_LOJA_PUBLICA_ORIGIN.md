# Correção: Erro ao Criar Pedido na Loja Pública + Campo Origin

## Problemas Resolvidos

### 1. Erro ao Criar Cliente na Loja Pública
**Erro original:**
```
SQLSTATE[HY000]: General error: 1364 Field 'password' doesn't have a default value
```

**Causa:** 
- Na loja pública, clientes são criados sem senha (não precisam fazer login)
- A tabela `clients` exigia o campo `password` (NOT NULL)
- O campo `cpf` também era obrigatório e único

**Solução:**
- Tornado campos `password` e `cpf` nullable na tabela `clients`
- Removida constraint UNIQUE do campo `cpf` (múltiplos clientes podem não ter CPF)

### 2. Campo Origin Adicionado aos Pedidos
Adicionado campo `origin` para identificar a origem do pedido:
- `admin` - Pedido criado pelo painel administrativo
- `public_store` - Pedido criado pela loja pública

## Migrations Criadas

### 1. Make Password Nullable
**Arquivo:** `2025_10_06_200457_make_password_nullable_in_clients_table.php`

```php
public function up(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->string('password')->nullable()->change();
    });
}
```

### 2. Add Origin to Orders
**Arquivo:** `2025_10_06_200521_add_origin_to_orders_table.php`

```php
public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->enum('origin', ['admin', 'public_store'])
              ->default('admin')
              ->after('status');
    });
}
```

### 3. Make CPF Nullable
**Arquivo:** `2025_10_06_201030_make_cpf_nullable_in_clients_table.php`

```php
public function up(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->string('cpf')->nullable()->change();
    });
}
```

### 4. Remove Unique Constraint from CPF
**Arquivo:** `2025_10_06_201114_remove_unique_constraint_from_cpf_in_clients_table.php`

```php
public function up(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->dropUnique(['cpf']);
    });
}
```

## Alterações nos Models

### Model Order
**Arquivo:** `app/Models/Order.php`

Adicionado campo `origin` ao fillable:
```php
protected $fillable = [
    'tenant_id', 
    'identify', 
    'client_id', 
    'table_id', 
    'total', 
    'status',
    'origin',  // ← NOVO
    'comment',
    // ... outros campos
];
```

## Alterações nos Controllers

### PublicStoreController
**Arquivo:** `app/Http/Controllers/Api/PublicStoreController.php`

No método `createOrder`, adicionado origin ao criar pedido:
```php
$order = Order::create([
    'identify' => $this->generateOrderIdentify(),
    'tenant_id' => $tenant->id,
    'client_id' => $client->id,
    'total' => $total,
    'status' => 'Em Preparo',
    'origin' => 'public_store',  // ← NOVO
    // ... outros campos
]);
```

### OrderRepository
**Arquivo:** `app/Repositories/OrderRepository.php`

No método `createNewOrder`, adicionado origin com valor padrão 'admin':
```php
$order = [
    'identify' => $identify,
    'total' => $total,
    'status' => $status,
    'origin' => 'admin',  // ← NOVO - Default origin is admin panel
    'tenant_id' => $tenantId,
    'comment' => $comment,
];
```

## Estrutura da Tabela Clients (Atualizada)

```sql
CREATE TABLE clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    cpf VARCHAR(255) NULLABLE,              -- ✅ Agora nullable
    phone VARCHAR(255) NULLABLE,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NULLABLE,         -- ✅ Agora nullable
    uuid VARCHAR(36) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    tenant_id BIGINT UNSIGNED NOT NULL,
    -- Campos de endereço
    address VARCHAR(255) NULLABLE,
    city VARCHAR(255) NULLABLE,
    state VARCHAR(255) NULLABLE,
    zip_code VARCHAR(255) NULLABLE,
    neighborhood VARCHAR(255) NULLABLE,
    number VARCHAR(255) NULLABLE,
    complement VARCHAR(255) NULLABLE,
    -- Timestamps
    email_verified_at TIMESTAMP NULLABLE,
    remember_token VARCHAR(100) NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

## Estrutura da Tabela Orders (Atualizada)

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    identify VARCHAR(255) NOT NULL,
    client_id BIGINT UNSIGNED,
    table_id BIGINT UNSIGNED,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(255) NOT NULL,
    origin ENUM('admin', 'public_store') DEFAULT 'admin',  -- ✅ NOVO
    comment TEXT NULLABLE,
    -- Campos de delivery
    is_delivery BOOLEAN DEFAULT FALSE,
    use_client_address BOOLEAN DEFAULT FALSE,
    delivery_address VARCHAR(255) NULLABLE,
    delivery_city VARCHAR(255) NULLABLE,
    delivery_state VARCHAR(255) NULLABLE,
    delivery_zip_code VARCHAR(255) NULLABLE,
    delivery_neighborhood VARCHAR(255) NULLABLE,
    delivery_number VARCHAR(255) NULLABLE,
    delivery_complement VARCHAR(255) NULLABLE,
    delivery_notes TEXT NULLABLE,
    -- Pagamento e envio
    payment_method VARCHAR(255) NULLABLE,
    shipping_method VARCHAR(255) NULLABLE,
    -- Timestamps
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL
);
```

## Casos de Uso

### Loja Pública (http://localhost:3000/store/{slug})
```php
// Cliente criado SEM senha
Client::updateOrCreate(
    ['email' => 'cliente@email.com', 'tenant_id' => $tenant->id],
    [
        'uuid' => Str::uuid(),
        'name' => 'Nome do Cliente',
        'phone' => '11999999999',
        'cpf' => null,  // Pode ser null
        'is_active' => true,
        // password não é definido (será null)
    ]
);

// Pedido criado com origin = 'public_store'
Order::create([
    'origin' => 'public_store',
    // ... outros campos
]);
```

### Painel Administrativo
```php
// Pedido criado com origin = 'admin' (padrão)
$order = Order::create([
    'origin' => 'admin',  // Ou não especificar (usa default)
    // ... outros campos
]);
```

## Benefícios

### 1. **Flexibilidade para Clientes**
- ✅ Clientes podem fazer pedidos sem cadastro completo
- ✅ CPF e senha são opcionais
- ✅ Facilita compras rápidas na loja pública

### 2. **Rastreabilidade de Pedidos**
- ✅ Identificação clara da origem do pedido
- ✅ Possibilidade de análises separadas por canal
- ✅ Relatórios de vendas por origem

### 3. **Integridade de Dados**
- ✅ Evita erros de campos obrigatórios não preenchidos
- ✅ Permite múltiplos clientes sem CPF
- ✅ Mantém compatibilidade com fluxos existentes

## Testes Realizados

### ✅ Teste 1: Criação de Cliente sem Senha
```bash
Cliente criado/atualizado: Fabio Santana (ID: 25)
```

### ✅ Teste 2: Verificação do Campo Origin
```bash
Último pedido:
  ID: 2iqpg6j8
  Origin: admin
  Status: Pronto
```

### ✅ Teste 3: Migrations Executadas
```bash
✓ 2025_10_06_200457_make_password_nullable_in_clients_table
✓ 2025_10_06_200521_add_origin_to_orders_table
✓ 2025_10_06_201030_make_cpf_nullable_in_clients_table
✓ 2025_10_06_201114_remove_unique_constraint_from_cpf_in_clients_table
```

## Próximos Passos (Opcional)

### Frontend
- [ ] Exibir badge de origem do pedido na listagem
- [ ] Filtrar pedidos por origem
- [ ] Dashboard com estatísticas por canal

### Backend
- [ ] Adicionar validação de CPF quando fornecido
- [ ] Implementar autenticação para clientes (opcional)
- [ ] Relatórios de conversão por canal

## Comandos de Rollback (Se Necessário)

```bash
# Reverter todas as migrations
php artisan migrate:rollback --step=4

# Ou reverter individualmente
php artisan migrate:rollback --path=database/migrations/2025_10_06_201114_remove_unique_constraint_from_cpf_in_clients_table.php
php artisan migrate:rollback --path=database/migrations/2025_10_06_201030_make_cpf_nullable_in_clients_table.php
php artisan migrate:rollback --path=database/migrations/2025_10_06_200521_add_origin_to_orders_table.php
php artisan migrate:rollback --path=database/migrations/2025_10_06_200457_make_password_nullable_in_clients_table.php
```
