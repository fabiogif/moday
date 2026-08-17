# Guia Rápido de Correções - Problemas de Produtos

## 🎯 O que foi corrigido?

1. **Erro "deleted_at does not exist"** - ✅ RESOLVIDO
   - Removido SoftDeletes do modelo Product
   - Produtos agora carregam normalmente

2. **Product ID undefined** - ✅ DEVE ESTAR RESOLVIDO
   - Era causado pelo erro anterior
   - Teste para confirmar

3. **Erro de validação ao editar** - ✅ DEVE ESTAR RESOLVIDO
   - Era causado pelo erro anterior
   - Código de validação já estava correto

4. **Botão Ver Detalhes desabilitado** - ✅ DEVE ESTAR RESOLVIDO
   - Será habilitado quando Product ID for resolvido

5. **Rate Limiter [login] not defined** - ⚠️ PRECISA LIMPAR CACHE
   - Configuração já existe
   - Execute o script de limpeza de cache

## 🚀 Como aplicar as correções?

### Passo 1: Fazer Pull das Alterações

```bash
cd /caminho/para/backend
git pull origin main
```

### Passo 2: Limpar Cache (IMPORTANTE!)

**Opção A - Usando o Script Automático:**
```bash
chmod +x fix-production-cache.sh
./fix-production-cache.sh
```

**Opção B - Manual:**
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Passo 3: Reiniciar Serviços

Se estiver usando:

**PM2:**
```bash
pm2 restart all
```

**Supervisor:**
```bash
sudo supervisorctl restart all
```

**Docker:**
```bash
docker-compose restart
```

### Passo 4: Testar

1. ✅ Acessar página de produtos
2. ✅ Verificar se produtos aparecem com IDs
3. ✅ Criar novo produto
4. ✅ Editar produto existente
5. ✅ Clicar em "Ver Detalhes"
6. ✅ Fazer login (testar Rate Limiter)

## 🐛 Problemas Persistentes?

### Se produtos ainda não carregarem:

```bash
# Ver logs do Laravel
tail -f storage/logs/laravel.log

# Ver últimas 100 linhas
tail -n 100 storage/logs/laravel.log
```

### Se Rate Limiter ainda não funcionar:

```bash
# Verificar se RouteServiceProvider está sendo carregado
php artisan about | grep -i provider

# Limpar TUDO
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Recriar caches
php artisan config:cache
php artisan route:cache
```

### Se erro de permissões:

```bash
# Ajustar permissões (use seu usuário/grupo)
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 📊 Verificar Status

```bash
# Ver informações do sistema
php artisan about

# Ver rotas da API
php artisan route:list | grep api/product

# Testar conexão com banco
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit
```

## 🔮 Futuro: Reativar SoftDeletes

Quando quiser voltar a usar SoftDeletes nos produtos:

### 1. Executar Migration no Banco de Produção

**No painel da Digital Ocean ou via SSH:**
```sql
ALTER TABLE products ADD COLUMN deleted_at TIMESTAMP NULL;
```

**Ou via Laravel:**
```bash
php artisan migrate --path=database/migrations/2025_10_13_195456_add_deleted_at_back_to_products_table.php
```

### 2. Editar o Modelo Product

```php
// backend/app/Models/Product.php
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;  // Adicionar SoftDeletes novamente
```

### 3. Commit e Deploy

```bash
git add app/Models/Product.php
git commit -m "feat: reactivate SoftDeletes on Product model"
git push
```

## 📞 Ajuda Adicional

### Logs Importantes

- **Laravel:** `storage/logs/laravel.log`
- **Nginx:** `/var/log/nginx/error.log`
- **PHP-FPM:** `/var/log/php8.2-fpm.log` (versão pode variar)

### Comandos Úteis de Debug

```bash
# Ver variáveis de ambiente
php artisan env

# Testar configuração
php artisan config:show database

# Ver lista de providers
php artisan about
```

### Suporte

Se os problemas persistirem após seguir todos os passos:

1. Verificar logs completos
2. Testar endpoint diretamente: `GET /api/product`
3. Verificar se há erros no console do navegador
4. Verificar Network tab para ver resposta da API

## ✅ Checklist Final

- [ ] Git pull executado
- [ ] Cache limpo com `optimize:clear`
- [ ] Novos caches criados
- [ ] Serviços reiniciados
- [ ] Produtos carregando com IDs
- [ ] Edição de produtos funcionando
- [ ] Ver Detalhes habilitado
- [ ] Login funcionando sem erro de Rate Limiter

---

**Data:** 13/01/2025
**Versão:** 1.0
**Ambiente:** Produção (Digital Ocean)
