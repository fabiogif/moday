/**
 * Hook para fazer requisições autenticadas
 * Verifica se o usuário está autenticado antes de fazer a requisição
 */

import { useState, useEffect, useCallback } from 'react'
import { useAuthStore } from '@/contexts/auth-context'
import { apiClient, endpoints } from '@/lib/api-client'

interface UseAuthenticatedApiState<T> {
  data: T | null
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
  isAuthenticated: boolean
}

export function useAuthenticatedApi<T>(
  endpoint: string,
  options: { immediate?: boolean } = {}
): UseAuthenticatedApiState<T> {
  const { immediate = true } = options
  const { token, isAuthenticated } = useAuthStore()
  
  const [data, setData] = useState<T | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchData = useCallback(async () => {
    if (!isAuthenticated || !token) {
      setError('Usuário não autenticado')
      return
    }

    // Garantir que o token está no ApiClient
    apiClient.setToken(token)

    setLoading(true)
    setError(null)

    try {
      console.log('AuthenticatedApi: Fazendo requisição para:', endpoint)
      
      const response = await apiClient.get<T>(endpoint)
      
      console.log('AuthenticatedApi: Resposta recebida:', response)
      
      if (response.success) {
        // Verificar diferentes estruturas de resposta
        let extractedData = response.data
        
        // 1. Se response.data é um array, usar diretamente
        if (Array.isArray(response.data)) {
          console.log('AuthenticatedApi: Array direto detectado')
          extractedData = response.data
        }
        // 2. Se response.data tem uma propriedade data (Laravel Resource Collection)
        else if (response.data && typeof response.data === 'object' && 'data' in response.data) {
          console.log('AuthenticatedApi: Laravel Resource Collection detectada')
          extractedData = (response.data as any).data
        }
        // 3. Se response.data é um objeto simples 
        else if (response.data && typeof response.data === 'object') {
          console.log('AuthenticatedApi: Objeto simples detectado')
          extractedData = response.data
        }
        
        console.log('AuthenticatedApi: Dados extraídos:', extractedData)
        setData(extractedData as T)
      } else {
        console.error('AuthenticatedApi: Resposta não foi bem-sucedida:', response)
        setError(response.message || 'Erro ao carregar dados')
      }
    } catch (err: any) {
      console.error('AuthenticatedApi: Erro na requisição:', err)
      
      // Tentar extrair mais informações do erro
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
  }, [endpoint, isAuthenticated, token])

  useEffect(() => {
    if (immediate && isAuthenticated && token) {
      fetchData()
    }
  }, [immediate, isAuthenticated, token, fetchData])

  return { 
    data, 
    loading, 
    error, 
    refetch: fetchData,
    isAuthenticated 
  }
}

// Hooks específicos para endpoints autenticados
export function useAuthenticatedProducts() {
  return useAuthenticatedApi(endpoints.products.list, { immediate: true })
}

export function useAuthenticatedProductStats() {
  return useAuthenticatedApi(endpoints.products.stats, { immediate: true })
}

export function useAuthenticatedCategories() {
  return useAuthenticatedApi(endpoints.categories.list, { immediate: true })
}

export function useAuthenticatedCategoryStats() {
  return useAuthenticatedApi(endpoints.categories.stats, { immediate: true })
}

export function useAuthenticatedOrders(params?: { page?: number; per_page?: number; status?: string }) {
  const queryString = params ? `?${new URLSearchParams(
    Object.entries(params).filter(([_, v]) => v !== undefined) as [string, string][]
  ).toString()}` : ''
  
  return useAuthenticatedApi(`${endpoints.orders.list}${queryString}`, { immediate: true })
}

export function useAuthenticatedTables() {
  return useAuthenticatedApi(endpoints.tables.list, { immediate: true })
}

export function useAuthenticatedTableStats() {
  return useAuthenticatedApi(endpoints.tables.stats, { immediate: true })
}

export function useAuthenticatedUsers() {
  return useAuthenticatedApi(endpoints.users.list, { immediate: true })
}

export function useAuthenticatedPermissions() {
  return useAuthenticatedApi(endpoints.permissions.list, { immediate: true })
}

export function useAuthenticatedRoles() {
  return useAuthenticatedApi(endpoints.roles.list, { immediate: true })
}

export function useAuthenticatedProfiles() {
  return useAuthenticatedApi(endpoints.profiles.list, { immediate: true })
}

export function useAuthenticatedClients() {
  return useAuthenticatedApi(endpoints.clients.list, { immediate: true })
}

export function useAuthenticatedClientStats() {
  return useAuthenticatedApi(endpoints.clients.stats, { immediate: true })
}

// Hook para operações de mutação (POST, PUT, DELETE)
export function useMutation<T, P = any>() {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { token, isAuthenticated } = useAuthStore()

  const mutate = useCallback(async (
    endpoint: string,
    method: 'POST' | 'PUT' | 'DELETE',
    data?: P
  ): Promise<T | null> => {
    if (!isAuthenticated || !token) {
      setError('Usuário não autenticado')
      return null
    }

    // Garantir que o token está no ApiClient
    apiClient.setToken(token)

    setLoading(true)
    setError(null)

    try {
      console.log('AuthenticatedMutation: Fazendo requisição para:', endpoint)
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
      console.error('AuthenticatedMutation: Erro na requisição:', err)
      
      // Se o erro tem dados de validação, mostrá-los de forma mais clara
      if (err.data && err.data.data) {
        console.error('AuthenticatedMutation: Erros de validação:', err.data.data)
        const validationErrors = Object.entries(err.data.data)
          .map(([field, errors]) => `${field}: ${Array.isArray(errors) ? errors.join(', ') : errors}`)
          .join('; ')
        setError(`Erro de validação: ${validationErrors}`)
      } else {
        setError(err.message || 'Erro na requisição')
      }
      return null
    } finally {
      setLoading(false)
    }
  }, [isAuthenticated, token])

  return { mutate, loading, error }
}

// Hook para operações de mutação com tratamento de erros de validação
export function useMutationWithValidation<T, P = any>(
  setFormError: any,
  fieldMapping?: Record<string, string>
) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { token, isAuthenticated } = useAuthStore()

  const mutate = useCallback(async (
    endpoint: string,
    method: 'POST' | 'PUT' | 'DELETE',
    data?: P
  ): Promise<T | null> => {
    if (!isAuthenticated || !token) {
      setError('Usuário não autenticado')
      return null
    }

    // Garantir que o token está no ApiClient
    apiClient.setToken(token)

    setLoading(true)
    setError(null)

    try {
      console.log('AuthenticatedMutation: Fazendo requisição para:', endpoint)
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
      console.error('AuthenticatedMutation: Erro na requisição:', err)
      
      // Tratar erros de validação do backend
      if (err.data && err.data.data) {
        console.error('AuthenticatedMutation: Erros de validação:', err.data.data)
        
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