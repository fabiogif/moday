# 🔍 Debug - Erro de Upload de Imagem

## 🐛 Erro Atual
```json
{
    "message": "Validation errors",
    "errors": {
        "image": [
            "Ocorreu uma falha no upload do campo image."
        ]
    }
}
```

## 📋 Possíveis Causas

### 1. Limites de Upload PHP
O erro "Ocorreu uma falha no upload" geralmente indica que o arquivo não chegou ao servidor.

**Verificar configurações PHP:**
```bash
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"
```

**Valores recomendados:**
```ini
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M
max_file_uploads = 20
```

### 2. Permissões de Diretório
```bash
# Verificar permissões
ls -la backend/storage/app/public

# Corrigir se necessário
chmod -R 775 backend/storage
chown -R www-data:www-data backend/storage  # Linux
# ou
chown -R _www:_www backend/storage  # Mac
```

### 3. Symlink do Storage
```bash
cd backend
php artisan storage:link

# Verificar se foi criado
ls -la public/storage
```

### 4. Nginx/Apache Limites
**Nginx (`nginx.conf`):**
```nginx
client_max_body_size 10M;
```

**Apache (`.htaccess`):**
```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

---

## 🔧 Soluções Passo a Passo

### Solução 1: Verificar e Ajustar PHP.ini

**Docker/Sail:**
```bash
# Criar arquivo php.ini customizado
cat > backend/php.ini << 'EOF'
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M
max_file_uploads = 20
max_execution_time = 300
EOF

# Adicionar ao docker-compose.yml
volumes:
  - ./php.ini:/usr/local/etc/php/conf.d/uploads.ini
```

**Digital Ocean:**
1. Vá em Settings → App-Level
2. Environment Variables
3. Adicionar:
   - `PHP_UPLOAD_MAX_FILESIZE=10M`
   - `PHP_POST_MAX_SIZE=10M`

### Solução 2: Debug no Controller

Adicionar logging para debug:

```php
// ProductApiController.php - método store/update
public function store(StoreUpdateProductRequest $request): JsonResponse
{
    try {
        $user = auth()->user();
        
        // DEBUG: Ver o que está chegando
        \Log::info('Upload Debug', [
            'has_file' => $request->hasFile('image'),
            'file_info' => $request->hasFile('image') ? [
                'name' => $request->file('image')->getClientOriginalName(),
                'size' => $request->file('image')->getSize(),
                'mime' => $request->file('image')->getMimeType(),
                'error' => $request->file('image')->getError(),
            ] : 'No file',
            'all_inputs' => $request->except(['image']),
        ]);
        
        // ... resto do código
```

### Solução 3: Verificar Validação

Adicionar mensagem mais específica:

```php
// StoreUpdateProductRequest.php
public function messages()
{
    return [
        'image.uploaded' => 'O arquivo de imagem não foi enviado corretamente. Verifique o tamanho.',
        'image.image' => 'O arquivo deve ser uma imagem válida.',
        'image.mimes' => 'A imagem deve ser nos formatos: jpeg, png, jpg, gif ou svg.',
        'image.max' => 'A imagem não pode ser maior que 2MB.',
    ];
}
```

### Solução 4: Frontend - Verificar Envio

```typescript
// Verificar o FormData antes de enviar
const handleSubmit = async (formData: FormData) => {
  // Debug
  console.log('FormData entries:')
  for (let [key, value] of formData.entries()) {
    if (value instanceof File) {
      console.log(`${key}:`, {
        name: value.name,
        size: value.size,
        type: value.type
      })
    } else {
      console.log(`${key}:`, value)
    }
  }
  
  // Enviar
  await mutate(endpoint, 'POST', formData)
}
```

---

## 🧪 Testes de Verificação

### Teste 1: Upload Simples via CURL
```bash
curl -X POST http://localhost:8000/api/product \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "name=Teste Upload" \
  -F "description=Testando upload" \
  -F "price=100.00" \
  -F "qtd_stock=10" \
  -F "categories[0]=CATEGORY_UUID" \
  -F "image=@/path/to/test-image.jpg"
```

### Teste 2: Verificar Logs
```bash
# Tail nos logs do Laravel
tail -f backend/storage/logs/laravel.log

# Filtrar erros de upload
grep -i "upload\|image" backend/storage/logs/laravel.log | tail -20
```

### Teste 3: Verificar Permissões
```bash
# Testar criação de arquivo
touch backend/storage/app/public/test.txt
rm backend/storage/app/public/test.txt

# Se falhar, ajustar permissões
chmod -R 775 backend/storage
```

---

## ✅ Checklist de Debug

### Backend
- [ ] PHP upload_max_filesize >= 10M
- [ ] PHP post_max_size >= 10M
- [ ] Diretório storage/app/public tem permissão de escrita
- [ ] Symlink storage:link criado
- [ ] Logs não mostram erro de disco cheio
- [ ] Validação está como 'nullable'

### Frontend
- [ ] FormData contém o arquivo
- [ ] Arquivo é menor que 2MB
- [ ] Tipo MIME é válido (image/*)
- [ ] Headers corretos (multipart/form-data)
- [ ] Token JWT válido

### Servidor
- [ ] Nginx/Apache permite uploads grandes
- [ ] Disco tem espaço disponível
- [ ] Processo PHP tem permissão de escrita

---

## 🔍 Script de Diagnóstico

Criar e executar:

```bash
#!/bin/bash
# check-upload.sh

echo "=== Verificação de Upload ==="
echo ""

echo "1. Configurações PHP:"
php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
php -r "echo 'memory_limit: ' . ini_get('memory_limit') . PHP_EOL;"
echo ""

echo "2. Permissões Storage:"
ls -la backend/storage/app/public | head -5
echo ""

echo "3. Symlink Storage:"
ls -la backend/public/storage 2>&1
echo ""

echo "4. Espaço em Disco:"
df -h backend/storage
echo ""

echo "5. Últimos erros de upload:"
grep -i "upload\|image" backend/storage/logs/laravel.log 2>/dev/null | tail -5
echo ""

echo "=== Fim da Verificação ==="
```

Executar:
```bash
chmod +x check-upload.sh
./check-upload.sh
```

---

## 💡 Solução Rápida (Temporária)

Se precisa de uma solução imediata enquanto investiga:

1. **Aumentar limites no .env:**
```env
# .env
UPLOAD_MAX_FILESIZE=10M
POST_MAX_SIZE=10M
```

2. **Adicionar ao bootstrap/app.php:**
```php
// Aumentar limites em runtime (não recomendado para produção)
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('memory_limit', '256M');
```

3. **Criar produto sem imagem primeiro:**
   - Criar produto
   - Depois editar para adicionar imagem

---

## 📊 Logs Úteis

### Ver requisição completa
```php
// Em ProductApiController
\Log::info('Request completa', [
    'method' => $request->method(),
    'url' => $request->fullUrl(),
    'headers' => $request->headers->all(),
    'has_file' => $request->hasFile('image'),
    'content_type' => $request->header('Content-Type'),
]);
```

### Ver erro específico de upload
```php
if ($request->hasFile('image')) {
    $error = $request->file('image')->getError();
    \Log::error('Upload error code: ' . $error);
    
    // Códigos de erro PHP:
    // UPLOAD_ERR_OK = 0 (sucesso)
    // UPLOAD_ERR_INI_SIZE = 1 (arquivo maior que upload_max_filesize)
    // UPLOAD_ERR_FORM_SIZE = 2 (arquivo maior que MAX_FILE_SIZE do form)
    // UPLOAD_ERR_PARTIAL = 3 (upload parcial)
    // UPLOAD_ERR_NO_FILE = 4 (nenhum arquivo enviado)
}
```

---

## 🎯 Solução Definitiva

**Arquivo:** `backend/.do/app.yaml` ou `.platform.app.yaml`

```yaml
build:
  pre_build:
    - echo "upload_max_filesize = 10M" >> /opt/heroku/php/etc/php/php.ini
    - echo "post_max_size = 10M" >> /opt/heroku/php/etc/php/php.ini
    - echo "memory_limit = 256M" >> /opt/heroku/php/etc/php/php.ini
```

Ou criar `backend/php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M
max_file_uploads = 20
max_execution_time = 300
```

---

**Última Atualização:** 2025-10-14  
**Status:** Guia de Debugging Completo
