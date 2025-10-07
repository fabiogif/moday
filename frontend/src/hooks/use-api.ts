/**
 * Hooks customizados para integração com a API
 * Inclui cache, loading states e tratamento de erros
 */

import { useState, useEffect, useCallback } from 'react'
import { apiClient, endpoints } from '@/lib/api-client'

interface UseApiState<T> {
  data: T | null
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
}

interface UseApiOptions {
  immediate?: boolean
  cache?: boolean
  cacheKey?: string
}

// Cache simples em memória
const cache = new Map<string, { data: any; timestamp: number }>()
const CACHE_DURATION = 5 * 60 * 1000 // 5 minutos

function getCachedData<T>(key: string): T | null {
  const cached = cache.get(key)
  if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
    return cached.data
  }
  return null
}

function setCachedData<T>(key: string, data: T): void {
  cache.set(key, { data, timestamp: Date.now() })
}

export function useApi<T>(
  endpoint: string,
  options: UseApiOptions = {}
): UseApiState<T> {
  const { immediate = true, cache: useCache = true, cacheKey } = options
  
  const [data, setData] = useState<T | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchData = useCallback(async () => {
    const key = cacheKey || endpoint
    
    // Verificar cache primeiro
    if (useCache) {
      const cachedData = getCachedData<T>(key)
      if (cachedData) {
        setData(cachedData)
        return
      }
    }

    setLoading(true)
    setError(null)

    try {
      const response = await apiClient.get<T>(endpoint)
      
      if (response.success) {
        setData(response.data)
        
        // Salvar no cache
        if (useCache) {
          setCachedData(key, response.data)
        }
      } else {
        setError(response.message || 'Erro ao carregar dados')
      }
    } catch (err: any) {
      setError(err.message || 'Erro na requisição')
    } finally {
      setLoading(false)
    }
  }, [endpoint, useCache, cacheKey])

  const refetch = useCallback(async () => {
    // Limpar cache se existir
    if (useCache && cacheKey) {
      cache.delete(cacheKey)
    }
    await fetchData()
  }, [fetchData, useCache, cacheKey])

  useEffect(() => {
    if (immediate) {
      fetchData()
    }
  }, [immediate, fetchData])

  return { data, loading, error, refetch }
}

// Hook específico para produtos
export function useProducts() {
  return useApi(endpoints.products.list, {
    cacheKey: 'products',
    immediate: true
  })
}

// Hook específico para categorias
export function useCategories() {
  return useApi(endpoints.categories.list, {
    cacheKey: 'categories',
    immediate: true
  })
}

// Hook específico para pedidos
export function useOrders(params?: { page?: number; per_page?: number; status?: string }) {
  const queryString = params ? `?${new URLSearchParams(
    Object.entries(params).filter(([_, v]) => v !== undefined) as [string, string][]
  ).toString()}` : ''
  
  return useApi(`${endpoints.orders.list}${queryString}`, {
    cacheKey: `orders-${JSON.stringify(params)}`,
    immediate: true
  })
}

// Hook específico para mesas
export function useTables() {
  return useApi(endpoints.tables.list, {
    cacheKey: 'tables',
    immediate: true
  })
}

// Hook específico para usuários
export function useUsers() {
  return useApi(endpoints.users.list, {
    cacheKey: 'users',
    immediate: true
  })
}

// Hook específico para clientes
export function useClients() {
  return useApi(endpoints.clients.list, {
    cacheKey: 'clients',
    immediate: true
  })
}

// Hook para operações de mutação (POST, PUT, DELETE)
export function useMutation<T, P = any>() {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const mutate = useCallback(async (
    endpoint: string,
    method: 'POST' | 'PUT' | 'DELETE',
    data?: P
  ): Promise<T | null> => {
    setLoading(true)
    setError(null)

    try {
      let response
      switch (method) {
        case 'POST':
          response = await apiClient.post<T>(endpoint, data)
          break
        case 'PUT':
          response = await apiClient.put<T>(endpoint, data)
          break
        case 'DELETE':
          response = await apiClient.delete<T>(endpoint)
          break
      }

      if (response.success) {
        return response.data
      } else {
        setError(response.message || 'Erro na operação')
        return null
      }
    } catch (err: any) {
      setError(err.message || 'Erro na requisição')
      return null
    } finally {
      setLoading(false)
    }
  }, [])

  return { mutate, loading, error }
}

// Hook para autenticação
export function useAuth() {
  const [user, setUser] = useState<any>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const checkAuth = async () => {
      try {
        const token = localStorage.getItem('auth-token')
        if (token) {
          apiClient.setToken(token)
      const response = await apiClient.get('/api/auth/me')
      if (response.success) {
        setUser(response.data as any)
      }
        }
      } catch (error) {
        console.error('Erro ao verificar autenticação:', error)
        apiClient.clearToken()
      } finally {
        setLoading(false)
      }
    }

    checkAuth()
  }, [])

  const login = async (email: string, password: string) => {
    try {
      const response = await apiClient.post('/api/auth/login', { email, password })
      if (response.success) {
        const data = response.data as any
        apiClient.setToken(data.token)
        setUser(data.user)
        return { success: true }
      }
      return { success: false, message: response.message }
    } catch (error: any) {
      return { success: false, message: error.message }
    }
  }

  const logout = () => {
    apiClient.clearToken()
    setUser(null)
  }

  return { user, loading, login, logout }
}

