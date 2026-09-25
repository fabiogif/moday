import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { OrderFormDialog } from '../components/order-form-dialog'
import { apiClient } from '@/lib/api-client'

// Usa os hooks reais de useViaCEP/useCitiesByState (não mocka
// @/hooks/use-location) para reproduzir a corrida real entre o
// preenchimento do CEP e o carregamento assíncrono da lista de cidades,
// igual ao regressivo de clients/__tests__/client-form-dialog-cep-city.

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({ token: 'fake-token', isAuthenticated: true }),
}))

// Retornos estáveis entre renders, como nos hooks reais: um `data: []` novo a cada
// chamada faz o useEffect([clientsData]) do formulário rodar em loop e trava o teste.
jest.mock('@/hooks/use-authenticated-api', () => {
  const clients = { data: [], loading: false, error: null, refetch: jest.fn() }
  const products = { data: [], loading: false, error: null }
  const tables = { data: [], loading: false, error: null }
  const mutation = { mutate: jest.fn(), loading: false, error: null }
  return {
    useAuthenticatedClients: () => clients,
    useAuthenticatedCatalogProducts: () => products,
    useAuthenticatedTables: () => tables,
    useMutation: () => mutation,
  }
})

jest.mock('@/lib/api-client', () => {
  const actual = jest.requireActual('@/lib/api-client')
  return {
    ...actual,
    apiClient: {
      post: jest.fn(),
      get: jest.fn(),
    },
  }
})

const mockApiGet = apiClient.get as jest.MockedFunction<typeof apiClient.get>

describe('OrderFormDialog - preenchimento de Cidade de entrega pelo CEP', () => {
  beforeEach(() => {
    jest.clearAllMocks()

    mockApiGet.mockImplementation(async (url: string) => {
      if (url === '/api/cep/01001000') {
        return {
          success: true,
          data: {
            address: 'Praça da Sé',
            neighborhood: 'Sé',
            complement: '',
            zip_code: '01001-000',
            state: { id: 26, uf: 'SP', name: 'São Paulo', ibge_code: '35' },
            city: {
              id: 999,
              name: 'São Paulo',
              ibge_code: '3550308',
              state: { id: 26, uf: 'SP' },
            },
          },
        } as any
      }
      if (url === '/api/states') {
        return { success: true, data: [{ id: 26, uf: 'SP', name: 'São Paulo' }] } as any
      }
      if (url === '/api/states/SP/cities') {
        // A lista de cidades só chega depois que o CEP já preencheu
        // estado+cidade no formulário — é essa janela que expõe o bug.
        await new Promise((resolve) => setTimeout(resolve, 50))
        return {
          success: true,
          data: {
            state: { id: 26, uf: 'SP', name: 'São Paulo' },
            cities: [{ id: 999, name: 'São Paulo', is_capital: true }],
          },
        } as any
      }
      throw new Error(`GET inesperado: ${url}`)
    })
  })

  test('cidade de entrega permanece preenchida mesmo depois que a lista de cidades termina de carregar', async () => {
    const user = userEvent.setup()
    render(<OrderFormDialog onAddOrder={jest.fn()} renderAsPage />)

    await user.click(screen.getByRole('switch', { name: /Delivery/i }))

    const cepInput = await screen.findByPlaceholderText('01234-567')
    await user.type(cepInput, '01001000')
    await user.tab()

    await waitFor(() => {
      expect(screen.getByPlaceholderText('Rua das Flores, 123')).toHaveValue('Praça da Sé')
    })

    const cityCombobox = screen.getByRole('combobox', { name: /Cidade/i })

    await waitFor(
      () => {
        expect(cityCombobox).toHaveTextContent('São Paulo')
      },
      { timeout: 2000 }
    )
  })
})
