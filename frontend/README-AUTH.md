# Sistema de Autenticação - Alba Tec Frontend

## ✅ Funcionalidades Implementadas

### 🔐 Autenticação

- **Middleware de proteção de rotas** (`src/middleware.ts`)
- **Contexto de autenticação com Zustand** (`src/contexts/auth-context.tsx`)
- **Página de login** baseada no sign-in-3 (`src/app/sign-in-3/`)
- **Formulários com validação** usando React Hook Form + Zod
- **Integração com API** do backend Laravel

### 📱 Páginas Criadas

- **Login** (`/sign-in-3`) - Página de autenticação
- **Produtos** (`/products`) - Gerenciamento de produtos
- **Pedidos** (`/orders`) - Gerenciamento de pedidos
- **Mesas** (`/tables`) - Gerenciamento de mesas
- **Categorias** (`/categories`) - Gerenciamento de categorias

### 🔌 API Routes

- **Autenticação**: `/api/auth/login`
- **Produtos**: `/api/product` (GET, POST, PUT, DELETE)
- **Pedidos**: `/api/orders` (GET, POST)
- **Mesas**: `/api/tables` (GET, POST, PUT, DELETE)
- **Categorias**: `/api/categories` (GET, POST, PUT, DELETE)

## 🛡️ Proteção de Rotas

### Rotas Públicas

- `/` - Página inicial (redireciona para login)
- `/sign-in-3` - Login
- `/sign-up-3` - Cadastro
- `/forgot-password-3` - Recuperação de senha

### Rotas Protegidas

- `/dashboard-2` - Dashboard principal
- `/products` - Produtos
- `/orders` - Pedidos
- `/tables` - Mesas
- `/categories` - Categorias
- `/mail`, `/tasks`, `/chat`, `/calendar`, `/users`, `/settings` - Outras funcionalidades

## 🎨 Tecnologias Utilizadas

- **Next.js 15** com App Router
- **React 19** com TypeScript
- **shadcn/ui v3** - Componentes modernos
- **Tailwind CSS v4** - Estilização
- **Zustand** - Gerenciamento de estado
- **React Hook Form + Zod** - Formulários e validação
- **TanStack Table** - Tabelas avançadas
- **Sonner** - Notificações toast

## 🚀 Como Usar

1. **Configurar variáveis de ambiente**:

   ```bash
   # Criar arquivo .env.local
   NEXT_PUBLIC_API_URL=http://localhost:8000
   ```

2. **Instalar dependências**:

   ```bash
   npm install
   # ou
   pnpm install
   ```

3. **Executar em desenvolvimento**:

   ```bash
   npm run dev
   # ou
   pnpm dev
   ```

4. **Acessar a aplicação**:
   - Frontend: http://localhost:3000
   - Backend: http://localhost

## 📋 Funcionalidades por Página

### 🔐 Login (`/sign-in-3`)

- Formulário de login com validação
- Integração com API do backend
- Redirecionamento automático após login
- Tratamento de erros

### 📦 Produtos (`/products`)

- Listagem de produtos com busca
- Criação, edição e exclusão de produtos
- Associação com categorias
- Validação de formulários

### 🛒 Pedidos (`/orders`)

- Listagem de pedidos
- Criação de novos pedidos
- Associação com mesas e clientes
- Status dos pedidos

### 🪑 Mesas (`/tables`)

- Gerenciamento de mesas
- Criação, edição e exclusão
- Identificação única das mesas

### 📂 Categorias (`/categories`)

- Gerenciamento de categorias
- Criação, edição e exclusão
- Associação com produtos

## 🔧 Configuração do Backend

O frontend está configurado para se comunicar com o backend Laravel através dos seguintes endpoints:

- **Autenticação**: `POST /api/auth/login`
- **Produtos**: `GET/POST/PUT/DELETE /api/product`
- **Pedidos**: `GET/POST /api/order`
- **Mesas**: `GET/POST/PUT/DELETE /api/table`
- **Categorias**: `GET/POST/PUT/DELETE /api/category`

## 🎯 Próximos Passos

1. **Implementar páginas de cadastro e recuperação de senha**
2. **Adicionar mais funcionalidades ao dashboard**
3. **Implementar relatórios e analytics**
4. **Adicionar testes automatizados**
5. **Configurar CI/CD**

## 📝 Notas Importantes

- Todas as rotas protegidas redirecionam para `/sign-in-3` se não autenticado
- O token JWT é armazenado em cookies para persistência
- O estado de autenticação é gerenciado pelo Zustand
- Todas as requisições para a API incluem o token de autorização
- O sistema está configurado para usar apenas o `dashboard-2` (removido o dashboard padrão)
