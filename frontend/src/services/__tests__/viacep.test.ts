import { mapCepApiResponse } from '@/services/viacep'

describe('mapCepApiResponse', () => {
  it('mapeia UF e cidade a partir do payload real do backend', () => {
    const address = mapCepApiResponse(
      {
        address: '2ª Travessa do Ouro',
        neighborhood: 'Liberdade',
        complement: '',
        zip_code: '40344-530',
        city: {
          id: 536,
          ibge_code: '2927408',
          name: 'Salvador',
          state: { id: 5, uf: 'BA' },
        },
        state: {
          id: 5,
          ibge_code: '29',
          uf: 'BA',
          name: 'Bahia',
        },
      },
      '40344530'
    )

    expect(address.state).toBe('BA')
    expect(address.city).toBe('Salvador')
    expect(address.address).toBe('2ª Travessa do Ouro')
  })

  it('usa o estado aninhado na cidade quando o state de topo não vem', () => {
    const address = mapCepApiResponse(
      {
        address: 'Praça da Sé',
        neighborhood: 'Sé',
        zip_code: '01001-000',
        city: {
          id: 1,
          name: 'São Paulo',
          state: { uf: 'SP' },
        },
        state: null,
      },
      '01001000'
    )

    expect(address.state).toBe('SP')
    expect(address.city).toBe('São Paulo')
  })
})
