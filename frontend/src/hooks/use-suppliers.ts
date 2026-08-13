import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import { endpoints } from '@/lib/api-client'

export interface Supplier {
  id: number
  uuid: string
  name: string
  fantasy_name?: string
  document: string
  document_type: 'cpf' | 'cnpj'
  email?: string
  phone: string
  phone2?: string
  address?: string
  number?: string
  complement?: string
  neighborhood?: string
  city?: string
  state?: string
  zip_code?: string
  full_address?: string
  bank_name?: string
  bank_agency?: string
  bank_account?: string
  pix_key?: string
  notes?: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface SupplierFormData {
  name: string
  fantasy_name?: string
  document: string
  document_type: 'cpf' | 'cnpj'
  email?: string
  phone: string
  phone2?: string
  address?: string
  number?: string
  complement?: string
  neighborhood?: string
  city?: string
  state?: string
  zip_code?: string
  bank_name?: string
  bank_agency?: string
  bank_account?: string
  pix_key?: string
  notes?: string
  is_active?: boolean
}

/**
 * Hook para listar fornecedores
 */
export function useSuppliers() {
  return useAuthenticatedApi<Supplier[]>(endpoints.suppliers.list, {
    immediate: true,
  })
}

/**
 * Hook para detalhes de um fornecedor
 */
export function useSupplier(uuid: string | null) {
  return useAuthenticatedApi<Supplier>(
    uuid ? endpoints.suppliers.show(uuid) : '',
    { immediate: !!uuid }
  )
}

/**
 * Hook para operações de mutação (criar, atualizar, deletar)
 */
export function useSupplierMutation() {
  return useMutation<Supplier, SupplierFormData>()
}

/**
 * Hook para verificar documento
 */
export function useCheckSupplierDocument() {
  return useMutation<{ exists: boolean; supplier: Supplier | null }, { document: string }>()
}

