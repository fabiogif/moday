# 📋 Documentação dos Testes de Upload de Imagem em Produtos

## 📍 Localização
**Arquivo:** `tests/Feature/Api/ProductImageUploadTest.php`

## 🎯 Objetivo
Testar de forma abrangente o upload de imagens em produtos, cobrindo cenários positivos, negativos, validações e segurança.

---

## 📝 Lista de Testes

### ✅ Testes de Criação com Imagem

#### 1. `pode_criar_produto_com_imagem_jpg`
**O que testa:** Criação de produto com imagem JPG
**Validações:**
- Status HTTP 201 (Created)
- Mensagem de sucesso
- Imagem salva no diretório correto do tenant

#### 2. `pode_criar_produto_com_imagem_png`
**O que testa:** Criação de produto com imagem PNG
**Validações:**
- Aceita formato PNG
- Salva corretamente no storage

#### 3. `pode_criar_produto_sem_imagem`
**O que testa:** Criação de produto SEM imagem (campo opcional)
**Validações:**
- Produto criado com sucesso
- Campo `image` fica `null` no banco
- Não requer imagem obrigatória

---

### 🔒 Testes de Validação

#### 4. `valida_tamanho_maximo_da_imagem`
**O que testa:** Validação de tamanho máximo (2MB)
**Cenário:** Tenta enviar imagem de 3MB
**Validações:**
- Status HTTP 422 (Unprocessable Entity)
- Erro de validação no campo `image`
- Imagem não é salva

#### 5. `valida_tipo_de_arquivo_da_imagem`
**O que testa:** Validação de tipo de arquivo
**Cenário:** Tenta enviar arquivo PDF ao invés de imagem
**Validações:**
- Rejeita arquivos não-imagem
- Retorna erro de validação
- Aceita apenas: jpeg, png, jpg, gif, svg

---

### 🔄 Testes de Atualização

#### 6. `pode_atualizar_produto_com_nova_imagem`
**O que testa:** Substituição de imagem existente
**Cenário:**
1. Produto criado com imagem A
2. Atualização enviando imagem B
**Validações:**
- Nova imagem salva corretamente
- Produto atualizado com sucesso

#### 7. `pode_atualizar_produto_sem_alterar_imagem`
**O que testa:** Atualização de produto mantendo imagem original
**Cenário:** Atualizar nome/preço sem enviar nova imagem
**Validações:**
- Atualização bem-sucedida
- Imagem original permanece intacta
- Dados de texto atualizados

---

### 🏢 Testes de Isolamento por Tenant

#### 8. `imagem_e_salva_no_diretorio_correto_do_tenant`
**O que testa:** Isolamento de arquivos por tenant
**Validações:**
- Imagem salva em `tenants/{uuid}/products/`
- NÃO salva em diretório global
- Estrutura de pastas correta

#### 9. `nao_pode_fazer_upload_para_produto_de_outro_tenant`
**O que testa:** Segurança entre tenants
**Cenário:** Usuário do Tenant A tenta atualizar produto do Tenant B
**Validações:**
- Status 404 (não revela existência)
- Imagem não é salva
- Isolamento de dados mantido

---

### 🌐 Testes de URL e Armazenamento

#### 10. `url_da_imagem_e_salva_corretamente_no_banco`
**O que testa:** Formato da URL salva no banco
**Validações:**
- URL completa com domínio
- Caminho correto: `/storage/tenants/{uuid}/products/`
- Hash único do arquivo

---

### 🎨 Testes de Formatos de Arquivo

#### 11. `pode_criar_produto_com_imagem_gif`
**O que testa:** Upload de GIF animado
**Validações:**
- Formato GIF aceito
- Salvo corretamente

#### 12. `pode_criar_produto_com_imagem_svg`
**O que testa:** Upload de SVG (vetor)
**Validações:**
- Formato SVG aceito
- Útil para ícones e logos

---

### 🔄 Testes de Casos Especiais

#### 13. `multiplos_produtos_podem_ter_imagens_com_mesmo_nome_original`
**O que testa:** Conflito de nomes de arquivo
**Cenário:** Dois produtos com arquivos chamados "produto.jpg"
**Validações:**
- Ambos salvos com hashes únicos
- Sem conflito de nomes
- Isolamento garantido

---

### 🔐 Testes de Segurança

#### 14. `requer_autenticacao_para_upload_de_imagem`
**O que testa:** Proteção de endpoint
**Cenário:** Requisição sem token JWT
**Validações:**
- Status 401 (Unauthorized)
- Nenhuma imagem salva
- Endpoint protegido

---

## 🚀 Como Executar os Testes

### Executar todos os testes de upload de imagem
```bash
cd backend
./vendor/bin/sail artisan test tests/Feature/Api/ProductImageUploadTest.php
```

### Executar teste específico
```bash
./vendor/bin/sail artisan test --filter=pode_criar_produto_com_imagem_jpg
```

### Executar com cobertura detalhada
```bash
./vendor/bin/sail artisan test tests/Feature/Api/ProductImageUploadTest.php --coverage
```

### Executar em paralelo (mais rápido)
```bash
./vendor/bin/sail artisan test tests/Feature/Api/ProductImageUploadTest.php --parallel
```

---

## 📊 Cobertura de Testes

### ✅ Cenários Cobertos

| Categoria | Testes | Status |
|-----------|--------|--------|
| Criação com imagem | 3 | ✅ |
| Validação de arquivo | 2 | ✅ |
| Atualização | 2 | ✅ |
| Isolamento de tenant | 2 | ✅ |
| Formatos de arquivo | 2 | ✅ |
| URL e armazenamento | 1 | ✅ |
| Casos especiais | 1 | ✅ |
| Segurança | 2 | ✅ |
| **TOTAL** | **15** | **✅** |

### 📈 Métricas Esperadas
- **Cobertura de código:** > 95% do ProductApiController
- **Tempo de execução:** < 5 segundos (todos os testes)
- **Taxa de sucesso:** 100%

---

## 🔍 Validações Automatizadas

### Formato de Arquivo
```php
'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048']
```

✅ Formatos aceitos: JPEG, PNG, JPG, GIF, SVG  
✅ Tamanho máximo: 2MB (2048 KB)  
✅ Campo opcional (nullable)

### Estrutura de Diretórios
```
storage/
└── app/
    └── public/
        └── tenants/
            └── {tenant-uuid}/
                └── products/
                    ├── abc123def456.jpg
                    ├── xyz789uvw012.png
                    └── ...
```

### URL Final no Banco
```
https://seu-dominio.com/storage/tenants/{tenant-uuid}/products/{hash}.jpg
```

---

## 🛠️ Configuração para Testes

### 1. Storage Fake
Os testes usam `Storage::fake('public')` para não criar arquivos reais durante os testes.

### 2. Factories Necessárias
- `PlanFactory`
- `TenantFactory`
- `UserFactory`
- `CategoryFactory`
- `ProductFactory`

### 3. Banco de Dados
Usa `RefreshDatabase` trait para limpar banco entre testes.

---

## 🐛 Troubleshooting

### Erro: "Storage disk does not exist"
**Solução:** Verificar configuração em `config/filesystems.php`

### Erro: "Class 'Storage' not found"
**Solução:** Adicionar `use Illuminate\Support\Facades\Storage;`

### Erro: "Column 'image' does not exist"
**Solução:** Rodar migrations: `php artisan migrate`

### Testes falhando com erro de permissão
**Solução:** 
```bash
chmod -R 775 storage
chown -R www-data:www-data storage
```

---

## 📚 Referências

### Laravel Testing
- [HTTP Tests](https://laravel.com/docs/http-tests)
- [File Upload Testing](https://laravel.com/docs/http-tests#testing-file-uploads)
- [Storage Testing](https://laravel.com/docs/filesystem#testing)

### Boas Práticas
1. Sempre usar `Storage::fake()` em testes
2. Testar cenários positivos E negativos
3. Validar isolamento entre tenants
4. Verificar segurança e autenticação
5. Testar diferentes formatos de arquivo

---

## 🎯 Checklist de Implementação

Ao modificar código de upload de imagens, verificar:

- [ ] Todos os testes passam
- [ ] Novos cenários têm testes correspondentes
- [ ] Validações de segurança mantidas
- [ ] Isolamento por tenant funciona
- [ ] URLs geradas corretamente
- [ ] Limpeza de imagens antigas (se aplicável)
- [ ] Documentação atualizada

---

## 💡 Exemplos de Uso nos Testes

### Criar Imagem Fake
```php
$image = UploadedFile::fake()->image('produto.jpg', 800, 600);
```

### Criar Arquivo Não-Imagem
```php
$file = UploadedFile::fake()->create('documento.pdf', 1000);
```

### Verificar Arquivo Existe
```php
Storage::disk('public')->assertExists('path/to/file.jpg');
```

### Verificar Arquivo NÃO Existe
```php
Storage::disk('public')->assertMissing('path/to/file.jpg');
```

---

## 🚨 Importante

### Antes de Deploy
```bash
# Executar todos os testes
./vendor/bin/sail artisan test

# Verificar cobertura
./vendor/bin/sail artisan test --coverage-html coverage/

# Executar apenas testes de imagem
./vendor/bin/sail artisan test tests/Feature/Api/ProductImageUploadTest.php
```

### Em Produção
- Configurar storage symlink: `php artisan storage:link`
- Verificar permissões: `storage/app/public` deve ser gravável
- Configurar limites PHP: `upload_max_filesize`, `post_max_size`
- Configurar backup de imagens

---

**Última Atualização:** 2025-10-14  
**Versão:** 1.0  
**Autor:** Sistema de Testes Automatizados  
**Status:** ✅ Completo e Funcional
