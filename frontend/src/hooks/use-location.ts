import { useState, useEffect, useMemo } from 'react'
import { apiClient, endpoints } from '@/lib/api-client'

export interface State {
  id: number
  uf: string
  name: string
  ibge_code?: string
  region?: string
}

export interface City {
  id: number
  name: string
  ibge_code?: string
  is_capital: boolean
  state?: {
    id: number
    uf: string
    name: string
    ibge_code?: string
  }
}

export function useStates() {
  const [states, setStates] = useState<State[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    loadStates()
  }, [])

  async function loadStates() {
    try {
      setLoading(true)
      setError(null)
      const response = await apiClient.get<{ success: boolean; data: { success: boolean; data: State[] } }>(
        endpoints.states.list
      )

      if (response.success && response.data?.data) {
        setStates(Array.isArray(response.data.data) ? response.data.data : [])
      } else {
        setError('Erro ao carregar estados')
        setStates([])
      }
    } catch {
      setError('Erro ao carregar estados')
      setStates([])
    } finally {
      setLoading(false)
    }
  }

  return { states: states || [], loading, error, refresh: loadStates }
}

/**
 * Carrega municípios da base local IBGE pelo ID do estado.
 * Aceita também UF para compatibilidade: resolve o id via lista de estados.
 */
export function useCitiesByState(stateKey: string | number | null) {
  const { states } = useStates()
  const [cities, setCities] = useState<City[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const stateId = useMemo(() => {
    if (stateKey === null || stateKey === undefined || stateKey === '') {
      return null
    }
    if (typeof stateKey === 'number') {
      return stateKey
    }
    if (/^\d+$/.test(stateKey)) {
      return Number(stateKey)
    }
    const match = states.find((s) => s.uf === stateKey.toUpperCase())
    return match?.id ?? null
  }, [stateKey, states])

  useEffect(() => {
    if (stateId) {
      loadCities(stateId)
    } else {
      setCities([])
    }
  }, [stateId])

  async function loadCities(id: number) {
    try {
      setLoading(true)
      setError(null)
      const response = await apiClient.get<{
        success: boolean
        data: {
          success: boolean
          data: {
            state: State
            cities: City[]
          }
        }
      }>(endpoints.states.cities(id))

      if (response.success && response.data?.data && 'cities' in response.data.data) {
        setCities(Array.isArray(response.data.data.cities) ? response.data.data.cities : [])
      } else {
        setError('Erro ao carregar cidades')
        setCities([])
      }
    } catch {
      setError('Erro ao carregar cidades')
      setCities([])
    } finally {
      setLoading(false)
    }
  }

  return {
    cities: cities || [],
    loading,
    error,
    refresh: () => stateId && loadCities(stateId),
  }
}

export function useSearchCities(searchTerm: string, minLength = 2) {
  const [cities, setCities] = useState<City[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (searchTerm && searchTerm.length >= minLength) {
      const timer = setTimeout(() => {
        searchCities(searchTerm)
      }, 300)

      return () => clearTimeout(timer)
    } else {
      setCities([])
    }
  }, [searchTerm, minLength])

  async function searchCities(query: string) {
    try {
      setLoading(true)
      setError(null)
      const response = await apiClient.get<{
        success: boolean
        data: {
          success: boolean
          data: City[]
        }
      }>(`${endpoints.cities.search}?q=${encodeURIComponent(query)}`)

      if (response.success && response.data?.data) {
        setCities(Array.isArray(response.data.data) ? response.data.data : [])
      } else {
        setError('Erro ao pesquisar cidades')
        setCities([])
      }
    } catch {
      setError('Erro ao pesquisar cidades')
      setCities([])
    } finally {
      setLoading(false)
    }
  }

  return { cities: cities || [], loading, error }
}
