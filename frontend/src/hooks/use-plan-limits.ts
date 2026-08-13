/**
 * Hook para verificar limites do plano atual
 */

import { useState, useEffect, useCallback } from 'react'
import { apiClient, endpoints } from '@/lib/api-client'
import { useAuth } from '@/contexts/auth-context'

export interface PlanLimitData {
  has_limit_reached: boolean
  reached_limits: string[]
  current_usage: {
    users: number
    products: number
    orders_this_month: number
  }
  plan_limits: {
    max_users: number | null
    max_products: number | null
    max_orders_per_month: number | null
  }
  plan_name: string
  message: string
  detailed_messages?: string[]
}

export interface CurrentUsage {
  users: number
  products: number
  orders_this_month: number
}

export interface UsePlanLimitsState {
  hasLimitReached: boolean
  reachedLimits: string[]
  currentUsage: CurrentUsage
  planLimits: {
    max_users: number | null
    max_products: number | null
    max_orders_per_month: number | null
  }
  planName: string
  message: string
  detailedMessages: string[]
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
}

export function usePlanLimits(): UsePlanLimitsState {
  const [data, setData] = useState<PlanLimitData | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { token, isAuthenticated, isLoading: authLoading } = useAuth()

  const fetchLimits = useCallback(async () => {
    // Não fazer requisição se não estiver autenticado ou ainda estiver carregando
    if (!isAuthenticated || authLoading || !token) {
      return
    }

    setLoading(true)
    setError(null)

    try {
      // Garantir que o token está carregado antes da requisição
      // Verificar diretamente no localStorage primeiro
      if (typeof window !== 'undefined') {
        const tokenFromStorage = localStorage.getItem('auth-token')
        if (tokenFromStorage) {
          apiClient.setToken(tokenFromStorage)
        } else if (token) {
          apiClient.setToken(token)
        } else {
          apiClient.reloadToken()
        }
      } else if (token) {
        apiClient.setToken(token)
      } else {
        apiClient.reloadToken()
      }
      
      const response = await apiClient.get<PlanLimitData>(endpoints.planLimits.check)

      if (response.success) {
        setData(response.data)
      } else {
        setError(response.message || 'Erro ao verificar limites')
      }
    } catch (err: any) {
      setError(err.message || 'Erro ao verificar limites')
    } finally {
      setLoading(false)
    }
  }, [isAuthenticated, authLoading, token])

  useEffect(() => {
    // Só executar quando estiver autenticado e o token estiver disponível
    if (!authLoading && isAuthenticated && token) {
      fetchLimits()

      // Verificar a cada 5 minutos
      const interval = setInterval(() => {
        fetchLimits()
      }, 5 * 60 * 1000)

      return () => clearInterval(interval)
    }
  }, [fetchLimits, authLoading, isAuthenticated, token])

  return {
    hasLimitReached: data?.has_limit_reached ?? false,
    reachedLimits: data?.reached_limits ?? [],
    currentUsage: data?.current_usage ?? {
      users: 0,
      products: 0,
      orders_this_month: 0,
    },
    planLimits: data?.plan_limits ?? {
      max_users: null,
      max_products: null,
      max_orders_per_month: null,
    },
    planName: data?.plan_name ?? '',
    message: data?.message ?? '',
    detailedMessages: data?.detailed_messages ?? [],
    loading,
    error,
    refetch: fetchLimits,
  }
}

export function useCurrentUsage(): {
  usage: CurrentUsage | null
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
} {
  const [usage, setUsage] = useState<CurrentUsage | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchUsage = useCallback(async () => {
    setLoading(true)
    setError(null)

    try {
      const response = await apiClient.get<CurrentUsage>(endpoints.planLimits.currentUsage)

      if (response.success) {
        setUsage(response.data)
      } else {
        setError(response.message || 'Erro ao obter uso atual')
      }
    } catch (err: any) {
      setError(err.message || 'Erro ao obter uso atual')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchUsage()
  }, [fetchUsage])

  return {
    usage,
    loading,
    error,
    refetch: fetchUsage,
  }
}
