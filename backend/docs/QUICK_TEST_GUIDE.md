# 🧪 Guia Rápido - Testes de Upload de Imagem

## ⚡ Execução Rápida

### Executar todos os testes de upload de imagem
```bash
./vendor/bin/sail artisan test tests/Feature/Api/ProductImageUploadTest.php
```

### Executar com output detalhado
```bash
./vendor/bin/sail artisan test tests/Feature/Api/ProductImageUploadTest.php --verbose
```

---

## 📋 Resultado Esperado

```
PASS  Tests\Feature\Api\ProductImageUploadTest
✓ pode criar produto com imagem jpg                               0.15s
✓ pode criar produto com imagem png                               0.12s
✓ pode criar produto sem imagem                                   0.10s
✓ valida tamanho maximo da imagem                                 0.08s
✓ valida tipo de arquivo da imagem                                0.09s
✓ pode atualizar produto com nova imagem                          0.14s
✓ pode atualizar produto sem alterar imagem                       0.11s
✓ imagem e salva no diretorio correto do tenant                   0.13s
✓ url da imagem e salva corretamente no banco                     0.12s
✓ pode criar produto com imagem gif                               0.10s
✓ pode criar produto com imagem svg                               0.09s
✓ multiplos produtos podem ter imagens com mesmo nome original    0.16s
✓ requer autenticacao para upload de imagem                       0.07s
✓ nao pode fazer upload para produto de outro tenant              0.15s

Tests:    14 passed (40 assertions)
Duration: 1.61s
```

---

## 🎯 Testes Individuais

### Teste específico por nome
```bash
./vendor/bin/sail artisan test --filter=pode_criar_produto_com_imagem_jpg
```

### Teste de validação de tamanho
```bash
./vendor/bin/sail artisan test --filter=valida_tamanho_maximo_da_imagem
```

### Teste de segurança
```bash
./vendor/bin/sail artisan test --filter=requer_autenticacao_para_upload_de_imagem
```

---

## 🔧 Troubleshooting

### Erro: "Target class [ProductImageUploadTest] does not exist"
**Solução:**
```bash
composer dump-autoload
./vendor/bin/sail artisan clear-compiled
```

### Erro: "Database connection failed"
**Solução:**
```bash
# Verificar se containers estão rodando
docker ps

# Iniciar containers se necessário
./vendor/bin/sail up -d

# Rodar migrations
./vendor/bin/sail artisan migrate --env=testing
```

### Testes lentos
**Solução:** Usar banco em memória (SQLite)
```bash
# Em phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## 📊 Cobertura dos Testes

### Ver quais linhas de código são testadas
```bash
./vendor/bin/sail artisan test --coverage
```

### Gerar relatório HTML de cobertura
```bash
./vendor/bin/sail artisan test --coverage-html coverage/
```

Depois abra: `coverage/index.html` no navegador

---

## ✅ O Que os Testes Verificam

### ✓ Funcionalidade Básica
- [x] Upload de imagem JPG, PNG, GIF, SVG
- [x] Criação de produto com imagem
- [x] Criação de produto sem imagem (opcional)
- [x] Atualização com nova imagem
- [x] Atualização sem alterar imagem

### ✓ Validações
- [x] Tamanho máximo (2MB)
- [x] Tipos de arquivo permitidos
- [x] Rejeição de arquivos inválidos

### ✓ Segurança
- [x] Requer autenticação
- [x] Isolamento entre tenants
- [x] Não pode acessar produtos de outros tenants

### ✓ Armazenamento
- [x] Salva no diretório correto do tenant
- [x] URL completa gerada corretamente
- [x] Hash único para evitar conflitos

---

## 🚀 CI/CD Integration

### GitHub Actions
```yaml
- name: Run Product Image Upload Tests
  run: ./vendor/bin/sail artisan test tests/Feature/Api/ProductImageUploadTest.php
```

### GitLab CI
```yaml
test:image_upload:
  script:
    - php artisan test tests/Feature/Api/ProductImageUploadTest.php
```

---

## 📝 Adicionar Novos Testes

### Template para novo teste
```php
#[Test]
public function seu_novo_teste()
{
    // Arrange (preparar)
    $image = UploadedFile::fake()->image('test.jpg');
    
    // Act (executar)
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
        'Accept' => 'application/json'
    ])->postJson('/api/product', [
        'name' => 'Teste',
        'price' => 100.00,
        'qtd_stock' => 10,
        'image' => $image,
        'categories' => [$this->category->uuid]
    ]);
    
    // Assert (verificar)
    $response->assertStatus(201);
    Storage::disk('public')->assertExists(
        "tenants/{$this->tenant->uuid}/products/{$image->hashName()}"
    );
}
```

---

## 🎓 Comandos Úteis

```bash
# Executar todos os testes
./vendor/bin/sail artisan test

# Executar apenas testes de Feature
./vendor/bin/sail artisan test tests/Feature/

# Executar testes em paralelo (mais rápido)
./vendor/bin/sail artisan test --parallel

# Executar com detalhes de falhas
./vendor/bin/sail artisan test --stop-on-failure

# Ver apenas testes que falharam
./vendor/bin/sail artisan test --bail
```

---

## 📚 Documentação Completa

Para documentação detalhada, veja:
- `TESTS_IMAGE_UPLOAD_DOCUMENTATION.md` - Documentação completa
- `tests/Feature/Api/ProductImageUploadTest.php` - Código dos testes

---

**Dica:** Execute os testes sempre que modificar código relacionado a upload de imagens!
