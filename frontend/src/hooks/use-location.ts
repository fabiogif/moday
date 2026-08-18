import { useState, useEffect } from 'react'
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

/** Aceita `{ data: T[] }` ou o array já no `data` do ApiResponse. */
export function unwrapResourceList<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) {
    return payload as T[]
  }
  if (payload && typeof payload === 'object' && Array.isArray((payload as { data?: unknown }).data)) {
    return (payload as { data: T[] }).data
  }
  return []
}

/** Aceita `{ state, cities }` ou o mesmo objeto aninhado em `data`. */
export function unwrapCitiesPayload(payload: unknown): City[] {
  if (!payload || typeof payload !== 'object') {
    return []
  }

  const source =
    'cities' in payload
      ? payload
      : (payload as { data?: unknown }).data && typeof (payload as { data?: unknown }).data === 'object'
        ? (payload as { data: object }).data
        : null

  if (!source || !('cities' in source)) {
    return []
  }

  const cities = (source as { cities: unknown }).cities
  return Array.isArray(cities) ? cities : []
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
      const response = await apiClient.get<State[] | { data: State[] }>(endpoints.states.list)

      if (response.success) {
        setStates(unwrapResourceList<State>(response.data))
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
 * Carrega municípios da base local IBGE.
 * A API aceita id numérico ou UF — não espera a lista de estados resolver o id.
 */
export function useCitiesByState(stateKey: string | number | null) {
  const [cities, setCities] = useState<City[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const requestKey =
    stateKey === null || stateKey === undefined || stateKey === ''
      ? null
      : String(stateKey)

  useEffect(() => {
    if (!requestKey) {
      setCities([])
      setLoading(false)
      setError(null)
      return
    }

    const key = requestKey
    let cancelled = false

    async function loadCities() {
      try {
        setLoading(true)
        setError(null)
        const response = await apiClient.get<{ state: State; cities: City[] } | { data: { state: State; cities: City[] } }>(
          endpoints.states.cities(key)
        )

        if (cancelled) return

        if (response.success) {
          setCities(unwrapCitiesPayload(response.data))
        } else {
          setError('Erro ao carregar cidades')
          setCities([])
        }
      } catch {
        if (!cancelled) {
          setError('Erro ao carregar cidades')
          setCities([])
        }
      } finally {
        if (!cancelled) {
          setLoading(false)
        }
      }
    }

    void loadCities()
    return () => {
      cancelled = true
    }
  }, [requestKey])

  return {
    cities: cities || [],
    loading,
    error,
    refresh: () => undefined,
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
      const response = await apiClient.get<City[] | { data: City[] }>(
        `${endpoints.cities.search}?q=${encodeURIComponent(query)}`
      )

      if (response.success) {
        setCities(unwrapResourceList<City>(response.data))
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
