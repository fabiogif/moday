import { applyCepToForm, clearCepLinkedFields, applyCepToStateHandlers } from '@/lib/apply-cep-to-form'
import type { AddressData } from '@/services/viacep'

const ADDRESS: AddressData = {
  address: '2ª Travessa do Ouro',
  neighborhood: 'Liberdade',
  city: 'Salvador',
  state: 'BA',
  zipCode: '40344-530',
  complement: '',
}

describe('applyCepToForm', () => {
  it('preenche estado e cidade juntos, sem atraso', () => {
    const setValue = jest.fn()

    applyCepToForm(setValue, ADDRESS, {
      address: 'address',
      neighborhood: 'neighborhood',
      state: 'state',
      city: 'city',
    })

    expect(setValue).toHaveBeenCalledWith('state', 'BA', expect.any(Object))
    expect(setValue).toHaveBeenCalledWith('city', 'Salvador', expect.any(Object))
    expect(setValue).toHaveBeenCalledWith('address', '2ª Travessa do Ouro', expect.any(Object))
  })

  it('clearCepLinkedFields remove só os campos vinculados ao CEP', () => {
    const setValue = jest.fn()

    clearCepLinkedFields(setValue, {
      address: 'address',
      neighborhood: 'neighborhood',
      state: 'state',
      city: 'city',
    })

    expect(setValue).toHaveBeenCalledWith('state', '', expect.any(Object))
    expect(setValue).toHaveBeenCalledWith('city', '', expect.any(Object))
    expect(setValue).toHaveBeenCalledWith('address', '', expect.any(Object))
    expect(setValue).toHaveBeenCalledWith('neighborhood', '', expect.any(Object))
  })
})

describe('applyCepToStateHandlers', () => {
  it('aplica estado e cidade imediatamente', () => {
    const handlers = {
      setState: jest.fn(),
      setCity: jest.fn(),
      setAddress: jest.fn(),
      setNeighborhood: jest.fn(),
    }

    applyCepToStateHandlers(ADDRESS, handlers)

    expect(handlers.setState).toHaveBeenCalledWith('BA')
    expect(handlers.setCity).toHaveBeenCalledWith('Salvador')
    expect(handlers.setAddress).toHaveBeenCalledWith('2ª Travessa do Ouro')
  })
})
