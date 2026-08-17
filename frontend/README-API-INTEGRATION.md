# Integração Frontend-Backend

## Configuração

### 1. Variáveis de Ambiente

Crie um arquivo `.env.local` na raiz do frontend com:

```env
# Configurações da API
NEXT_PUBLIC_API_URL=http://localhost:8000/api

# Configurações de autenticação
NEXT_PUBLIC_JWT_SECRET=your-jwt-secret-here

# Configurações de cache
NEXT_PUBLIC_CACHE_DURATION=300000
```

### 2. Endpoints Implementados

#### Backend (Laravel)
- **Produtos**: GET, POST, PUT, DELETE `/api/product`
- **Categorias**: GET, POST, PUT, DELETE `/api/category`
- **Pedidos**: GET, POST, PUT, DELETE `/api/order`
- **Mesas**: GET, POST, PUT, DELETE `/api/table`
- **Usuários**: GET, POST, PUT, DELETE `/api/users`
- **Clientes**: GET, POST, PUT, DELETE `/api/client`

#### Frontend (Next.js)
- **Páginas atualizadas**: products, categories, orders, tables, users
- **Cliente HTTP**: `src/lib/api-client.ts`
- **Hooks customizados**: `src/hooks/use-api.ts`

### 3. Funcionalidades Implementadas

#### Cliente HTTP (`api-client.ts`)
- Autenticação JWT automática
- Tratamento de erros padronizado
- Cache em memória (5 minutos)
- Headers automáticos

#### Hooks (`use-api.ts`)
- `useProducts()` - Lista produtos com cache
- `useCategories()` - Lista categorias com cache
- `useOrders()` - Lista pedidos com paginação
- `useTables()` - Lista mesas com cache
- `useUsers()` - Lista usuários com cache
- `useMutation()` - Operações CRUD
- `useAuth()` - Autenticação

#### Cache
- Cache automático em memória
- Duração configurável (padrão: 5 minutos)
- Invalidação automática após mutações
- Chaves de cache únicas por endpoint

### 4. Como Usar

#### Exemplo: Página de Produtos
```tsx
import { useProducts, useMutation } from "@/hooks/use-api"

export default function ProductsPage() {
  const { data: products, loading, error, refetch } = useProducts()
  const { mutate: createProduct } = useMutation()

  const handleAddProduct = async (productData) => {
    await createProduct('/api/product', 'POST', productData)
    await refetch() // Recarregar dados
  }

  if (loading) return <div>Carregando...</div>
  if (error) return <div>Erro: {error}</div>

  return <div>{/* Renderizar produtos */}</div>
}
```

#### Exemplo: Autenticação
```tsx
import { useAuth } from "@/hooks/use-api"

export default function LoginPage() {
  const { user, login, logout } = useAuth()

  const handleLogin = async (email, password) => {
    const result = await login(email, password)
    if (result.success) {
      // Redirecionar para dashboard
    }
  }

  return <div>{/* Formulário de login */}</div>
}
```

### 5. Estrutura de Dados

#### Produto
```typescript
interface Product {
  id: number
  name: string
  description: string
  price: number
  category: string
  stock: number
  isActive: boolean
  createdAt: string
}
```

#### Categoria
```typescript
interface Category {
  id: number
  name: string
  description: string
  color: string
  productCount: number
  isActive: boolean
  createdAt: string
}
```

#### Pedido
```typescript
interface Order {
  id: number
  orderNumber: string
  customerName: string
  customerEmail: string
  status: string
  total: number
  items: number
  orderDate: string
  deliveryDate: string
}
```

### 6. Tratamento de Erros

- Erros de rede são capturados automaticamente
- Mensagens de erro são exibidas na UI
- Retry automático em caso de falha temporária
- Logs detalhados no console

### 7. Performance

- Cache inteligente reduz chamadas à API
- Loading states melhoram UX
- Paginação para listas grandes
- Debounce em pesquisas

### 8. Próximos Passos

1. **Testes**: Implementar testes unitários e de integração
2. **Offline**: Adicionar suporte offline com Service Workers
3. **Real-time**: WebSockets para atualizações em tempo real
4. **Analytics**: Métricas de performance e uso
5. **PWA**: Transformar em Progressive Web App

### 9. Troubleshooting

#### Erro de CORS
- Verificar configuração CORS no Laravel
- Adicionar domínio do frontend em `config/cors.php`

#### Erro de Autenticação
- Verificar se o token JWT está sendo enviado
- Verificar se o token não expirou
- Verificar se o usuário tem permissões

#### Erro de Cache
- Limpar cache do navegador
- Verificar se as chaves de cache são únicas
- Verificar se o cache não está corrompido

### 10. Comandos Úteis

```bash
# Instalar dependências
npm install

# Executar em desenvolvimento
npm run dev

# Build para produção
npm run build

# Executar testes
npm test

# Linting
npm run lint
```
