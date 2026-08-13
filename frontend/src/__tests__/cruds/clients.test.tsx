import { screen, waitFor } from '@testing-library/react'
import { render, generateClient } from '../utils/test-utils'
import ClientsPage from '@/app/(dashboard)/clients/page'
import {
  useAuthenticatedClients,
  useAuthenticatedClientStats,
  useMutation,
} from '@/hooks/use-authenticated-api'
import { useAuth } from '@/contexts/auth-context'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: jest.fn(),
}))

const mockUseAuth = useAuth as jest.MockedFunction<typeof useAuth>
const mockUseAuthenticatedClients = useAuthenticatedClients as jest.MockedFunction<
  typeof useAuthenticatedClients
>
const mockUseAuthenticatedClientStats =
  useAuthenticatedClientStats as jest.MockedFunction<typeof useAuthenticatedClientStats>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

const defaultClientStats = {
  total_clients: { current: 2, previous: 0, growth: 0 },
  active_clients: { current: 2, previous: 0, growth: 0 },
  orders_per_client: { current: 4, previous: 0, growth: 0 },
  new_clients: { current: 1, previous: 0, growth: 0 },
}

describe('Clients CRUD', () => {
  const mockClients = [
    generateClient({ id: 1, name: 'John Smith', email: 'john@example.com' }),
    generateClient({ id: 2, name: 'Jane Doe', email: 'jane@example.com', is_active: false }),
  ]

  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuth.mockReturnValue({
      isLoading: false,
      isAuthenticated: true,
    } as ReturnType<typeof useAuth>)

    mockUseAuthenticatedClients.mockReturnValue({
      data: mockClients,
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    mockUseAuthenticatedClientStats.mockReturnValue({
      data: defaultClientStats,
      loading: false,
      error: null,
      refetch: jest.fn(),
      isAuthenticated: true,
    })

    mockUseMutation.mockReturnValue({
      mutate: jest.fn().mockResolvedValue({}),
      loading: false,
      error: null,
    })
  })

  it('should render clients list', async () => {
    render(<ClientsPage />)

    await waitFor(() => {
      expect(screen.getByText('John Smith')).toBeInTheDocument()
      expect(screen.getByText('Jane Doe')).toBeInTheDocument()
    })
  })

  it('should display loading state', () => {
    mockUseAuthenticatedClients.mockReturnValue({
      data: [],
      loading: true,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<ClientsPage />)
    expect(screen.getByText('Carregando clientes...')).toBeInTheDocument()
  })

  it('should display error state', () => {
    mockUseAuthenticatedClients.mockReturnValue({
      data: null,
      loading: false,
      error: 'Falha na API',
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<ClientsPage />)
    expect(screen.getByText('Erro ao carregar clientes: Falha na API')).toBeInTheDocument()
  })

  it('should show authentication error when not authenticated', () => {
    mockUseAuth.mockReturnValue({
      isLoading: false,
      isAuthenticated: false,
    } as ReturnType<typeof useAuth>)

    mockUseAuthenticatedClients.mockReturnValue({
      data: [],
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: false,
    })

    render(<ClientsPage />)
    expect(
      screen.getByText('Usuário não autenticado. Faça login para continuar.')
    ).toBeInTheDocument()
  })
})
