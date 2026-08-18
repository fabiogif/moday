import { useState } from 'react'
import { act, fireEvent, render, screen, waitFor } from '@testing-library/react'
import { DeliveryAddressForm } from '../delivery-address-form'
import { searchAddressByCEP } from '@/services/viacep'

jest.mock('@/services/viacep', () => ({
  isValidCEP: (cep: string) => cep.replace(/\D/g, '').length === 8,
  searchAddressByCEP: jest.fn(),
}))

jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    info: jest.fn(),
    error: jest.fn(),
  },
}))

jest.mock('@/hooks/use-location', () => ({
  useStates: () => ({
    states: [{ id: 5, uf: 'BA', name: 'Bahia' }],
    loading: false,
    error: null,
    refresh: jest.fn(),
  }),
  useCitiesByState: () => ({
    cities: [{ id: 536, name: 'Salvador', is_capital: true }],
    loading: false,
    error: null,
    refresh: jest.fn(),
  }),
}))

const searchMock = searchAddressByCEP as jest.MockedFunction<typeof searchAddressByCEP>

const ADDRESS = {
  address: '2ª Travessa do Ouro',
  neighborhood: 'Liberdade',
  city: 'Salvador',
  state: 'BA',
  zipCode: '40344-530',
}

const EMPTY_ADDRESS = {
  zip: '',
  address: '',
  number: '',
  neighborhood: '',
  city: '',
  state: '',
  complement: '',
}

function Harness({ initial = EMPTY_ADDRESS }: { initial?: typeof EMPTY_ADDRESS }) {
  const [address, setAddress] = useState(initial)
  return <DeliveryAddressForm address={address} onAddressChange={setAddress} />
}

function getStateTrigger() {
  return screen.getAllByRole('combobox')[0]
}

describe('DeliveryAddressForm CEP', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('CEP válido encontrado preenche e trava estado e cidade', async () => {
    searchMock.mockResolvedValue(ADDRESS)
    render(<Harness />)

    fireEvent.change(screen.getByPlaceholderText('40325-465'), {
      target: { value: '40344530' },
    })

    await waitFor(() => {
      expect(screen.getByDisplayValue('2ª Travessa do Ouro')).toBeInTheDocument()
    })
    expect(screen.getByText('Salvador (Capital)')).toBeInTheDocument()
    expect(getStateTrigger()).toBeDisabled()
  })

  it('CEP vazio mantém estado e cidade editáveis', () => {
    render(<Harness />)

    expect(getStateTrigger()).not.toBeDisabled()
  })

  it('CEP não encontrado mantém os campos editáveis', async () => {
    searchMock.mockResolvedValue(null)
    render(
      <Harness
        initial={{ ...EMPTY_ADDRESS, state: 'BA', city: 'Salvador' }}
      />
    )

    fireEvent.change(screen.getByPlaceholderText('40325-465'), {
      target: { value: '40325465' },
    })

    await waitFor(() => expect(searchMock).toHaveBeenCalled())
    expect(getStateTrigger()).not.toBeDisabled()
    expect(screen.getByText('Salvador (Capital)')).toBeInTheDocument()
  })

  it('CEP inválido não consulta e não trava os campos', async () => {
    render(<Harness />)

    fireEvent.change(screen.getByPlaceholderText('40325-465'), {
      target: { value: '123' },
    })

    expect(searchMock).not.toHaveBeenCalled()
    expect(getStateTrigger()).not.toBeDisabled()
  })

  it('alterar um CEP encontrado destrava os campos e consulta de novo', async () => {
    searchMock.mockResolvedValue(ADDRESS)
    render(<Harness />)
    const input = screen.getByPlaceholderText('40325-465')

    fireEvent.change(input, { target: { value: '40344530' } })
    await waitFor(() => expect(getStateTrigger()).toBeDisabled())

    fireEvent.change(input, { target: { value: '40344-53' } })
    expect(getStateTrigger()).not.toBeDisabled()

    searchMock.mockResolvedValue({ ...ADDRESS, city: 'Salvador', state: 'BA' })
    fireEvent.change(input, { target: { value: '40325465' } })
    await waitFor(() => expect(searchMock).toHaveBeenCalledTimes(2))
  })

  it('apagar o CEP torna estado e cidade editáveis', async () => {
    searchMock.mockResolvedValue(ADDRESS)
    render(<Harness />)
    const input = screen.getByPlaceholderText('40325-465')

    fireEvent.change(input, { target: { value: '40344530' } })
    await waitFor(() => expect(getStateTrigger()).toBeDisabled())

    fireEvent.change(input, { target: { value: '' } })
    expect(getStateTrigger()).not.toBeDisabled()
  })

  it('erro na consulta mantém os campos editáveis', async () => {
    searchMock.mockRejectedValue(new Error('Falha de rede'))
    render(<Harness />)

    fireEvent.change(screen.getByPlaceholderText('40325-465'), {
      target: { value: '01001000' },
    })

    await waitFor(() => expect(searchMock).toHaveBeenCalled())
    expect(getStateTrigger()).not.toBeDisabled()
  })

  it('resposta antiga não sobrescreve a consulta mais recente', async () => {
    let resolveFirst: (value: typeof ADDRESS | null) => void = () => undefined
    let resolveSecond: (value: typeof ADDRESS | null) => void = () => undefined

    searchMock
      .mockImplementationOnce(
        () =>
          new Promise((resolve) => {
            resolveFirst = resolve
          })
      )
      .mockImplementationOnce(
        () =>
          new Promise((resolve) => {
            resolveSecond = resolve
          })
      )

    render(<Harness />)
    const input = screen.getByPlaceholderText('40325-465')

    fireEvent.change(input, { target: { value: '11111111' } })
    await waitFor(() => expect(searchMock).toHaveBeenCalledTimes(1))

    fireEvent.change(input, { target: { value: '40344530' } })
    await waitFor(() => expect(searchMock).toHaveBeenCalledTimes(2))

    await act(async () => {
      resolveSecond(ADDRESS)
    })
    await waitFor(() => {
      expect(screen.getByDisplayValue('2ª Travessa do Ouro')).toBeInTheDocument()
    })

    await act(async () => {
      resolveFirst({ ...ADDRESS, address: 'Rua Antiga', city: 'São Paulo', state: 'SP' })
    })

    expect(screen.getByDisplayValue('2ª Travessa do Ouro')).toBeInTheDocument()
    expect(screen.queryByDisplayValue('Rua Antiga')).not.toBeInTheDocument()
    expect(getStateTrigger()).toBeDisabled()
  })
})
