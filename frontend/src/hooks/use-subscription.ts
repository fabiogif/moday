"use client"

import { useState, useCallback } from "react"
import { apiClient, endpoints } from "@/lib/api-client"
import type {
  SubscriptionStatus2,
  SubscriptionInvoice,
  UpgradeRequest,
  DowngradeRequest,
  ReactivateRequest,
  UpdateCardRequest,
} from "@/types/subscription"

export function useSubscription() {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const clearError = () => setError(null)

  const upgrade = useCallback(async (planId: number): Promise<boolean> => {
    setLoading(true)
    setError(null)
    try {
      await apiClient.post("/api/subscription/upgrade", { plan_id: planId } satisfies UpgradeRequest)
      return true
    } catch (err: any) {
      setError(err?.message ?? "Falha ao realizar upgrade.")
      return false
    } finally {
      setLoading(false)
    }
  }, [])

  const scheduleDowngrade = useCallback(async (planId: number): Promise<boolean> => {
    setLoading(true)
    setError(null)
    try {
      await apiClient.post("/api/subscription/downgrade", { plan_id: planId } satisfies DowngradeRequest)
      return true
    } catch (err: any) {
      setError(err?.message ?? "Falha ao agendar downgrade.")
      return false
    } finally {
      setLoading(false)
    }
  }, [])

  const cancelDowngrade = useCallback(async (): Promise<boolean> => {
    setLoading(true)
    setError(null)
    try {
      await apiClient.delete("/api/subscription/downgrade")
      return true
    } catch (err: any) {
      setError(err?.message ?? "Falha ao cancelar downgrade.")
      return false
    } finally {
      setLoading(false)
    }
  }, [])

  const cancelSubscription = useCallback(async (): Promise<boolean> => {
    setLoading(true)
    setError(null)
    try {
      await apiClient.post("/api/subscription/cancel", {})
      return true
    } catch (err: any) {
      setError(err?.message ?? "Falha ao cancelar assinatura.")
      return false
    } finally {
      setLoading(false)
    }
  }, [])

  const reactivate = useCallback(async (data: ReactivateRequest): Promise<boolean> => {
    setLoading(true)
    setError(null)
    try {
      await apiClient.post("/api/subscription/reactivate", data)
      return true
    } catch (err: any) {
      setError(err?.message ?? "Falha ao reativar assinatura.")
      return false
    } finally {
      setLoading(false)
    }
  }, [])

  const updateCard = useCallback(async (cardToken: string): Promise<boolean> => {
    setLoading(true)
    setError(null)
    try {
      await apiClient.put("/api/subscription/card", { card_token: cardToken } satisfies UpdateCardRequest)
      return true
    } catch (err: any) {
      setError(err?.message ?? "Falha ao atualizar cartão.")
      return false
    } finally {
      setLoading(false)
    }
  }, [])

  const fetchInvoices = useCallback(async (): Promise<SubscriptionInvoice[]> => {
    setLoading(true)
    setError(null)
    try {
      const res = await apiClient.get<SubscriptionInvoice[]>(endpoints.subscription.invoices)
      return res?.data ?? []
    } catch (err: any) {
      // 404/rota ausente: não quebra a UI — lista vazia
      if (err?.status === 404) {
        return []
      }
      setError(err?.message ?? "Falha ao buscar faturas.")
      return []
    } finally {
      setLoading(false)
    }
  }, [])

  return {
    loading,
    error,
    clearError,
    upgrade,
    scheduleDowngrade,
    cancelDowngrade,
    cancelSubscription,
    reactivate,
    updateCard,
    fetchInvoices,
  }
}
