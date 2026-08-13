'use client'

import { useState, useEffect, useCallback, useRef } from 'react'
import { useAdminAuth } from '@/contexts/admin-auth-context'
import adminApi from '@/lib/admin-api-client'
import type { Plan } from '@/app/admin/plans/page'

interface AdminApiState<T> {
  data: T | null
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
  isAuthenticated: boolean
  isAuthLoading: boolean
}

export function useAdminApiState<T>(
  fetcher: () => Promise<T>,
  deps: unknown[] = [],
  options: { immediate?: boolean } = {}
): AdminApiState<T> {
  const { immediate = true } = options
  const { isAuthenticated, isLoading: isAuthLoading, token } = useAdminAuth()
  const [data, setData] = useState<T | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const fetcherRef = useRef(fetcher)

  useEffect(() => {
    fetcherRef.current = fetcher
  }, [fetcher])

  const fetchData = useCallback(async () => {
    if (isAuthLoading) return
    if (!isAuthenticated || !token) {
      setError('Administrador não autenticado')
      return
    }

    adminApi.reloadToken()
    setLoading(true)
    setError(null)

    try {
      const result = await fetcherRef.current()
      setData(result)
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Erro ao carregar dados'
      setError(message)
    } finally {
      setLoading(false)
    }
  }, [isAuthLoading, isAuthenticated, token])

  const refetch = useCallback(async () => {
    await fetchData()
  }, [fetchData])

  useEffect(() => {
    if (immediate && !isAuthLoading && isAuthenticated && token) {
      fetchData()
    }
  }, [immediate, isAuthLoading, isAuthenticated, token, fetchData, ...deps])

  return {
    data,
    loading: isAuthLoading || loading,
    error,
    refetch,
    isAuthenticated,
    isAuthLoading,
  }
}

export function useAdminPlans() {
  return useAdminApiState<Plan[]>(
    async () => {
      const response = await adminApi.getPlans({ per_page: 100 })
      if (Array.isArray(response.data)) {
        return response.data as Plan[]
      }
      return []
    },
    [],
    { immediate: true }
  )
}

export function useAdminMutation<T, P = unknown>() {
  const { isAuthenticated, token } = useAdminAuth()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const mutate = useCallback(
    async (action: () => Promise<T>): Promise<T | null> => {
      if (!isAuthenticated || !token) {
        const authError = 'Administrador não autenticado'
        setError(authError)
        throw new Error(authError)
      }

      adminApi.reloadToken()
      setLoading(true)
      setError(null)

      try {
        const response = await action()
        return response
      } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Erro na operação'
        setError(message)
        throw err instanceof Error ? err : new Error(message)
      } finally {
        setLoading(false)
      }
    },
    [isAuthenticated, token]
  )

  return { mutate, loading, error }
}
