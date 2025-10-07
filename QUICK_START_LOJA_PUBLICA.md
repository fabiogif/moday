# 🚀 Quick Start - Loja Pública

## 📋 Setup Rápido (5 minutos)

### 1️⃣ Executar Migration

```bash
cd backend
php artisan migrate
```

Isso vai adicionar os campos `payment_method` e `shipping_method` na tabela `orders`.

---

### 2️⃣ Adicionar Slug ao Tenant

Opção 1 - Via SQL:
```sql
UPDATE tenants 
SET slug = 'minha-loja' 
WHERE id = 1;
```

Opção 2 - Via Tinker:
```bash
php artisan tinker

$tenant = \App\Models\Tenant::find(1);
$tenant->slug = 'minha-loja';
$tenant->save();
```

---

### 3️⃣ Acessar a Loja

```
http://localhost:3000/store/minha-loja
```

Substitua `minha-loja` pelo slug do seu tenant.

---

## 🧪 Testar Funcionalidades

### Teste 1: Visualizar Produtos ✅
- [ ] Acesse a loja
- [ ] Produtos aparecem em grid
- [ ] Preços visíveis
- [ ] Estoque exibido

### Teste 2: Carrinho ✅
- [ ] Adicione produtos
- [ ] Altere quantidades
- [ ] Remova produtos
- [ ] Total calculado corretamente

### Teste 3: Checkout ✅
- [ ] Clique em "Finalizar Pedido"
- [ ] Preencha dados do cliente
- [ ] Escolha entrega/retirada
- [ ] Preencha endereço (se entrega)
- [ ] Escolha forma de pagamento
- [ ] Confirme pedido

### Teste 4: WhatsApp ✅
- [ ] Pedido criado com sucesso
- [ ] Link WhatsApp gerado
- [ ] Clique em "Enviar via WhatsApp"
- [ ] WhatsApp abre com mensagem
- [ ] Mensagem formatada corretamente

---

## 📱 URLs Importantes

### Loja Pública
```
http://localhost:3000/store/{slug}
```

### API Endpoints
```
GET  http://localhost/api/store/{slug}/info
GET  http://localhost/api/store/{slug}/products
POST http://localhost/api/store/{slug}/orders
```

---

## 🔧 Configuração do Tenant

### Campos Obrigatórios

Para a loja funcionar corretamente, certifique-se que o tenant tem:

```sql
SELECT 
  id, 
  name,           -- Nome da loja ✅
  slug,           -- Slug único ✅
  email,          -- Email de contato ✅
  phone,          -- Telefone/WhatsApp ✅
  address,        -- Endereço (opcional)
  city,           -- Cidade (opcional)
  state,          -- Estado (opcional)
  zipcode,        -- CEP (opcional)
  logo,           -- Logo (opcional)
  is_active       -- Deve ser TRUE ✅
FROM tenants
WHERE id = 1;
```

**Campos essenciais:**
- `slug` - Para acessar a loja
- `phone` - Para integração WhatsApp
- `is_active = TRUE` - Loja ativa

---

## 🛒 Exemplo de Uso Completo

### Passo a Passo

1. **Cliente acessa:** `http://localhost:3000/store/minha-loja`

2. **Visualiza produtos:**
   - Suco de Laranja - R$ 5,00
   - Refrigerante - R$ 4,50
   - Água Mineral - R$ 2,00

3. **Adiciona ao carrinho:**
   - 2x Suco de Laranja
   - 1x Refrigerante
   - Total: R$ 14,50

4. **Finaliza pedido:**
   - Nome: João Silva
   - Email: joao@example.com
   - Telefone: (11) 99999-9999

5. **Escolhe entrega:**
   - Endereço: Rua das Flores, 123
   - Bairro: Centro
   - Cidade: São Paulo - SP
   - CEP: 01234-567

6. **Escolhe pagamento:**
   - PIX

7. **Confirma pedido:**
   - Pedido #A1B2C3D4 criado
   - Total: R$ 14,50

8. **Envia WhatsApp:**
   - Clica no botão
   - WhatsApp abre automaticamente
   - Mensagem formatada pronta para enviar

---

## 💡 Dicas

### Slug do Tenant
- Use apenas letras minúsculas, números e hífen
- Exemplos válidos: `minha-loja`, `pizzaria-brasil`, `loja-123`
- Exemplos inválidos: `Minha Loja`, `loja_123`, `loja.com`

### WhatsApp
- O telefone do tenant será usado para WhatsApp
- Formato automático: `55` + `DDD` + `número`
- Exemplo: `(11) 98765-4321` → `5511987654321`

### Estoque
- Produtos sem estoque aparecem como "Esgotado"
- Estoque é atualizado automaticamente ao confirmar pedido
- Validação de estoque antes de criar pedido

---

## 🐛 Troubleshooting

### Loja não encontrada
**Problema:** Ao acessar `/store/{slug}` recebe erro 404

**Solução:**
1. Verifique se o slug existe no banco
2. Verifique se tenant está ativo (`is_active = TRUE`)
3. Verifique o slug na URL (case-sensitive)

### Produtos não aparecem
**Problema:** Loja carrega mas sem produtos

**Solução:**
1. Verifique se existem produtos ativos (`is_active = TRUE`)
2. Verifique se produtos têm estoque (`qtd_stock > 0`)
3. Verifique se produtos pertencem ao tenant correto (`tenant_id`)

### Erro ao criar pedido
**Problema:** Erro 500 ao finalizar checkout

**Solução:**
1. Verifique logs do Laravel (`storage/logs/laravel.log`)
2. Verifique se migration foi executada
3. Verifique se campos obrigatórios estão preenchidos
4. Verifique se produtos têm estoque

### WhatsApp não abre
**Problema:** Link do WhatsApp não funciona

**Solução:**
1. Verifique se tenant tem telefone cadastrado
2. Verifique formato do telefone (números apenas)
3. Teste o link manualmente
4. Verifique se WhatsApp está instalado

---

## 📊 Dados de Teste

### SQL para Criar Dados de Teste

```sql
-- Criar/Atualizar Tenant
UPDATE tenants 
SET slug = 'loja-teste',
    phone = '11987654321',
    email = 'contato@lojateste.com',
    is_active = TRUE
WHERE id = 1;

-- Criar Produtos de Teste
INSERT INTO products (uuid, tenant_id, name, description, price, promotional_price, qtd_stock, is_active)
VALUES 
  (UUID(), 1, 'Produto Teste 1', 'Descrição do produto 1', 10.00, 8.00, 50, TRUE),
  (UUID(), 1, 'Produto Teste 2', 'Descrição do produto 2', 20.00, NULL, 30, TRUE),
  (UUID(), 1, 'Produto Teste 3', 'Descrição do produto 3', 15.00, 12.00, 20, TRUE);
```

### Acessar

```
http://localhost:3000/store/loja-teste
```

---

## ✅ Checklist de Validação

Antes de usar em produção:

- [ ] Migration executada
- [ ] Tenant tem slug único
- [ ] Tenant está ativo
- [ ] Tenant tem telefone (WhatsApp)
- [ ] Produtos estão ativos
- [ ] Produtos têm estoque
- [ ] Testou adicionar ao carrinho
- [ ] Testou finalizar pedido
- [ ] Testou link do WhatsApp
- [ ] Validou mensagem formatada
- [ ] Testou em mobile
- [ ] Testou diferentes formas de pagamento
- [ ] Testou entrega e retirada

---

## 🎯 Próximos Passos (Opcional)

Melhorias sugeridas:

1. **SEO**
   - Meta tags por loja
   - Título dinâmico
   - Descrição da loja

2. **Imagens**
   - Upload de logo
   - Galeria de produtos
   - Otimização de imagens

3. **Customização**
   - Cores personalizadas por tenant
   - Banner da loja
   - Sobre nós / Contato

4. **Funcionalidades**
   - Cupom de desconto
   - Frete calculado
   - Múltiplas fotos por produto
   - Categorias de produtos
   - Busca de produtos

5. **Pagamentos**
   - Integração PIX automática
   - Gateway de pagamento
   - Boleto bancário

6. **Notificações**
   - Email de confirmação
   - SMS de confirmação
   - Status do pedido

---

**Setup completo!** 🎉  
**Loja pronta para receber pedidos!** 🚀

Para mais detalhes, consulte: `LOJA_PUBLICA_IMPLEMENTACAO.md`
