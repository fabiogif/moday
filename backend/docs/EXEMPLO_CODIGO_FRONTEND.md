# 📝 Exemplos de Código para o Frontend

## Configuração da URL da API

### Opção 1: Usando variável de ambiente (Recomendado)

```javascript
// .env.local (desenvolvimento)
NEXT_PUBLIC_API_URL=http://localhost:8000

// .env.production (produção)
NEXT_PUBLIC_API_URL=https://orca-app-7hejo.ondigitalocean.app
```

### Opção 2: Arquivo de configuração

```javascript
// config/api.js
const API_CONFIG = {
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'https://orca-app-7hejo.ondigitalocean.app',
  timeout: 10000,
};

export default API_CONFIG;
```

---

## Exemplos de Requisições

### 1. Buscar Informações da Loja

```javascript
// services/storeService.js
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://orca-app-7hejo.ondigitalocean.app';

export async function getStoreInfo(slug) {
  try {
    const response = await fetch(`${API_URL}/store/${slug}/info`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Erro ao buscar informações da loja:', error);
    throw error;
  }
}

// Uso:
const storeData = await getStoreInfo('empresa-oi');
console.log(storeData);
// { success: true, data: { id: 3, name: "Empresa oi", ... } }
```

### 2. Buscar Produtos da Loja

```javascript
export async function getStoreProducts(slug) {
  try {
    const response = await fetch(`${API_URL}/store/${slug}/products`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Erro ao buscar produtos:', error);
    throw error;
  }
}

// Uso:
const products = await getStoreProducts('empresa-oi');
console.log(products);
// { success: true, data: [...] }
```

### 3. Criar Pedido

```javascript
export async function createOrder(slug, orderData) {
  try {
    const response = await fetch(`${API_URL}/store/${slug}/orders`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(orderData),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erro ao criar pedido');
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Erro ao criar pedido:', error);
    throw error;
  }
}

// Uso:
const orderData = {
  client: {
    name: "João Silva",
    email: "joao@email.com",
    phone: "11999999999",
    cpf: "12345678900"
  },
  products: [
    {
      uuid: "product-uuid-123",
      quantity: 2
    }
  ],
  delivery: {
    is_delivery: true,
    address: "Rua Example",
    number: "123",
    neighborhood: "Centro",
    city: "São Paulo",
    state: "SP",
    zip_code: "01000-000",
    complement: "Apto 45",
    notes: "Entregar na portaria"
  },
  payment_method: "pix",
  shipping_method: "delivery"
};

const result = await createOrder('empresa-oi', orderData);
console.log(result);
// { success: true, data: { order_id: "ABC123", whatsapp_link: "..." } }
```

---

## Componente React Completo

```jsx
// components/StorePage.jsx
import { useState, useEffect } from 'react';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://orca-app-7hejo.ondigitalocean.app';

export default function StorePage({ slug }) {
  const [storeInfo, setStoreInfo] = useState(null);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function fetchStoreData() {
      try {
        setLoading(true);
        
        // Buscar informações da loja
        const infoResponse = await fetch(`${API_URL}/store/${slug}/info`);
        const infoData = await infoResponse.json();
        
        if (infoData.success) {
          setStoreInfo(infoData.data);
        }

        // Buscar produtos
        const productsResponse = await fetch(`${API_URL}/store/${slug}/products`);
        const productsData = await productsResponse.json();
        
        if (productsData.success) {
          setProducts(productsData.data);
        }

      } catch (err) {
        setError(err.message);
        console.error('Erro ao carregar dados da loja:', err);
      } finally {
        setLoading(false);
      }
    }

    if (slug) {
      fetchStoreData();
    }
  }, [slug]);

  if (loading) {
    return <div>Carregando...</div>;
  }

  if (error) {
    return <div>Erro: {error}</div>;
  }

  return (
    <div>
      <h1>{storeInfo?.name}</h1>
      <p>{storeInfo?.email}</p>
      
      <h2>Produtos</h2>
      <div className="products-grid">
        {products.map((product) => (
          <div key={product.uuid} className="product-card">
            <h3>{product.name}</h3>
            <p>{product.description}</p>
            <p>R$ {product.price}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

## Hook Personalizado

```javascript
// hooks/useStore.js
import { useState, useEffect } from 'react';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://orca-app-7hejo.ondigitalocean.app';

export function useStore(slug) {
  const [data, setData] = useState({
    info: null,
    products: [],
    loading: true,
    error: null,
  });

  useEffect(() => {
    async function loadStore() {
      try {
        const [infoRes, productsRes] = await Promise.all([
          fetch(`${API_URL}/store/${slug}/info`),
          fetch(`${API_URL}/store/${slug}/products`),
        ]);

        const [infoData, productsData] = await Promise.all([
          infoRes.json(),
          productsRes.json(),
        ]);

        setData({
          info: infoData.success ? infoData.data : null,
          products: productsData.success ? productsData.data : [],
          loading: false,
          error: null,
        });
      } catch (error) {
        setData({
          info: null,
          products: [],
          loading: false,
          error: error.message,
        });
      }
    }

    if (slug) {
      loadStore();
    }
  }, [slug]);

  return data;
}

// Uso:
function MyComponent() {
  const { info, products, loading, error } = useStore('empresa-oi');
  
  if (loading) return <div>Carregando...</div>;
  if (error) return <div>Erro: {error}</div>;
  
  return (
    <div>
      <h1>{info.name}</h1>
      {/* ... */}
    </div>
  );
}
```

---

## Usando Axios (Alternativa)

```javascript
// lib/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'https://orca-app-7hejo.ondigitalocean.app',
  timeout: 10000,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

// Interceptor para tratamento de erros
api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('API Error:', error.response?.data || error.message);
    return Promise.reject(error);
  }
);

export default api;

// services/storeService.js
import api from '@/lib/api';

export const storeService = {
  async getInfo(slug) {
    const response = await api.get(`/store/${slug}/info`);
    return response.data;
  },

  async getProducts(slug) {
    const response = await api.get(`/store/${slug}/products`);
    return response.data;
  },

  async createOrder(slug, orderData) {
    const response = await api.post(`/store/${slug}/orders`, orderData);
    return response.data;
  },
};

// Uso:
const storeInfo = await storeService.getInfo('empresa-oi');
const products = await storeService.getProducts('empresa-oi');
```

---

## Configuração no Digital Ocean (Frontend)

Para adicionar a variável de ambiente no painel do Digital Ocean:

1. Acesse: https://cloud.digitalocean.com/apps
2. Clique no app `clownfish-app-rr5rv`
3. Vá em **Settings** → **App-Level Environment Variables**
4. Clique em **"Edit"**
5. Adicione:
   ```
   Key: NEXT_PUBLIC_API_URL
   Value: https://orca-app-7hejo.ondigitalocean.app
   Encrypt: No
   ```
6. Clique em **"Save"**
7. Redeploy o app

---

## Teste Rápido no Console do Navegador

```javascript
// Teste direto no console do navegador
fetch('https://orca-app-7hejo.ondigitalocean.app/store/empresa-oi/info')
  .then(res => res.json())
  .then(data => console.log(data));

// Deve retornar:
// { success: true, data: { id: 3, name: "Empresa oi", ... } }
```

