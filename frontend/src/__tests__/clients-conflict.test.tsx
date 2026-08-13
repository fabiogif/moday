import { render, screen, waitFor } from '@testing-library/react'
import ClientsPage from '@/app/(dashboard)/clients/page'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    token: 'mock-token',
    isAuthenticated: true,
    isLoading: false,
    user: { name: 'Test User', email: 'test@test.com' },
  }),
}))

jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedClients: jest.fn(),
  useAuthenticatedClientStats: jest.fn(),
  useMutation: jest.fn(() => ({ mutate: jest.fn(), loading: false, error: null })),
}))

jest.mock('@/app/(dashboard)/clients/components/stat-cards', () => ({
  StatCards: () => <div data-testid="client-stat-cards" />,
}))

jest.mock('@/app/(dashboard)/clients/components/client-form-dialog', () => ({
  ClientFormDialog: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}))

jest.mock('@/app/(dashboard)/clients/components/success-alert', () => ({
  SuccessAlert: ({ open, title, message, onOpenChange }: any) => (
    open ? (
      <div role="alert">
        <h2>{title}</h2>
        <p>{message}</p>
        <button onClick={() => onOpenChange(false)}>OK</button>
      </div>
    ) : null
  ),
}))

jest.mock('@/components/ui/error-toast', () => ({
  showErrorToast: jest.fn(),
  showSuccessToast: jest.fn(),
}))

const { useAuthenticatedClients, useAuthenticatedClientStats, useMutation } =
  jest.requireMock('@/hooks/use-authenticated-api')

describe('ClientsPage - conflito de exclusão', () => {
  beforeEach(() => {
    jest.clearAllMocks()

    ;(useAuthenticatedClientStats as jest.Mock).mockReturnValue({
      data: {
        total_clients: { current: 1, previous: 0, growth: 0 },
        active_clients: { current: 1, previous: 0, growth: 0 },
        orders_per_client: { current: 1, previous: 0, growth: 0 },
        new_clients: { current: 0, previous: 0, growth: 0 },
      },
      loading: false,
      error: null,
      refetch: jest.fn(),
      isAuthenticated: true,
    })

    ;(useMutation as jest.Mock).mockReturnValue({
      mutate: jest.fn(),
      loading: false,
      error: null,
    })
  })

  it('renderiza a lista de clientes mockados', async () => {
    const refetch = jest.fn()
    ;(useAuthenticatedClients as jest.Mock).mockReturnValue({
      data: [
        {
          id: 1,
          name: 'Cliente Teste',
          cpf: '12345678900',
          email: 'cliente@test.com',
          phone: '11999999999',
          address: 'Rua A',
          city: 'São Paulo',
          state: 'SP',
          zip_code: '01000000',
          neighborhood: 'Centro',
          number: '100',
          complement: '',
          full_address: 'Rua A, 100 - Centro',
          has_complete_address: true,
          total_orders: 1,
          last_order: '07/11/2025',
          last_order_raw: new Date().toISOString(),
          is_active: true,
          created_at: new Date().toISOString(),
          created_at_formatted: '07/11/2025',
          updated_at: new Date().toISOString(),
        },
      ],
      loading: false,
      error: null,
      refetch,
      isAuthenticated: true,
    })

    render(<ClientsPage />)

    await waitFor(() => {
      expect(screen.getByText('Cliente Teste')).toBeInTheDocument()
    })
  })
})
