# Exemplos Práticos - Rate Limiting e CSRF

## 🎯 Exemplos de Uso no Frontend

### 1. Axios com Interceptor para CSRF

```typescript
// api/client.ts
import axios from 'axios';

const apiClient = axios.create({
  baseURL: 'http://localhost:8000/api',
  withCredentials: true, // Importante para CSRF
});

// Interceptor para adicionar CSRF token automaticamente
apiClient.interceptors.request.use(
  async (config) => {
    // Adicionar CSRF token em requisições que modificam dados
    if (['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase() || '')) {
      let csrfToken = sessionStorage.getItem('csrf_token');
      
      // Se não houver token ou estiver expirado, obter um novo
      if (!csrfToken) {
        const response = await axios.get('http://localhost:8000/api/csrf-token');
        csrfToken = response.data.csrf_token;
        sessionStorage.setItem('csrf_token', csrfToken);
      }
      
      config.headers['X-CSRF-TOKEN'] = csrfToken;
    }
    
    // Adicionar JWT token se houver
    const jwtToken = localStorage.getItem('jwt_token');
    if (jwtToken) {
      config.headers['Authorization'] = `Bearer ${jwtToken}`;
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para tratar erros de rate limiting
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 429) {
      const retryAfter = error.response.headers['retry-after'] || 60;
      console.error(`Rate limit atingido. Tente novamente em ${retryAfter} segundos.`);
      // Você pode mostrar um toast/notificação aqui
    }
    
    if (error.response?.status === 419) {
      console.error('Token CSRF inválido. Renovando...');
      sessionStorage.removeItem('csrf_token');
      // Retry automático pode ser implementado aqui
    }
    
    return Promise.reject(error);
  }
);

export default apiClient;
```

### 2. React Hook para Rate Limiting

```typescript
// hooks/useRateLimitedRequest.ts
import { useState, useCallback } from 'react';
import apiClient from '../api/client';

interface RateLimitState {
  isLimited: boolean;
  retryAfter: number;
}

export const useRateLimitedRequest = () => {
  const [rateLimit, setRateLimit] = useState<RateLimitState>({
    isLimited: false,
    retryAfter: 0,
  });

  const makeRequest = useCallback(async (
    method: 'get' | 'post' | 'put' | 'delete',
    url: string,
    data?: any
  ) => {
    try {
      const response = await apiClient[method](url, data);
      
      // Resetar rate limit se sucesso
      if (rateLimit.isLimited) {
        setRateLimit({ isLimited: false, retryAfter: 0 });
      }
      
      return response.data;
    } catch (error: any) {
      if (error.response?.status === 429) {
        const retryAfter = parseInt(error.response.headers['retry-after'] || '60');
        setRateLimit({ isLimited: true, retryAfter });
        
        throw new Error(`Muitas requisições. Tente novamente em ${retryAfter} segundos.`);
      }
      throw error;
    }
  }, [rateLimit.isLimited]);

  return { makeRequest, rateLimit };
};
```

### 3. Componente de Login com Rate Limiting

```typescript
// components/LoginForm.tsx
import React, { useState } from 'react';
import { useRateLimitedRequest } from '../hooks/useRateLimitedRequest';

export const LoginForm: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const { makeRequest, rateLimit } = useRateLimitedRequest();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    try {
      const data = await makeRequest('post', '/auth/login', { email, password });
      localStorage.setItem('jwt_token', data.token);
      // Redirecionar para dashboard
    } catch (err: any) {
      setError(err.message || 'Erro ao fazer login');
    }
  };

  return (
    <form onSubmit={handleLogin}>
      {rateLimit.isLimited && (
        <div className="alert alert-warning">
          Muitas tentativas. Aguarde {rateLimit.retryAfter} segundos.
        </div>
      )}
      
      {error && <div className="alert alert-danger">{error}</div>}
      
      <input
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder="Email"
        disabled={rateLimit.isLimited}
      />
      
      <input
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        placeholder="Senha"
        disabled={rateLimit.isLimited}
      />
      
      <button type="submit" disabled={rateLimit.isLimited}>
        {rateLimit.isLimited ? `Aguarde ${rateLimit.retryAfter}s` : 'Entrar'}
      </button>
    </form>
  );
};
```

## 🧪 Testes de Rate Limiting

### Teste Automático com Jest

```typescript
// tests/rateLimit.test.ts
import apiClient from '../api/client';

describe('Rate Limiting', () => {
  it('deve bloquear após 5 tentativas de login', async () => {
    const promises = [];
    
    // Fazer 6 requisições de login
    for (let i = 0; i < 6; i++) {
      promises.push(
        apiClient.post('/auth/login', {
          email: 'test@test.com',
          password: 'wrongpassword'
        }).catch(err => err.response)
      );
    }
    
    const responses = await Promise.all(promises);
    
    // Pelo menos uma das últimas requisições deve ser 429
    const rateLimited = responses.some(r => r?.status === 429);
    expect(rateLimited).toBe(true);
  });

  it('deve incluir headers de rate limit', async () => {
    const response = await apiClient.get('/health');
    
    expect(response.headers).toHaveProperty('x-ratelimit-limit');
    expect(response.headers).toHaveProperty('x-ratelimit-remaining');
  });
});
```

### Teste Manual com cURL

```bash
#!/bin/bash
# test-manual-rate-limit.sh

echo "=== Testando Rate Limiting de Login (5 req/min) ==="
echo ""

for i in {1..7}; do
  echo -n "Tentativa $i: "
  
  response=$(curl -s -w "\n%{http_code}" -X POST http://localhost:8000/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@test.com","password":"wrong"}')
  
  status=$(echo "$response" | tail -n1)
  
  if [ "$status" == "429" ]; then
    echo "❌ BLOQUEADO (429 - Rate Limited)"
  elif [ "$status" == "401" ]; then
    echo "✓ Processado (401 - Unauthorized)"
  else
    echo "⚠ Outro status: $status"
  fi
  
  sleep 0.5
done

echo ""
echo "=== Testando Headers de Rate Limit ==="
curl -i http://localhost:8000/api/health 2>&1 | grep -i "x-ratelimit"
```

## 📊 Monitoramento de Rate Limiting

### Dashboard de Monitoramento (React)

```typescript
// components/RateLimitDashboard.tsx
import React, { useEffect, useState } from 'react';
import apiClient from '../api/client';

interface RateLimitInfo {
  endpoint: string;
  limit: number;
  remaining: number;
  resetAt: Date;
}

export const RateLimitDashboard: React.FC = () => {
  const [limits, setLimits] = useState<RateLimitInfo[]>([]);

  useEffect(() => {
    const checkLimits = async () => {
      const endpoints = ['/product', '/order', '/dashboard'];
      const limitInfo: RateLimitInfo[] = [];

      for (const endpoint of endpoints) {
        try {
          const response = await apiClient.get(endpoint);
          
          limitInfo.push({
            endpoint,
            limit: parseInt(response.headers['x-ratelimit-limit'] || '0'),
            remaining: parseInt(response.headers['x-ratelimit-remaining'] || '0'),
            resetAt: new Date(Date.now() + 60000), // Aproximado
          });
        } catch (error) {
          console.error(`Erro ao verificar ${endpoint}:`, error);
        }
      }

      setLimits(limitInfo);
    };

    checkLimits();
    const interval = setInterval(checkLimits, 30000); // A cada 30s

    return () => clearInterval(interval);
  }, []);

  return (
    <div className="rate-limit-dashboard">
      <h3>Status de Rate Limiting</h3>
      <table>
        <thead>
          <tr>
            <th>Endpoint</th>
            <th>Limite</th>
            <th>Restantes</th>
            <th>Uso (%)</th>
          </tr>
        </thead>
        <tbody>
          {limits.map((limit) => {
            const usage = ((limit.limit - limit.remaining) / limit.limit) * 100;
            return (
              <tr key={limit.endpoint}>
                <td>{limit.endpoint}</td>
                <td>{limit.limit}</td>
                <td>{limit.remaining}</td>
                <td>
                  <div className="progress">
                    <div 
                      className={`progress-bar ${usage > 80 ? 'bg-danger' : usage > 50 ? 'bg-warning' : 'bg-success'}`}
                      style={{ width: `${usage}%` }}
                    >
                      {usage.toFixed(0)}%
                    </div>
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
};
```

## 🔧 Utilitários

### Helper para Retry com Backoff

```typescript
// utils/retryWithBackoff.ts
export async function retryWithBackoff<T>(
  fn: () => Promise<T>,
  maxRetries = 3,
  baseDelay = 1000
): Promise<T> {
  let lastError: any;
  
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error: any) {
      lastError = error;
      
      // Não fazer retry se não for rate limit
      if (error.response?.status !== 429) {
        throw error;
      }
      
      // Pegar tempo de retry do header ou calcular backoff exponencial
      const retryAfter = error.response?.headers['retry-after'];
      const delay = retryAfter 
        ? parseInt(retryAfter) * 1000 
        : baseDelay * Math.pow(2, i);
      
      console.log(`Rate limited. Retrying in ${delay}ms...`);
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
  
  throw lastError;
}

// Uso
const data = await retryWithBackoff(() => 
  apiClient.post('/product', productData)
);
```

## 📱 Exemplo Completo - App de E-commerce

```typescript
// services/ProductService.ts
import apiClient from '../api/client';
import { retryWithBackoff } from '../utils/retryWithBackoff';

export class ProductService {
  // Listagem com rate limiting de leitura (100/min)
  static async getAll(page = 1) {
    const response = await apiClient.get('/product', { params: { page } });
    return response.data;
  }

  // Criação com rate limiting crítico (30/min) e retry
  static async create(productData: any) {
    return retryWithBackoff(() => 
      apiClient.post('/product', productData)
    );
  }

  // Atualização com rate limiting crítico (30/min)
  static async update(id: string, productData: any) {
    const response = await apiClient.put(`/product/${id}`, productData);
    return response.data;
  }

  // Exclusão com rate limiting crítico (30/min)
  static async delete(id: string) {
    const response = await apiClient.delete(`/product/${id}`);
    return response.data;
  }
}
```

---

**Nota:** Todos os exemplos acima são compatíveis com a implementação de rate limiting e CSRF do Laravel 11.
