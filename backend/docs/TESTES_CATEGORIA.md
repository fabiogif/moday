# Testes para Categoria

## Arquivos de Teste Criados

1. **`tests/Feature/CategoryTest.php`** - Teste completo com todos os cenários
2. **`tests/Feature/CategoryBasicTest.php`** - Teste básico simplificado
3. **`tests/Feature/CategorySimpleTest.php`** - Teste simples sem atributos PHPUnit
4. **`tests/Feature/CategoryManualTest.php`** - Teste manual com documentação

## Cenários de Teste Implementados

### ✅ Testes de Criação

-   ✅ Criar categoria com sucesso
-   ✅ Não pode criar categoria sem autenticação
-   ✅ Validação de campos obrigatórios
-   ✅ Validação de unicidade do nome por tenant
-   ✅ Pode criar categoria com mesmo nome em tenants diferentes

### ✅ Testes de Listagem

-   ✅ Listar categorias com paginação
-   ✅ Listar categorias vazias
-   ✅ Listar categorias com filtros

### ✅ Testes de Busca

-   ✅ Buscar categoria por ID/UUID
-   ✅ Não pode acessar categoria de outro tenant
-   ✅ Buscar categoria inexistente

### ✅ Testes de Atualização

-   ✅ Atualizar categoria com sucesso
-   ✅ Validação de campos na atualização
-   ✅ Atualizar categoria inexistente

### ✅ Testes de Exclusão

-   ✅ Deletar categoria com sucesso
-   ✅ Deletar categoria inexistente
-   ✅ Não pode deletar categoria de outro tenant

### ✅ Testes de Segurança

-   ✅ Isolamento por tenant
-   ✅ Autenticação obrigatória
-   ✅ Autorização por tenant

## Como Executar os Testes

### Opção 1: Docker (Recomendado)

```bash
# Certifique-se de que o Docker está rodando
docker-compose up -d

# Executar todos os testes de categoria
docker-compose exec app php artisan test tests/Feature/CategoryTest.php

# Executar teste específico
docker-compose exec app php artisan test tests/Feature/CategoryManualTest.php

# Executar com verbose
docker-compose exec app php artisan test tests/Feature/CategoryTest.php --verbose
```

### Opção 2: Local (se as extensões estiverem disponíveis)

```bash
# Executar todos os testes
php artisan test tests/Feature/CategoryTest.php

# Executar teste específico
php artisan test tests/Feature/CategoryManualTest.php

# Executar com verbose
php artisan test tests/Feature/CategoryTest.php --verbose
```

### Opção 3: Teste Individual

```bash
# Executar apenas um método de teste
php artisan test --filter test_pode_criar_categoria_com_sucesso
```

## Estrutura dos Testes

### Setup (Arrange)

-   Criação de tenant
-   Criação de usuário
-   Geração de token JWT
-   Preparação de dados de teste

### Execução (Act)

-   Chamadas HTTP para os endpoints
-   Headers de autenticação
-   Dados de entrada

### Verificação (Assert)

-   Status HTTP correto
-   Estrutura da resposta
-   Dados retornados
-   Persistência no banco

## Endpoints Testados

| Método | Endpoint               | Descrição           |
| ------ | ---------------------- | ------------------- |
| POST   | `/api/category`        | Criar categoria     |
| GET    | `/api/category`        | Listar categorias   |
| GET    | `/api/category/{uuid}` | Buscar categoria    |
| PUT    | `/api/category/{id}`   | Atualizar categoria |
| DELETE | `/api/category/{uuid}` | Deletar categoria   |

## Dados de Teste

### Categoria Válida

```json
{
    "name": "Churrasco",
    "description": "Categoria para produtos de churrasco",
    "url": "churrasco"
}
```

### Headers de Autenticação

```json
{
    "Authorization": "Bearer {token}",
    "Accept": "application/json"
}
```

## Respostas Esperadas

### Sucesso (201)

```json
{
    "success": true,
    "data": {
        "name": "Churrasco",
        "identify": "uuid-da-categoria",
        "description": "Categoria para produtos de churrasco",
        "url": "churrasco",
        "status": "A",
        "created_at": "01/01/2024"
    },
    "message": "Categoria criada com sucesso"
}
```

### Erro de Validação (422)

```json
{
    "success": false,
    "message": "Dados inválidos",
    "errors": {
        "name": ["O campo name é obrigatório."]
    }
}
```

### Erro de Autenticação (401)

```json
{
    "success": false,
    "message": "Token de acesso inválido ou expirado"
}
```

## Troubleshooting

### Problema: Extensão mbstring não encontrada

**Solução:** Use Docker ou instale a extensão mbstring no PHP

### Problema: Docker não está rodando

**Solução:** Execute `docker-compose up -d` antes dos testes

### Problema: Banco de dados não configurado

**Solução:** Execute `php artisan migrate` antes dos testes

### Problema: Token JWT inválido

**Solução:** Verifique se o usuário tem tenant_id associado

## Melhorias Futuras

-   [ ] Testes de performance
-   [ ] Testes de concorrência
-   [ ] Testes de cache
-   [ ] Testes de rate limiting
-   [ ] Testes de integração com produtos
