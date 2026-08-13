import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import { endpoints } from '@/lib/api-client'

export interface StoreHour {
  id: number
  uuid: string
  tenant_id: number
  is_always_open: boolean
  day_of_week?: number
  day_name?: string
  day_name_short?: string
  delivery_type: 'delivery' | 'pickup' | 'both'
  delivery_type_label: string
  start_time?: string
  end_time?: string
  start_time_2?: string
  end_time_2?: string
  start_time_formatted?: string
  end_time_formatted?: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface StoreHourFormData {
  is_always_open?: boolean
  day_of_week?: number
  delivery_type: 'delivery' | 'pickup' | 'both'
  start_time?: string
  end_time?: string
  start_time_2?: string | null
  end_time_2?: string | null
  is_active?: boolean
}

export interface StoreHourStats {
  is_always_open: boolean
  total_hours: number
  days_configured: number
  has_delivery: boolean
  has_pickup: boolean
}

export interface StoreStatus {
  is_open: boolean
  timestamp: string
}

/**
 * Hook para listar horários de funcionamento
 */
export function useStoreHours(filters?: {
  day_of_week?: number
  delivery_type?: string
  is_active?: boolean
}) {
  return useAuthenticatedApi<StoreHour[]>(endpoints.storeHours.list, {
    immediate: true,
    queryParams: filters,
  })
}

/**
 * Hook para estatísticas de horários
 */
export function useStoreHourStats() {
  return useAuthenticatedApi<StoreHourStats>(endpoints.storeHours.stats, {
    immediate: true,
  })
}

/**
 * Hook para verificar se a loja está aberta
 */
export function useStoreStatus(deliveryType?: string) {
  return useAuthenticatedApi<StoreStatus>(endpoints.storeHours.checkIsOpen, {
    immediate: true,
    queryParams: deliveryType ? { delivery_type: deliveryType } : undefined,
  })
}

/**
 * Hook para detalhes de um horário
 */
export function useStoreHour(uuid: string | null) {
  return useAuthenticatedApi<StoreHour>(
    uuid ? endpoints.storeHours.show(uuid) : '',
    { immediate: !!uuid }
  )
}

/**
 * Hook para operações de mutação
 */
export function useStoreHourMutation() {
  return useMutation<StoreHour, StoreHourFormData>()
}

