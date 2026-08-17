/**
 * Hook para buscar dados de desempenho/vendas
 */

import { useState } from 'react'
import { useAuthenticatedApi } from './use-authenticated-api'
import { useMutation } from './use-authenticated-api'
import { endpoints, apiClient } from '@/lib/api-client'

export interface SalesPerformanceData {
  period: {
    start_date: string
    end_date: string
    days: number
  }
  indicators: {
    total_sales: {
      current: number
      previous: number
      growth: number
    }
    total_sales_value: {
      current: number
      previous: number
      growth: number
    }
    average_ticket: {
      current: number
      previous: number
      growth: number
    }
    new_clients: {
      current: number
      previous: number
      growth: number
    }
  }
  sales_by_hour: Array<{
    hour: number
    count: number
    hour_label: string
  }>
  best_hour: {
    hour: number
    count: number
    hour_label: string
  } | null
  sales_by_day: Array<{
    day_of_week: number
    day_name: string
    count: number
  }>
  best_day: {
    day_of_week: number
    day_name: string
    count: number
  } | null
  sales_by_payment_method: Array<{
    payment_method: string
    count: number
    total_value: number
  }>
}

export interface SalesPerformanceParams {
  start_date?: string
  end_date?: string
  days?: number
}

export function useSalesPerformance(params?: SalesPerformanceParams) {
  const endpoint = endpoints.salesPerformance.list(params)
  
  return useAuthenticatedApi<SalesPerformanceData>(endpoint, {
    immediate: true,
    queryParams: params
  })
}

export function useExportSalesPerformance() {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const exportData = async (params?: SalesPerformanceParams) => {
    setLoading(true)
    setError(null)
    
    try {
      const endpoint = endpoints.salesPerformance.export(params)
      const response = await apiClient.get<SalesPerformanceData & { exported_at: string; tenant_id: number }>(endpoint)
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Erro ao exportar dados')
      }
    } catch (err: any) {
      setError(err.message || 'Erro ao exportar dados')
      throw err
    } finally {
      setLoading(false)
    }
  }

  return { exportData, loading, error }
}

export function useRefreshSalesPerformance() {
  const { mutate, loading, error } = useMutation<void>()

  const refresh = async () => {
    return await mutate(endpoints.salesPerformance.refresh, 'POST')
  }

  return { refresh, loading, error }
}

