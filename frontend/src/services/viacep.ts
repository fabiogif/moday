/**
 * Consulta de CEP via proxy backend (ViaCEP + resolução IBGE local).
 */

import { apiClient, endpoints } from '@/lib/api-client'

export interface AddressData {
  address: string
  neighborhood: string
  city: string
  state: string
  zipCode: string
  complement?: string
  stateId?: number
  cityId?: number
  cityIbgeCode?: string
  logradouro?: string
  bairro?: string
  localidade?: string
  uf?: string
}

interface CepApiResponse {
  address: string
  neighborhood: string
  complement?: string
  zip_code: string
  state: {
    id: number
    uf: string
    name: string
    ibge_code?: string
  }
  city: {
    id: number
    name: string
    ibge_code?: string
  }
}

/**
 * Busca endereço pelo CEP através da API interna.
 */
export async function searchAddressByCEP(cep: string): Promise<AddressData | null> {
  const cleanCEP = cep.replace(/\D/g, '')

  if (cleanCEP.length !== 8) {
    throw new Error('CEP deve ter 8 dígitos')
  }

  try {
    // apiClient.get<T> tipa `data` como T (payload interno do ApiResponseClass)
    const response = await apiClient.get<CepApiResponse | null>(
      endpoints.cep.lookup(cleanCEP)
    )

    if (!response.success || !response.data) {
      return null
    }

    const data = response.data

    return {
      address: data.address || '',
      neighborhood: data.neighborhood || '',
      city: data.city?.name || '',
      state: data.state?.uf || '',
      zipCode: data.zip_code || cleanCEP,
      complement: data.complement || '',
      stateId: data.state?.id,
      cityId: data.city?.id,
      cityIbgeCode: data.city?.ibge_code,
      logradouro: data.address || '',
      bairro: data.neighborhood || '',
      localidade: data.city?.name || '',
      uf: data.state?.uf || '',
    }
  } catch (err: unknown) {
    const status = (err as { status?: number })?.status
    if (status === 404 || status === 422) {
      return null
    }
    throw err
  }
}

export function isValidCEP(cep: string): boolean {
  const cleanCEP = cep.replace(/\D/g, '')
  return cleanCEP.length === 8
}
