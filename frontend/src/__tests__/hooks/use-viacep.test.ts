import { renderHook, act, waitFor } from '@testing-library/react'
import { useViaCEP } from '@/hooks/use-viacep'
import { searchAddressByCEP } from '@/services/viacep'
import { toast } from 'sonner'

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

const searchMock = searchAddressByCEP as jest.MockedFunction<typeof searchAddressByCEP>

const ADDRESS = {
  address: '2ª Travessa do Ouro',
  neighborhood: 'Liberdade',
  city: 'Salvador',
  state: 'BA',
  zipCode: '40344-530',
}

describe('useViaCEP', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('inicia com estado e cidade desbloqueados', () => {
    const { result } = renderHook(() => useViaCEP())

    expect(result.current.found).toBe(false)
    expect(result.current.status).toBe('idle')
    expect(result.current.loading).toBe(false)
  })

  it('CEP válido encontrado trava o endereço', async () => {
    searchMock.mockResolvedValue(ADDRESS)
    const { result } = renderHook(() => useViaCEP())

    await act(async () => {
      const address = await result.current.searchCEP('40344530')
      expect(address?.city).toBe('Salvador')
      expect(address?.state).toBe('BA')
    })

    expect(result.current.found).toBe(true)
    expect(result.current.status).toBe('found')
    expect(toast.success).toHaveBeenCalled()
  })

  it('CEP vazio não trava e não dispara consulta', async () => {
    const { result } = renderHook(() => useViaCEP())

    await act(async () => {
      await result.current.searchCEP('')
    })

    expect(searchMock).not.toHaveBeenCalled()
    expect(result.current.found).toBe(false)
    expect(result.current.status).toBe('idle')
  })

  it('CEP não encontrado mantém campos editáveis', async () => {
    searchMock.mockResolvedValue(null)
    const { result } = renderHook(() => useViaCEP())

    await act(async () => {
      const address = await result.current.searchCEP('40325465')
      expect(address).toBeNull()
    })

    expect(result.current.found).toBe(false)
    expect(result.current.status).toBe('not_found')
    expect(toast.info).toHaveBeenCalled()
    expect(toast.error).not.toHaveBeenCalled()
  })

  it('CEP inválido mantém campos editáveis', async () => {
    const { result } = renderHook(() => useViaCEP())

    await act(async () => {
      await result.current.searchCEP('123')
    })

    expect(searchMock).not.toHaveBeenCalled()
    expect(result.current.found).toBe(false)
    expect(result.current.status).toBe('invalid')
  })

  it('erro na consulta mantém campos editáveis', async () => {
    searchMock.mockRejectedValue(new Error('Falha de rede'))
    const { result } = renderHook(() => useViaCEP())

    await act(async () => {
      await result.current.searchCEP('01001000')
    })

    expect(result.current.found).toBe(false)
    expect(result.current.status).toBe('error')
    expect(toast.error).toHaveBeenCalled()
  })

  it('alterar ou apagar um CEP encontrado destrava os campos', async () => {
    searchMock.mockResolvedValue(ADDRESS)
    const { result } = renderHook(() => useViaCEP())

    await act(async () => {
      await result.current.searchCEP('40344530')
    })
    expect(result.current.found).toBe(true)

    let shouldClear = false
    act(() => {
      shouldClear = result.current.notifyCepChange('40344-53')
    })
    expect(shouldClear).toBe(true)
    expect(result.current.found).toBe(false)

    await act(async () => {
      await result.current.searchCEP('40344530')
    })
    expect(result.current.found).toBe(true)

    act(() => {
      shouldClear = result.current.notifyCepChange('')
    })
    expect(shouldClear).toBe(true)
    expect(result.current.found).toBe(false)
  })

  it('não limpa dados manuais quando o CEP nunca foi encontrado', () => {
    const { result } = renderHook(() => useViaCEP())

    let shouldClear = true
    act(() => {
      shouldClear = result.current.notifyCepChange('40325465')
    })

    expect(shouldClear).toBe(false)
    expect(result.current.found).toBe(false)
  })

  it('ignora resposta antiga quando há duas consultas simultâneas', async () => {
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

    const { result } = renderHook(() => useViaCEP())

    let firstResult: typeof ADDRESS | null | undefined
    let secondResult: typeof ADDRESS | null | undefined

    act(() => {
      void result.current.searchCEP('11111111').then((value) => {
        firstResult = value
      })
    })

    await waitFor(() => expect(searchMock).toHaveBeenCalledTimes(1))

    act(() => {
      void result.current.searchCEP('40344530').then((value) => {
        secondResult = value
      })
    })

    await waitFor(() => expect(searchMock).toHaveBeenCalledTimes(2))

    await act(async () => {
      resolveSecond(ADDRESS)
    })
    await waitFor(() => expect(secondResult).toEqual(ADDRESS))
    expect(result.current.found).toBe(true)

    await act(async () => {
      resolveFirst({ ...ADDRESS, city: 'São Paulo', state: 'SP' })
    })

    await waitFor(() => expect(firstResult).toBeNull())
    expect(result.current.found).toBe(true)
    expect(result.current.status).toBe('found')
  })

  it('cancela a consulta em andamento se o CEP mudar antes do resultado', async () => {
    let resolveLookup: (value: typeof ADDRESS | null) => void = () => undefined
    searchMock.mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          resolveLookup = resolve
        })
    )

    const { result } = renderHook(() => useViaCEP())

    act(() => {
      void result.current.searchCEP('40344530')
    })

    await waitFor(() => expect(searchMock).toHaveBeenCalledTimes(1))
    expect(result.current.found).toBe(false)

    act(() => {
      result.current.notifyCepChange('40344-53')
    })

    expect(result.current.found).toBe(false)
    expect(result.current.loading).toBe(false)

    await act(async () => {
      resolveLookup(ADDRESS)
    })

    expect(result.current.found).toBe(false)
    expect(result.current.status).toBe('invalid')
  })

  it('não consulta de novo o mesmo CEP já encontrado', async () => {
    searchMock.mockResolvedValue(ADDRESS)
    const { result } = renderHook(() => useViaCEP())

    await act(async () => {
      await result.current.searchCEP('40344530')
    })
    expect(result.current.found).toBe(true)
    expect(searchMock).toHaveBeenCalledTimes(1)

    await act(async () => {
      const address = await result.current.searchCEP('40344-530')
      expect(address).toBeNull()
    })

    expect(searchMock).toHaveBeenCalledTimes(1)
    expect(result.current.found).toBe(true)
  })
})
