import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import { endpoints } from '@/lib/api-client'

export type DeliveryFeeType = 'fixed' | 'negotiable' | 'free'

export interface DeliveryFeeZone {
  id: number
  uuid: string
  tenant_id: number
  city: string
  neighborhood: string
  fee_type: DeliveryFeeType
  fee_value: number | null
  estimated_time_minutes: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface DeliveryFeeZoneFormData {
  city: string
  neighborhood: string
  fee_type: DeliveryFeeType
  fee_value?: number | null
  estimated_time_minutes: number
  is_active?: boolean
}

/**
 * Hook para listar as zonas de taxa de entrega por bairro do tenant.
 */
export function useDeliveryFeeZones() {
  return useAuthenticatedApi<DeliveryFeeZone[]>(endpoints.deliveryFeeZones.list, {
    immediate: true,
  })
}

/**
 * Hook para operações de criar/editar/excluir zonas de taxa de entrega.
 */
export function useDeliveryFeeZoneMutation() {
  return useMutation<DeliveryFeeZone, DeliveryFeeZoneFormData>()
}
