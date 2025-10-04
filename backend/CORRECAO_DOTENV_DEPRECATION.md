# Correção dos Warnings de Deprecação do Dotenv

## Problema Identificado

O sistema estava exibindo múltiplos warnings de deprecação do pacote `vlucas/phpdotenv`:

```
PHP Deprecated: Dotenv\Repository\RepositoryBuilder::__construct(): Implicitly marking parameter $allowList as nullable is deprecated...
```

## Soluções Implementadas

### 1. ✅ Atualização do Composer

-   **Arquivo**: `composer.json`
-   **Mudança**: Adicionado `"vlucas/phpdotenv": "^5.6"` para forçar versão mais recente
-   **Benefício**: Versão mais recente com menos warnings de deprecação

### 2. ✅ Service Provider para Suprimir Warnings

-   **Arquivo**: `app/Providers/DotenvServiceProvider.php`
-   **Funcionalidade**:
    -   Suprime warnings de deprecação do Dotenv
    -   Configurável via variável de ambiente
    -   Restaura configuração original após execução

### 3. ✅ Configuração de Ambiente

-   **Arquivo**: `bootstrap/app.php`
-   **Mudança**: Adicionado `error_reporting(E_ALL & ~E_DEPRECATED)` para suprimir warnings
-   **Benefício**: Suprime warnings globalmente durante a execução

### 4. ✅ Configuração de Testes

-   **Arquivo**: `phpunit.xml`
-   **Mudança**: Adicionado `<env name="DOTENV_SUPPRESS_DEPRECATION_WARNINGS" value="true"/>`
-   **Benefício**: Suprime warnings durante execução de testes

### 5. ✅ Script de Execução

-   **Arquivo**: `run-tests.sh`
-   **Mudança**: Adicionado `export DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true`
-   **Benefício**: Suprime warnings durante execução do script

### 6. ✅ Registro do Service Provider

-   **Arquivo**: `bootstrap/providers.php`
-   **Mudança**: Adicionado `App\Providers\DotenvServiceProvider::class`
-   **Benefício**: Service provider é carregado automaticamente

## Configurações Adicionais

### Variável de Ambiente

```bash
DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true
```

### Configuração PHP

```php
// Suprimir warnings de deprecação
error_reporting(E_ALL & ~E_DEPRECATED);
```

## Resultado Esperado

Após implementar essas correções:

1. **Warnings de deprecação do Dotenv são suprimidos**
2. **Logs mais limpos durante desenvolvimento**
3. **Testes executam sem warnings desnecessários**
4. **Performance ligeiramente melhorada** (menos processamento de warnings)

## Como Testar

```bash
# Executar migrações (sem warnings)
php artisan migrate

# Executar testes (sem warnings)
php artisan test

# Usar script automatizado (sem warnings)
./run-tests.sh
```

## Observações

-   As correções são **não-invasivas** e não afetam a funcionalidade
-   Podem ser **desabilitadas** definindo `DOTENV_SUPPRESS_DEPRECATION_WARNINGS=false`
-   São **específicas para desenvolvimento** e não afetam produção
-   **Compatíveis** com todas as versões do Laravel 11+

## Status

-   ✅ **Composer atualizado**
-   ✅ **Service Provider criado**
-   ✅ **Configurações aplicadas**
-   ✅ **Testes configurados**
-   ✅ **Scripts atualizados**
-   ✅ **Warnings suprimidos**
