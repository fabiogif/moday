/**
 * Hook para migração de planos
 */

import { useState, useCallback } from 'react'
import { apiClient, endpoints } from '@/lib/api-client'

export interface PlanMigrationHistory {
  id: number
  from_plan: string
  to_plan: string
  status: string
  migrated_at: string
  notes: string | null
}

export interface MigratePlanParams {
  plan_id: number
  notes?: string
}

export interface UsePlanMigrationState {
  migratePlan: (params: MigratePlanParams) => Promise<{ success: boolean; error?: string }>
  getHistory: () => Promise<PlanMigrationHistory[]>
  isMigrating: boolean
  isLoadingHistory: boolean
  error: string | null
  history: PlanMigrationHistory[]
}

export function usePlanMigration(): UsePlanMigrationState {
  const [isMigrating, setIsMigrating] = useState(false)
  const [isLoadingHistory, setIsLoadingHistory] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [history, setHistory] = useState<PlanMigrationHistory[]>([])

  const migratePlan = useCallback(async (params: MigratePlanParams): Promise<{ success: boolean; error?: string }> => {
    setIsMigrating(true)
    setError(null)

    try {
      const response = await apiClient.post(endpoints.planMigration.migrate, params)

      if (response.success) {
        return { success: true }
      }

      const message = response.message || 'Erro ao migrar plano'
      setError(message)
      return { success: false, error: message }
    } catch (err: any) {
      const message = err?.message || err?.error || 'Erro ao migrar plano'
      setError(message)
      return { success: false, error: message }
    } finally {
      setIsMigrating(false)
    }
  }, [])

  const getHistory = useCallback(async (): Promise<PlanMigrationHistory[]> => {
    setIsLoadingHistory(true)
    setError(null)

    try {
      const response = await apiClient.get<PlanMigrationHistory[]>(endpoints.planMigration.history)

      if (response.success) {
        setHistory(response.data)
        return response.data
      } else {
        setError(response.message || 'Erro ao obter histórico')
        return []
      }
    } catch (err: any) {
      setError(err.message || 'Erro ao obter histórico')
      return []
    } finally {
      setIsLoadingHistory(false)
    }
  }, [])

  return {
    migratePlan,
    getHistory,
    isMigrating,
    isLoadingHistory,
    error,
    history,
  }
}
