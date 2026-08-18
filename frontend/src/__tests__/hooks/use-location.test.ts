import { renderHook, waitFor } from '@testing-library/react'
import { unwrapCitiesPayload, unwrapResourceList, useCitiesByState } from '@/hooks/use-location'
import { apiClient } from '@/lib/api-client'

jest.mock('@/lib/api-client', () => ({
  apiClient: {
    get: jest.fn(),
  },
  endpoints: {
    states: {
      list: '/api/states',
      cities: (id: string | number) => `/api/states/${id}/cities`,
    },
    cities: {
      search: '/api/cities/search',
    },
  },
}))

describe('unwrapResourceList', () => {
  it('lê o array direto do data da API de estados', () => {
    const payload = [
      { id: 5, uf: 'BA', name: 'Bahia' },
      { id: 26, uf: 'SP', name: 'São Paulo' },
    ]

    expect(unwrapResourceList(payload)).toHaveLength(2)
    expect(unwrapResourceList(payload)[0]).toMatchObject({ uf: 'BA' })
  })

  it('aceita o array aninhado em data.data', () => {
    expect(unwrapResourceList({ data: [{ id: 5, uf: 'BA', name: 'Bahia' }] })).toHaveLength(1)
  })
})

describe('unwrapCitiesPayload', () => {
  it('lê cities do payload { state, cities }', () => {
    const payload = {
      state: { id: 5, uf: 'BA', name: 'Bahia' },
      cities: [{ id: 536, name: 'Salvador', is_capital: true }],
    }

    expect(unwrapCitiesPayload(payload)).toEqual([
      { id: 536, name: 'Salvador', is_capital: true },
    ])
  })

  it('aceita o mesmo objeto aninhado em data', () => {
    const payload = {
      data: {
        state: { id: 5, uf: 'BA', name: 'Bahia' },
        cities: [{ id: 536, name: 'Salvador', is_capital: true }],
      },
    }

    expect(unwrapCitiesPayload(payload)[0].name).toBe('Salvador')
  })
})

describe('useCitiesByState', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('busca cidades pela UF assim que o estado é informado', async () => {
    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: {
        state: { id: 5, uf: 'BA', name: 'Bahia' },
        cities: [{ id: 536, name: 'Salvador', is_capital: true }],
      },
    })

    const { result } = renderHook(() => useCitiesByState('BA'))

    await waitFor(() => expect(result.current.cities).toHaveLength(1))

    expect(apiClient.get).toHaveBeenCalledWith('/api/states/BA/cities')
    expect(result.current.cities[0].name).toBe('Salvador')
    expect(result.current.loading).toBe(false)
  })
})
