/**
 * Hook para fazer requisiçÃµes autenticadas
 * Verifica se o usuário está autenticado antes de fazer a requisição
 */

import { useState, useEffect, useCallback, useMemo } from 'react'
import { useAuth } from '@/contexts/auth-context'
import { apiClient, endpoints } from '@/lib/api-client'

interface UseAuthenticatedApiState<T> {
  data: T | null
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
  isAuthenticated: boolean
  pagination?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// ---------------------------------------------------------------------------
// Module-level TTL cache — keyed by "endpoint::queryParamsJSON"
// Survives re-renders and component remounts within the same browser session.
// ---------------------------------------------------------------------------
const REQUEST_CACHE = new Map<string, { data: unknown; timestamp: number; ttl: number }>()

function getCachedData(key: string): unknown | null {
  const entry = REQUEST_CACHE.get(key)
  if (!entry) return null
  if (Date.now() - entry.timestamp > entry.ttl) {
    REQUEST_CACHE.delete(key)
    return null
  }
  return entry.data
}

function setCachedData(key: string, data: unknown, ttl: number): void {
  REQUEST_CACHE.set(key, { data, timestamp: Date.now(), ttl })
}

/**
 * Invalidate cache entries whose key contains `pattern`.
 * Call with no argument to clear the entire cache.
 */
export function invalidateCache(pattern?: string): void {
  if (!pattern) {
    REQUEST_CACHE.clear()
    return
  }
  for (const key of REQUEST_CACHE.keys()) {
    if (key.includes(pattern)) REQUEST_CACHE.delete(key)
  }
}
// ---------------------------------------------------------------------------

export function useAuthenticatedApi<T>(
  endpoint: string,
  options: { immediate?: boolean; queryParams?: Record<string, any>; ttl?: number } = {}
): UseAuthenticatedApiState<T> {
  const { immediate = true, queryParams = {}, ttl = 30_000 } = options
  const { token, isAuthenticated, isLoading: authLoading } = useAuth()

  const [data, setData] = useState<T | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [pagination, setPagination] = useState<{
    current_page: number
    last_page: number
    per_page: number
    total: number
  } | null>(null)

  // Serializar queryParams para evitar loop infinito no useCallback
  const queryParamsKey = useMemo(() => JSON.stringify(queryParams), [queryParams])

  const fetchData = useCallback(async () => {
    // Aguardar o AuthContext terminar de carregar antes de verificar autenticação
    if (authLoading) {
      return
    }

    if (!isAuthenticated || !token) {
      setError('Usuário não autenticado')
      return
    }

    // Serve from cache if the data is still fresh — no network request needed
    const cacheKey = `${endpoint}::${queryParamsKey}`
    const cached = getCachedData(cacheKey)
    if (cached !== null) {
      setData(cached as T)
      return
    }

    // Garantir que o token está no ApiClient
    if (typeof window !== 'undefined') {
      const tokenFromStorage = localStorage.getItem('auth-token')
      apiClient.setToken(tokenFromStorage || token)
    } else {
      apiClient.setToken(token)
    }

    setLoading(true)
    setError(null)

    try {
      const currentQueryParams = queryParamsKey ? JSON.parse(queryParamsKey) : {}

      let finalEndpoint = endpoint

      if (Object.keys(currentQueryParams).length > 0) {
        const url = new URL(endpoint, window.location.origin)
        Object.entries(currentQueryParams).forEach(([key, value]) => {
          if (value !== undefined && value !== null) {
            url.searchParams.append(key, value.toString())
          }
        })
        finalEndpoint = url.pathname + url.search
      }

      const response = await apiClient.get<T>(finalEndpoint)

      if (response.success) {
        let extractedData = response.data
        let paginationData = null

        if (Array.isArray(response.data)) {
          extractedData = response.data
        } else if (response.data && typeof response.data === 'object' && 'pagination' in response.data) {
          paginationData = (response.data as any).pagination
          extractedData = response.data
        } else if (response.data && typeof response.data === 'object' && 'data' in response.data) {
          extractedData = (response.data as any).data
          if ('pagination' in response.data) {
            paginationData = (response.data as any).pagination
          }
        } else if (response.data && typeof response.data === 'object') {
          extractedData = response.data
        }

        // Cache the result and update state
        setCachedData(cacheKey, extractedData, ttl)
        setData(extractedData as T)
        if (paginationData) {
          setPagination(paginationData)
        }
      } else {
        setError(response.message || 'Erro ao carregar dados')
      }
    } catch (err: any) {
      let errorMessage = 'Erro na requisição'
      if (err.message) {
        errorMessage = err.message
      } else if (err.data && err.data.message) {
        errorMessage = err.data.message
      } else if (typeof err === 'string') {
        errorMessage = err
      }

      setError(errorMessage)
    } finally {
      setLoading(false)
    }
  }, [endpoint, isAuthenticated, token, authLoading, queryParamsKey, ttl])

  // refetch always bypasses cache so the caller gets fresh data on demand
  const refetch = useCallback(async () => {
    invalidateCache(`${endpoint}::${queryParamsKey}`)
    await fetchData()
  }, [endpoint, queryParamsKey, fetchData])

  useEffect(() => {
    if (immediate && !authLoading && isAuthenticated && token) {
      fetchData()
    }
  }, [immediate, authLoading, isAuthenticated, token, fetchData])

  const effectiveIsAuthenticated = authLoading ? true : isAuthenticated

  return {
    data,
    loading,
    error,
    refetch,
    isAuthenticated: effectiveIsAuthenticated,
    pagination: pagination || undefined
  }
}

// ---------------------------------------------------------------------------
// Hooks específicos — TTL configurado por frequência de mudança dos dados:
//   60 s — dados estáticos (produtos, categorias, métodos de pagamento)
//   30 s — dados semi-estáticos (mesas, clientes, tipos de atendimento)
//   15 s — dados dinÃ¢micos (pedidos do dia, pedidos por mesa)
// ---------------------------------------------------------------------------
export function useAuthenticatedProducts() {
  return useAuthenticatedApi(endpoints.products.list, { ttl: 60_000 })
}

/** Produtos para PDV e vendas — apenas ativos e com estoque (backend: ProductCatalogVisibilityRule). */
export function useAuthenticatedCatalogProducts() {
  return useAuthenticatedApi(endpoints.products.catalog, { ttl: 60_000 })
}

export function useAuthenticatedPermissions() {
  const result = useAuthenticatedApi(endpoints.permissions.list, { 
    immediate: true
  })
  
  // Extrair permissions do response.data.permissions
  const permissions = result.data && typeof result.data === 'object' && 'permissions' in result.data 
    ? (result.data as any).permissions 
    : result.data
  
  return {
    ...result,
    data: permissions
  }
}

export function useAuthenticatedProductStats() {
  return useAuthenticatedApi(endpoints.products.stats, { immediate: true })
}

export function useAuthenticatedCategories() {
  // A tela usa paginação client-side; buscar um lote maior evita ?sumir? categorias novas.
  return useAuthenticatedApi(endpoints.categories.list, {
    ttl: 60_000,
    queryParams: { per_page: 200 },
  })
}

export function useAuthenticatedCategoryStats() {
  return useAuthenticatedApi(endpoints.categories.stats, { ttl: 60_000 })
}

export function useAuthenticatedOrders(params?: { page?: number; per_page?: number; status?: string }) {
  const queryString = params ? `?${new URLSearchParams(
    Object.entries(params).filter(([_, v]) => v !== undefined) as [string, string][]
  ).toString()}` : ''
  
  return useAuthenticatedApi(`${endpoints.orders.list}${queryString}`, { immediate: true })
}

export function useAuthenticatedOrderStats() {
  return useAuthenticatedApi(endpoints.orders.stats, { immediate: true })
}

export function useAuthenticatedTables() {
  return useAuthenticatedApi(endpoints.tables.list, { ttl: 30_000 })
}

export function useAuthenticatedPlans() {
  return useAuthenticatedApi(endpoints.plans.list, { immediate: true })
}

export function useAuthenticatedServiceTypes() {
  return useAuthenticatedApi(endpoints.serviceTypes.list, { immediate: true })
}

export function useAuthenticatedActiveServiceTypes() {
  return useAuthenticatedApi(endpoints.serviceTypes.active, { ttl: 60_000 })
}

export function useAuthenticatedMenuServiceTypes() {
  return useAuthenticatedApi(endpoints.serviceTypes.menu, { ttl: 60_000 })
}

export function useAuthenticatedTableStats() {
  return useAuthenticatedApi(endpoints.tables.stats, { immediate: true })
}

export function useAuthenticatedProfiles() {
  const result = useAuthenticatedApi(endpoints.profiles.list, { 
    immediate: true
  })
  
  // Extrair profiles do response.data.profiles
  const profiles = result.data && typeof result.data === 'object' && 'profiles' in result.data 
    ? (result.data as any).profiles 
    : result.data
  
  return {
    ...result,
    data: profiles
  }
}

export function useAuthenticatedUsers(page: number = 1, perPage: number = 15) {
  const result = useAuthenticatedApi(endpoints.users.list, { 
    immediate: true, 
    queryParams: { page, per_page: perPage } 
  })
  
  // Extrair users do response.data.users
  const users = result.data && typeof result.data === 'object' && 'users' in result.data 
    ? (result.data as any).users 
    : result.data
  
  return {
    ...result,
    data: users,
    pagination: result.pagination
  }
}

export function useAuthenticatedRoles() {
  return useAuthenticatedApi(endpoints.roles.list, { immediate: true })
}

export function useAuthenticatedClients() {
  return useAuthenticatedApi(endpoints.clients.list, { ttl: 30_000 })
}

export function useAuthenticatedOrdersByTable(tableUuid: string | null) {
  return useAuthenticatedApi(
    tableUuid ? endpoints.orders.getByTable(tableUuid) : '',
    { immediate: !!tableUuid, ttl: 15_000 }
  )
}

export function useAuthenticatedTodayOrders() {
  return useAuthenticatedApi(endpoints.orders.getToday, { ttl: 15_000 })
}

export function useAuthenticatedClientStats() {
  return useAuthenticatedApi(endpoints.clients.stats, { immediate: true })
}

// Reviews
export function useAuthenticatedReviews(status?: string) {
  return useAuthenticatedApi(endpoints.reviews.list(status), { immediate: true })
}

export function useAuthenticatedReviewStats() {
  return useAuthenticatedApi(endpoints.reviews.stats, { immediate: true })
}

export function useAuthenticatedRecentReviews(limit: number = 5) {
  return useAuthenticatedApi(endpoints.reviews.recent(limit), { immediate: true })
}

// Hook para operaçÃµes de mutação (POST, PUT, DELETE)
export function useMutation<T, P = any>() {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { token, isAuthenticated } = useAuth()

  const mutate = useCallback(async (
    endpoint: string,
    method: 'POST' | 'PUT' | 'DELETE' | 'PATCH',
    data?: P
  ): Promise<T | null> => {
    if (!isAuthenticated || !token) {
      setError('Usuário não autenticado')
      throw new Error('Usuário não autenticado')
    }

    // Garantir que o token está no ApiClient e recarregar do localStorage se necessário
    apiClient.setToken(token)
    apiClient.reloadToken() // Forçar recarga do token para garantir sincronização

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
        case 'PATCH':
          response = await apiClient.patch<T>(endpoint, data)
          break
      }

      // Verificar se a resposta existe e é válida
      if (!response) {
        const errorMsg = 'Resposta vazia do servidor'
        setError(errorMsg)
        throw new Error(errorMsg)
      }

      if (response.success) {
        return response.data
      } else {
        const errorMsg = response.message || 'Erro na operação'
        setError(errorMsg)
        throw new Error(errorMsg)
      }
    } catch (err: any) {
      // Log detalhado apenas em desenvolvimento
      if (process.env.NODE_ENV === 'development') {

      }
      
      let errorMessage = 'Erro na requisição'
      
      // Tratar erros de validação (HTTP 422)
      if (err.errors || err.data) {
        // Laravel retorna erros em diferentes formatos
        const validationErrors = err.errors || err.data || {}
        const errorMessages: string[] = []
        
        Object.entries(validationErrors).forEach(([field, messages]) => {
          if (Array.isArray(messages)) {
            messages.forEach(msg => errorMessages.push(msg))
          } else if (typeof messages === 'string') {
            errorMessages.push(messages)
          }
        })
        
        if (errorMessages.length > 0) {
          errorMessage = errorMessages.join('\n')
        } else if (err.message) {
          errorMessage = err.message
        }
      } else if (err.message) {
        errorMessage = err.message
      }
      
      setError(errorMessage)
      throw err  // Lançar erro original ao invés de new Error()
    } finally {
      setLoading(false)
    }
  }, [isAuthenticated, token])

  return { mutate, loading, error }
}

// Hook para operaçÃµes de mutação com tratamento de erros de validação
export function useMutationWithValidation<T, P = any>(
  setFormError: any,
  fieldMapping?: Record<string, string>
) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { token, isAuthenticated } = useAuth()

  const mutate = useCallback(async (
    endpoint: string,
    method: 'POST' | 'PUT' | 'DELETE' | 'PATCH',
    data?: P
  ): Promise<T | null> => {
    if (!isAuthenticated || !token) {
      setError('Usuário não autenticado')
      return null
    }

    // Garantir que o token está no ApiClient e recarregar do localStorage se necessário
    apiClient.setToken(token)
    apiClient.reloadToken() // Forçar recarga do token para garantir sincronização

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
        case 'PATCH':
          response = await apiClient.patch<T>(endpoint, data)
          break
      }

      if (response.success) {
        return response.data
      } else {
        setError(response.message || 'Erro na operação')
        return null
      }
    } catch (err: any) {

      // Tratar erros de validação do backend
      if (err.data && err.data.data) {

        // Mapear erros para campos do formulário
        Object.entries(err.data.data).forEach(([field, messages]) => {
          const fieldName = fieldMapping?.[field] || field
          const errorMessage = Array.isArray(messages) ? messages[0] : messages
          
          setFormError(fieldName, {
            type: 'server',
            message: errorMessage
          })
        })
        
        return null
      } else {
        setError(err.message || 'Erro na requisição')
        return null
      }
    } finally {
      setLoading(false)
    }
  }, [isAuthenticated, token, setFormError])

  return { mutate, loading, error }
}

export function useAuthenticatedPaymentMethods() {
  return useAuthenticatedApi(endpoints.paymentMethods.list, { ttl: 60_000 })
}

export function useAuthenticatedActivePaymentMethods() {
  return useAuthenticatedApi(endpoints.paymentMethods.active, { ttl: 60_000 })
}