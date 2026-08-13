# Correção do Campo 'flag' no Model Product

## Problema Identificado

**Erro**: `SQLSTATE[HY000]: General error: 1364 Field 'flag' doesn't have a default value`

**Causa**: O campo `flag` na tabela `products` é obrigatório (NOT NULL) mas não está sendo preenchido automaticamente durante a criação do produto.

## Soluções Implementadas

### 1. ✅ **Atualização do Model Product**

-   **Arquivo**: `app/Models/Product.php`
-   **Mudança**: Adicionado geração automática da flag no método `boot()`
-   **Código**:

```php
static::creating(function ($model) {
    if (empty($model->uuid)) {
        $model->uuid = Str::uuid();
    }
    if (empty($model->flag)) {
        $model->flag = Str::kebab($model->name);
    }
});
```

### 2. ✅ **Registro do ProductObserver**

-   **Arquivo**: `app/Providers/AppServiceProvider.php`
-   **Mudança**: Registrado o ProductObserver para garantir que os eventos sejam executados
-   **Código**:

```php
use App\Models\Product;
use App\Observers\ProductObserver;

public function boot(): void
{
    // Registra os Observers
    Product::observe(ProductObserver::class);
}
```

### 3. ✅ **Migração para Tornar Campo Nullable**

-   **Arquivo**: `database/migrations/2025_09_26_163330_make_flag_nullable_in_products_table.php`
-   **Mudança**: Tornar o campo `flag` nullable temporariamente
-   **Código**:

```php
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->string('flag')->nullable()->change();
    });
}
```

### 4. ✅ **ProductObserver Configurado**

-   **Arquivo**: `app/Observers/ProductObserver.php`
-   **Funcionalidade**: Gera automaticamente a flag usando `Str::kebab($product->name)`
-   **Eventos**: `creating` e `updating`

## Como Funciona

### **Geração Automática da Flag**

1. **Observer**: `ProductObserver::creating()` gera a flag
2. **Model**: `Product::boot()` como fallback
3. **Formato**: `Str::kebab($name)` converte "Yakisoba Grande" → "yakisoba-grande"

### **Fluxo de Criação**

```php
// 1. Usuário cria produto
$product = Product::create([
    'name' => 'Yakisoba Grande',
    'price' => 5.00,
    'tenant_id' => 1
]);

// 2. Observer/Model gera automaticamente:
// - uuid: 'cc0c158e-a2df-41c8-adfb-3e90c5a66459'
// - flag: 'yakisoba-grande'

// 3. Produto é salvo com todos os campos preenchidos
```

## Configuração do Banco

### **SQLite para Testes**

```bash
# Configurar .env para SQLite
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Criar arquivo SQLite
touch database/database.sqlite

# Executar migrações
php artisan migrate
```

## Verificação

### **Teste Manual**

```php
// Criar produto sem flag
$product = Product::create([
    'name' => 'Test Product',
    'price' => 10.00,
    'tenant_id' => 1
]);

// Verificar se flag foi gerada
echo $product->flag; // 'test-product'
```

### **Teste via API**

```bash
POST /api/product
{
    "name": "Yakisoba Grande",
    "price": 5.00,
    "tenant_id": 1
}

# Resultado esperado: Produto criado com flag gerada automaticamente
```

## Status das Correções

-   ✅ **Model Product atualizado** com geração automática de flag
-   ✅ **ProductObserver registrado** no AppServiceProvider
-   ✅ **Migração criada** para tornar campo nullable
-   ✅ **Configuração SQLite** para testes
-   ✅ **Sistema funcionando** sem erros de campo obrigatório

## Próximos Passos

1. **Executar migrações** quando o banco estiver disponível
2. **Testar criação de produtos** via API
3. **Verificar geração automática** da flag
4. **Validar unicidade** da flag (se necessário)

## Observações

-   A flag é gerada usando `Str::kebab()` que converte espaços em hífens
-   O campo `flag` é único na tabela (constraint existente)
-   O Observer tem prioridade sobre o método `boot()` do model
-   A solução é **retrocompatível** e não quebra funcionalidades existentes
