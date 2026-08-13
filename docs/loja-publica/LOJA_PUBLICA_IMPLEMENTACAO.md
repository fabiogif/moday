# 🛍️ Loja Pública - Sistema de E-commerce

## 📋 Visão Geral

Implementação completa de uma loja pública (não autenticada) para cada tenant, com carrinho de compras, checkout e integração com WhatsApp.

---

## ✨ Funcionalidades Implementadas

### 🏪 Loja Pública
- ✅ Página da loja acessível via URL `/store/{slug}`
- ✅ Exibição de produtos com preços e estoque
- ✅ Carrinho de compras completo
- ✅ Checkout com formulário de dados do cliente
- ✅ Suporte a entrega e retirada no local
- ✅ Múltiplas formas de pagamento
- ✅ Integração com WhatsApp automática
- ✅ Mensagem formatada para WhatsApp
- ✅ Confirmação de pedido via WhatsApp

### 🛒 Carrinho de Compras
- Adicionar/remover produtos
- Alterar quantidades
- Validação de estoque em tempo real
- Cálculo automático de totais
- Suporte a preços promocionais
- Badge com contador de itens

### 📦 Checkout
- Formulário de dados do cliente (nome, email, telefone, CPF)
- Seleção de método de envio (entrega/retirada)
- Formulário de endereço de entrega (condicional)
- Seleção de forma de pagamento
- Resumo do pedido
- Validação completa de dados

### 💬 Integração WhatsApp
- Mensagem automática com dados do pedido
- Link direto para WhatsApp do tenant
- Formatação profissional da mensagem
- Inclusão de todos os detalhes: produtos, endereço, pagamento

---

## 🌐 Rotas API (Backend)

### Rotas Públicas (Sem Autenticação)

```php
// Obter informações da loja
GET /api/store/{slug}/info

// Obter produtos da loja
GET /api/store/{slug}/products

// Criar pedido (checkout)
POST /api/store/{slug}/orders
```

### Exemplos de Requisições

#### 1. Obter Informações da Loja
```bash
GET http://localhost/api/store/empresa-dev/info
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "c63028d6-79dd-4841-90b7-3d785db7e212",
    "name": "Empresa Dev",
    "slug": "empresa-dev",
    "email": "empresadev@empresadev.com.br",
    "phone": "(11) 98765-4321",
    "address": "Rua Exemplo, 123",
    "city": "São Paulo",
    "state": "SP",
    "zipcode": "01234-567",
    "logo": "https://...",
    "whatsapp": "5511987654321"
  }
}
```

#### 2. Obter Produtos
```bash
GET http://localhost/api/store/empresa-dev/products
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "uuid": "39ef0065-d98a-4378-8e26-9cbd9d2bc1cb",
      "name": "Suco de Laranja 300ml",
      "description": "Suco natural de laranja",
      "price": 6.00,
      "promotional_price": 5.00,
      "image": "https://...",
      "qtd_stock": 50,
      "brand": "Natural",
      "categories": [...]
    }
  ]
}
```

#### 3. Criar Pedido (Checkout)
```bash
POST http://localhost/api/store/empresa-dev/orders
Content-Type: application/json

{
  "client": {
    "name": "João Silva",
    "email": "joao@example.com",
    "phone": "(11) 99999-9999",
    "cpf": "123.456.789-00"
  },
  "delivery": {
    "is_delivery": true,
    "address": "Rua das Flores",
    "number": "123",
    "neighborhood": "Centro",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01234-567",
    "complement": "Apto 45",
    "notes": "Portão azul, tocar campainha"
  },
  "products": [
    {
      "uuid": "39ef0065-d98a-4378-8e26-9cbd9d2bc1cb",
      "quantity": 2
    }
  ],
  "payment_method": "pix",
  "shipping_method": "delivery"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Pedido criado com sucesso",
  "data": {
    "order_id": "A1B2C3D4",
    "total": "12,00",
    "whatsapp_link": "https://wa.me/5511987654321?text=...",
    "whatsapp_message": "..."
  }
}
```

---

## 🔧 Estrutura de Arquivos

### Backend

```
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── PublicStoreController.php  ← NOVO Controller
│   └── Models/
│       ├── Order.php                          ← Atualizado
│       ├── Tenant.php
│       ├── Product.php
│       └── Client.php
├── database/
│   └── migrations/
│       └── 2025_01_06_000000_add_payment_shipping_to_orders.php  ← NOVA Migration
└── routes/
    └── api.php                                ← Atualizado
```

### Frontend

```
frontend/
└── src/
    └── app/
        └── store/
            └── [slug]/
                └── page.tsx  ← NOVA Página da Loja
```

---

## 💾 Banco de Dados

### Migration: Adicionar Campos de Pagamento/Envio

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('payment_method')->nullable();
    $table->string('shipping_method')->nullable();
});
```

**Executar:**
```bash
cd backend
php artisan migrate
```

---

## 🎨 Frontend - Página da Loja

### Acesso
```
http://localhost:3000/store/{slug}
```

Exemplo:
```
http://localhost:3000/store/empresa-dev
```

### Features UI

#### 1. **Header**
- Logo da loja
- Nome da empresa
- Telefone e email
- Botão do carrinho com badge de contagem

#### 2. **Grid de Produtos**
- Cards responsivos (1-4 colunas)
- Imagem do produto
- Nome e descrição
- Preço (com desconto se houver)
- Badge de desconto (%)
- Indicador de estoque
- Botão "Adicionar ao Carrinho"

#### 3. **Carrinho (Bottom Sheet)**
- Lista de itens no carrinho
- Controles de quantidade (+/-)
- Botão remover
- Subtotal por item
- Total geral
- Botão "Finalizar Pedido"

#### 4. **Checkout (Step 2)**
- Formulário de dados pessoais
  - Nome, email, telefone, CPF
- Seleção de método de envio
  - Entrega / Retirada
- Formulário de endereço (condicional)
  - Endereço, número, bairro
  - Cidade, estado, CEP
  - Complemento
  - Observações
- Seleção de forma de pagamento
  - PIX
  - Cartão de Crédito
  - Cartão de Débito
  - Dinheiro
  - Transferência Bancária
- Resumo do pedido
- Botão "Confirmar Pedido"

#### 5. **Confirmação (Step 3)**
- Ícone de sucesso
- Número do pedido
- Total do pedido
- Próximos passos
- Botão "Enviar via WhatsApp"
- Botão "Fazer Novo Pedido"

---

## 📱 Integração WhatsApp

### Formato da Mensagem

```
*Novo Pedido #A1B2C3D4*

*Cliente:* João Silva
*Telefone:* (11) 99999-9999
*Email:* joao@example.com

*Produtos:*
• 2x Suco de Laranja 300ml - R$ 5,00

*Total:* R$ 10,00

*Endereço de Entrega:*
Rua das Flores, 123 - Apto 45
Centro, São Paulo/SP
CEP: 01234-567

*Observações:* Portão azul, tocar campainha

*Forma de Pagamento:* PIX
```

### Link WhatsApp Gerado

```
https://wa.me/5511987654321?text=*Novo%20Pedido%20%23A1B2C3D4*...
```

- Abre WhatsApp automaticamente
- Mensagem pré-formatada
- Pronto para enviar

---

## 🔒 Segurança

### Validações Implementadas

#### Backend
- ✅ Validação de tenant ativo
- ✅ Validação de estoque disponível
- ✅ Validação de dados do cliente
- ✅ Validação de endereço (condicional)
- ✅ Validação de produtos existentes
- ✅ Rate limiting (10 pedidos/minuto por IP)
- ✅ Sanitização de dados

#### Frontend
- ✅ Validação de campos obrigatórios
- ✅ Validação de email
- ✅ Validação de estoque antes de adicionar
- ✅ Controle de quantidade máxima
- ✅ Feedback de erros para usuário

---

## 🚀 Como Usar

### 1. Executar Migration

```bash
cd backend
php artisan migrate
```

### 2. Criar Slug para Tenant

No banco de dados, adicione um slug ao tenant:

```sql
UPDATE tenants 
SET slug = 'minha-loja' 
WHERE id = 1;
```

### 3. Acessar Loja

```
http://localhost:3000/store/minha-loja
```

### 4. Testar Checkout

1. Adicione produtos ao carrinho
2. Clique em "Finalizar Pedido"
3. Preencha os dados do cliente
4. Escolha método de envio
5. Preencha endereço (se entrega)
6. Escolha forma de pagamento
7. Confirme o pedido
8. Clique em "Enviar via WhatsApp"

---

## 📊 Formas de Pagamento

| Valor | Label |
|-------|-------|
| `pix` | PIX |
| `credit_card` | Cartão de Crédito |
| `debit_card` | Cartão de Débito |
| `money` | Dinheiro |
| `bank_transfer` | Transferência Bancária |

---

## 📮 Métodos de Envio

| Valor | Label |
|-------|-------|
| `delivery` | Entrega no endereço |
| `pickup` | Retirar no local |

---

## 🎯 Fluxo Completo

```
1. Cliente acessa: /store/{slug}
   ↓
2. Visualiza produtos e informações da loja
   ↓
3. Adiciona produtos ao carrinho
   ↓
4. Clica em "Finalizar Pedido"
   ↓
5. Preenche dados pessoais
   ↓
6. Escolhe método de envio (entrega/retirada)
   ↓
7. Se entrega: preenche endereço
   ↓
8. Escolhe forma de pagamento
   ↓
9. Confirma pedido
   ↓
10. Sistema cria pedido e cliente no banco
    ↓
11. Sistema atualiza estoque dos produtos
    ↓
12. Sistema gera mensagem formatada WhatsApp
    ↓
13. Cliente é redirecionado para tela de sucesso
    ↓
14. Cliente clica "Enviar via WhatsApp"
    ↓
15. WhatsApp abre com mensagem pré-formatada
    ↓
16. Cliente envia pedido para loja
    ↓
17. Loja recebe pedido pelo WhatsApp
```

---

## 🛠️ Endpoints Detalhados

### GET /api/store/{slug}/info

**Descrição:** Retorna informações públicas da loja

**Parâmetros:**
- `slug` (path) - Slug único da loja

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "...",
    "name": "Nome da Loja",
    "slug": "nome-da-loja",
    "email": "contato@loja.com",
    "phone": "(11) 98765-4321",
    "address": "Rua ...",
    "city": "São Paulo",
    "state": "SP",
    "zipcode": "01234-567",
    "logo": "https://...",
    "whatsapp": "5511987654321"
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Loja não encontrada ou inativa"
}
```

---

### GET /api/store/{slug}/products

**Descrição:** Retorna produtos ativos da loja com estoque

**Parâmetros:**
- `slug` (path) - Slug único da loja

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "uuid": "...",
      "name": "Produto",
      "description": "Descrição",
      "price": 10.00,
      "promotional_price": 8.00,
      "image": "https://...",
      "qtd_stock": 50,
      "brand": "Marca",
      "sku": "SKU123",
      "variations": [],
      "categories": [...]
    }
  ]
}
```

---

### POST /api/store/{slug}/orders

**Descrição:** Cria pedido público sem autenticação

**Parâmetros:**
- `slug` (path) - Slug único da loja

**Body:**
```json
{
  "client": {
    "name": "string (required)",
    "email": "string (required, email)",
    "phone": "string (required)",
    "cpf": "string (optional)"
  },
  "delivery": {
    "is_delivery": "boolean (required)",
    "address": "string (required if is_delivery=true)",
    "number": "string (required if is_delivery=true)",
    "neighborhood": "string (required if is_delivery=true)",
    "city": "string (required if is_delivery=true)",
    "state": "string (required if is_delivery=true, max:2)",
    "zip_code": "string (required if is_delivery=true)",
    "complement": "string (optional)",
    "notes": "string (optional)"
  },
  "products": [
    {
      "uuid": "string (required, exists:products)",
      "quantity": "integer (required, min:1)"
    }
  ],
  "payment_method": "string (required, in:pix,credit_card,debit_card,money,bank_transfer)",
  "shipping_method": "string (required, in:delivery,pickup)"
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Pedido criado com sucesso",
  "data": {
    "order_id": "A1B2C3D4",
    "total": "12,00",
    "whatsapp_link": "https://wa.me/...",
    "whatsapp_message": "..."
  }
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Dados inválidos",
  "errors": {
    "client.name": ["O campo nome é obrigatório."],
    "client.email": ["O campo email deve ser um endereço válido."]
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Produto X sem estoque suficiente"
}
```

---

## 🧪 Testes

### Testar Manualmente

#### 1. Criar Tenant com Slug
```sql
INSERT INTO tenants (name, slug, email, phone, is_active) 
VALUES ('Minha Loja', 'minha-loja', 'contato@loja.com', '11987654321', 1);
```

#### 2. Criar Produtos
```sql
INSERT INTO products (uuid, tenant_id, name, price, qtd_stock, is_active)
VALUES 
  (UUID(), 1, 'Produto 1', 10.00, 50, 1),
  (UUID(), 1, 'Produto 2', 20.00, 30, 1);
```

#### 3. Acessar Loja
```
http://localhost:3000/store/minha-loja
```

#### 4. Fazer Pedido
- Adicione produtos ao carrinho
- Finalize o pedido
- Preencha os dados
- Confirme
- Teste o link do WhatsApp

---

## 📝 Checklist de Implementação

### Backend
- [x] Controller PublicStoreController
- [x] Rotas públicas em api.php
- [x] Migration payment_shipping_to_orders
- [x] Atualização Model Order (fillable)
- [x] Validação de dados
- [x] Criação/atualização de cliente
- [x] Cálculo de total
- [x] Validação de estoque
- [x] Atualização de estoque
- [x] Geração de mensagem WhatsApp
- [x] Geração de link WhatsApp
- [x] Rate limiting

### Frontend
- [x] Página /store/[slug]/page.tsx
- [x] Listagem de produtos
- [x] Carrinho de compras
- [x] Checkout completo
- [x] Formulário de cliente
- [x] Formulário de endereço
- [x] Seleção de pagamento/envio
- [x] Tela de sucesso
- [x] Integração WhatsApp
- [x] Responsividade
- [x] Validações
- [x] Feedback de erros

---

## 🎉 Resultado Final

Sistema completo de loja pública com:

✅ **Catálogo de Produtos** - Grid responsivo com preços e estoque  
✅ **Carrinho de Compras** - Adicionar/remover/alterar quantidades  
✅ **Checkout Completo** - Dados cliente, endereço, pagamento, envio  
✅ **Integração WhatsApp** - Mensagem formatada automática  
✅ **Gestão de Estoque** - Atualização automática ao confirmar pedido  
✅ **Multi-tenant** - Cada loja com seu slug único  
✅ **Sem Autenticação** - Acesso público e direto  
✅ **Responsivo** - Funciona em desktop, tablet e mobile  

---

**Desenvolvido em:** 05/01/2025  
**Status:** ✅ Completo e Funcional  
**Pronto para Produção!** 🚀
