import { unwrapCitiesPayload, unwrapResourceList } from '@/hooks/use-location'

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
