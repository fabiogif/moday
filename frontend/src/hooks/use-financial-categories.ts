import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import { endpoints } from '@/lib/api-client'

export interface FinancialCategory {
  id: number
  uuid: string
  name: string
  type: 'receita' | 'despesa'
  type_label: string
  description?: string
  color: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface FinancialCategoryFormData {
  name: string
  type: 'receita' | 'despesa'
  description?: string
  color?: string
  is_active?: boolean
}

/**
 * Hook para listar categorias
 */
export function useFinancialCategories(type?: 'receita' | 'despesa') {
  const endpoint = type ? endpoints.financialCategories.byType(type) : endpoints.financialCategories.list
  
  return useAuthenticatedApi<FinancialCategory[]>(endpoint, {
    immediate: true,
  })
}

/**
 * Hook para detalhes de uma categoria
 */
export function useFinancialCategory(uuid: string | null) {
  return useAuthenticatedApi<FinancialCategory>(
    uuid ? endpoints.financialCategories.show(uuid) : '',
    { immediate: !!uuid }
  )
}

/**
 * Hook para operações de mutação
 */
export function useFinancialCategoryMutation() {
  return useMutation<FinancialCategory, FinancialCategoryFormData>()
}

