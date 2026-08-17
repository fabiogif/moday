import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import { endpoints } from '@/lib/api-client'

export interface Event {
  id: number
  uuid: string
  title: string
  type: 'promocao' | 'aviso' | 'outro'
  type_label: string
  color: string
  start_date: string
  start_date_formatted: string
  end_date: string
  end_date_formatted: string
  duration_minutes: number
  location?: string
  description: string
  is_active: boolean
  notifications_sent: boolean
  clients?: any[]
  clients_count: number
  created_at: string
  updated_at: string
}

export interface EventFormData {
  title: string
  type: 'promocao' | 'aviso' | 'outro'
  color?: string
  start_date: string
  duration_minutes: number
  location?: string
  description: string
  client_ids: number[]
  notification_channels?: ('email' | 'whatsapp' | 'sms')[]
  is_active?: boolean
}

export interface EventStats {
  total: number
  active: number
  upcoming: number
  past: number
  notifications_sent: number
}

/**
 * Hook para listar eventos
 */
export function useEvents(startDate?: string, endDate?: string) {
  const queryParams: Record<string, any> = {}
  
  if (startDate) queryParams.start_date = startDate
  if (endDate) queryParams.end_date = endDate

  return useAuthenticatedApi<Event[]>(endpoints.events.list, {
    immediate: true,
    queryParams,
  })
}

/**
 * Hook para estatísticas de eventos
 */
export function useEventStats() {
  return useAuthenticatedApi<EventStats>(endpoints.events.stats, {
    immediate: true,
  })
}

/**
 * Hook para próximos eventos
 */
export function useUpcomingEvents(limit: number = 10) {
  return useAuthenticatedApi<Event[]>(endpoints.events.upcoming, {
    immediate: true,
    queryParams: { limit },
  })
}

/**
 * Hook para detalhes de um evento
 */
export function useEvent(uuid: string | null) {
  return useAuthenticatedApi<Event>(
    uuid ? endpoints.events.show(uuid) : '',
    { immediate: !!uuid }
  )
}

/**
 * Hook para operações de mutação (criar, atualizar, deletar)
 */
export function useEventMutation() {
  return useMutation<Event, EventFormData>()
}

