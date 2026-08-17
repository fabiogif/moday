# ⚠️ NÃO EXECUTE SEEDERS EM PRODUÇÃO ⚠️

## O Erro que Você Está Vendo

```
Class "Faker\Factory" not found
```

## Por Que Acontece?

O Faker está em `require-dev` no composer.json, o que significa:
- É instalado em **desenvolvimento** (quando você roda `composer install`)
- **NÃO é instalado em produção** (quando o servidor roda `composer install --no-dev`)

Isso é **intencional** e **correto**!

## Por Que Faker Não Deve Estar em Produção?

1. **Segurança** - Bibliotecas de desenvolvimento não devem estar em produção
2. **Performance** - Reduz o tamanho da aplicação
3. **Boas práticas** - Separar dependências de dev/prod

## A Solução: NÃO EXECUTE SEEDERS EM PRODUÇÃO!

Seeders são APENAS para desenvolvimento/teste. Em produção:

### ❌ NÃO FAÇA:
```bash
php artisan db:seed              # NUNCA execute em produção!
php artisan migrate:fresh --seed # NUNCA! Vai apagar todos os dados!
```

### ✅ FAÇA:

**Para popular dados em produção:**

1. **Via Interface da Aplicação**
   - Cadastre usuários, categorias, produtos pela UI
   - Essa é a forma mais segura

2. **Via SQL Direto** (se necessário)
   ```sql
   INSERT INTO categories (name, description, tenant_id) 
   VALUES ('Bebidas', 'Categoria de bebidas', 1);
   ```

3. **Via Comando Artisan Customizado**
   ```php
   // app/Console/Commands/SeedProductionData.php
   public function handle() {
       // Lógica específica sem depender de Faker
   }
   ```

## O Que Fazer AGORA?

**PARE de tentar rodar seeders em produção!**

Em vez disso:

1. **Execute apenas migrations:**
   ```bash
   php artisan migrate
   ```

2. **Cadastre dados pela aplicação** ou SQL

3. **Use seeders apenas localmente:**
   ```bash
   # No seu computador local:
   php artisan db:seed  # ✅ OK em desenvolvimento!
   ```

## Se Você REALMENTE Precisar de Dados Iniciais

Crie migrations com dados ao invés de seeders:

```php
// database/migrations/2024_xx_xx_seed_initial_data.php
public function up() {
    DB::table('plans')->insert([
        ['name' => 'Básico', 'price' => 100],
        ['name' => 'Premium', 'price' => 200],
    ]);
}
```

Migrations são executadas em produção, seeders NÃO.

## Resumo

- ✅ Migrations → Use em produção
- ❌ Seeders → NUNCA use em produção
- ✅ Faker → Apenas em desenvolvimento
- ✅ Dados iniciais → Via migrations, SQL ou UI

---

**IMPORTANTE:** Se você está vendo esse erro, significa que você tentou executar seeders em produção. NÃO FAÇA ISSO! Use a aplicação normalmente para cadastrar dados.
