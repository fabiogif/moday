import { screen, waitFor } from '@testing-library/react'
import { render, generateOrder } from '../utils/test-utils'
import OrdersPage from '@/app/(dashboard)/orders/page'
import {
  useAuthenticatedOrders,
  useAuthenticatedOrderStats,
  useMutation,
} from '@/hooks/use-authenticated-api'
import { useAuth } from '@/contexts/auth-context'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: jest.fn(),
}))

jest.mock('@/hooks/use-order-refresh', () => ({
  useOrderRefresh: () => ({
    shouldRefresh: false,
    resetRefresh: jest.fn(),
  }),
}))

const mockUseAuth = useAuth as jest.MockedFunction<typeof useAuth>
const mockUseAuthenticatedOrders = useAuthenticatedOrders as jest.MockedFunction<
  typeof useAuthenticatedOrders
>
const mockUseAuthenticatedOrderStats =
  useAuthenticatedOrderStats as jest.MockedFunction<typeof useAuthenticatedOrderStats>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

const defaultOrderStats = {
  total_orders: { current: 2, previous: 0, growth: 0 },
  pending_orders: { current: 1, previous: 0, growth: 0 },
  paid_orders: { current: 1, previous: 0, growth: 0 },
  delivered_orders: { current: 0, previous: 0, growth: 0 },
  total_revenue: { current: 500, previous: 0, growth: 0 },
}

describe('Orders CRUD', () => {
  const mockOrders = [
    generateOrder({
      identify: 'ORD-001',
      client: { id: 1, name: 'John Doe', email: 'john@example.com', phone: '11999999999' },
    }),
    generateOrder({
      id: 2,
      identify: 'ORD-002',
      client: { id: 2, name: 'Jane Smith', email: 'jane@example.com', phone: '11888888888' },
      status: 'Concluído',
    }),
  ]

  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuth.mockReturnValue({
      isLoading: false,
      isAuthenticated: true,
    } as ReturnType<typeof useAuth>)

    mockUseAuthenticatedOrders.mockReturnValue({
      data: mockOrders,
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    mockUseAuthenticatedOrderStats.mockReturnValue({
      data: defaultOrderStats,
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

  it('should render orders list', async () => {
    render(<OrdersPage />)

    await waitFor(() => {
      expect(screen.getAllByText('ORD-001').length).toBeGreaterThan(0)
      expect(screen.getByText('John Doe')).toBeInTheDocument()
      expect(screen.getByText('Jane Smith')).toBeInTheDocument()
    })
  })

  it('should display loading state', () => {
    mockUseAuthenticatedOrders.mockReturnValue({
      data: [],
      loading: true,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<OrdersPage />)
    expect(screen.getByText('Carregando pedidos...')).toBeInTheDocument()
  })

  it('should display error state', () => {
    mockUseAuthenticatedOrders.mockReturnValue({
      data: null,
      loading: false,
      error: 'Falha na API',
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<OrdersPage />)
    expect(screen.getByText('Erro ao carregar pedidos: Falha na API')).toBeInTheDocument()
  })

  it('should render page title', () => {
    render(<OrdersPage />)
    expect(screen.getByRole('heading', { name: 'Pedidos' })).toBeInTheDocument()
  })
})
