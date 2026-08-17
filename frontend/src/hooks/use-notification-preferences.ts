import { useState, useEffect, useCallback } from 'react'
import { apiClient, endpoints } from '@/lib/api-client'
import { toast } from 'sonner'
import { NotificationPreference } from './use-notifications'

export function useNotificationPreferences() {
  const [preferences, setPreferences] = useState<NotificationPreference[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchPreferences = useCallback(async () => {
    try {
      setLoading(true)
      setError(null)
      const response = await apiClient.get(endpoints.notifications.preferences)
      if (response.data && Array.isArray(response.data)) {
        setPreferences(response.data)
      } else {
        setPreferences([])
      }
    } catch (err: any) {
      setError(err.message || 'Erro ao carregar preferências')
      toast.error('Erro ao carregar preferências de notificação')
    } finally {
      setLoading(false)
    }
  }, [])

  const updatePreferences = useCallback(async (newPreferences: any[]) => {
    try {
      setLoading(true)
      const response = await apiClient.put(endpoints.notifications.updatePreferences, {
        preferences: newPreferences
      })
      toast.success(response.message || 'Preferências atualizadas com sucesso')
      await fetchPreferences()
      return true
    } catch (err: any) {
      toast.error(err.message || 'Erro ao atualizar preferências')
      return false
    } finally {
      setLoading(false)
    }
  }, [fetchPreferences])

  const getPreferenceForEvent = useCallback((eventType: string): NotificationPreference | null => {
    return preferences.find(p => p.event_type === eventType) || null
  }, [preferences])

  useEffect(() => {
    fetchPreferences()
  }, [fetchPreferences])

  return {
    preferences,
    loading,
    error,
    updatePreferences,
    getPreferenceForEvent,
    refetch: fetchPreferences,
  }
}

