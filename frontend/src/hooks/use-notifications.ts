import { useState, useEffect, useCallback } from 'react'
import { apiClient, endpoints } from '@/lib/api-client'
import { toast } from 'sonner'

export interface Notification {
  id: number
  uuid: string
  type: string
  channel: string
  title: string
  message: string
  data: any
  read_at: string | null
  sent_at: string | null
  is_read: boolean
  is_success: boolean
  created_at: string
  created_at_human: string
}

export interface NotificationPreference {
  id: number
  uuid: string
  event_type: string
  email_enabled: boolean
  push_enabled: boolean
  sms_enabled: boolean
  frequency: string
  quiet_hours_start: string | null
  quiet_hours_end: string | null
}

export function useNotifications() {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [unreadCount, setUnreadCount] = useState(0)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchNotifications = useCallback(async () => {
    try {
      setLoading(true)
      setError(null)
      const response = await apiClient.get(endpoints.notifications.list)
      if (response.data && Array.isArray(response.data)) {
        setNotifications(response.data)
      } else {
        setNotifications([])
      }
    } catch (err: any) {
      setError(err.message || 'Erro ao carregar notificações')
      toast.error('Erro ao carregar notificações')
    } finally {
      setLoading(false)
    }
  }, [])

  const fetchUnreadCount = useCallback(async () => {
    try {
      const response = await apiClient.get(endpoints.notifications.unreadCount)
      if (response.data && typeof response.data === 'object' && 'count' in response.data) {
        setUnreadCount((response.data as { count: number }).count)
      }
    } catch (err) {

    }
  }, [])

  const markAsRead = useCallback(async (uuid: string) => {
    try {
      await apiClient.post(endpoints.notifications.markAsRead(uuid), {})
      setNotifications(prev =>
        prev.map(n => n.uuid === uuid ? { ...n, is_read: true, read_at: new Date().toISOString() } : n)
      )
      setUnreadCount(prev => Math.max(0, prev - 1))
      toast.success('Notificação marcada como lida')
    } catch (err) {
      toast.error('Erro ao marcar notificação como lida')
    }
  }, [])

  const markAllAsRead = useCallback(async () => {
    try {
      const response = await apiClient.post(endpoints.notifications.markAllAsRead, {})
      setNotifications(prev =>
        prev.map(n => ({ ...n, is_read: true, read_at: new Date().toISOString() }))
      )
      setUnreadCount(0)
      toast.success(response.message || 'Todas notificações marcadas como lidas')
    } catch (err) {
      toast.error('Erro ao marcar notificações como lidas')
    }
  }, [])

  const deleteNotification = useCallback(async (uuid: string) => {
    try {
      await apiClient.delete(endpoints.notifications.delete(uuid))
      setNotifications(prev => prev.filter(n => n.uuid !== uuid))
      toast.success('Notificação deletada')
    } catch (err) {
      toast.error('Erro ao deletar notificação')
    }
  }, [])

  useEffect(() => {
    fetchNotifications()
    fetchUnreadCount()
    
    // Poll unread count every 30 seconds
    const interval = setInterval(fetchUnreadCount, 30000)
    return () => clearInterval(interval)
  }, [fetchNotifications, fetchUnreadCount])

  return {
    notifications,
    unreadCount,
    loading,
    error,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    refetch: fetchNotifications,
  }
}

