import { render, screen, waitFor } from '@testing-library/react'
import TablesPage from '@/app/(dashboard)/tables/page'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    token: 'mock-token',
    isAuthenticated: true,
    isLoading: false,
    user: { name: 'Test User', email: 'test@test.com' },
  }),
}))

jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedTables: jest.fn(),
  useMutation: jest.fn(() => ({ mutate: jest.fn(), loading: false, error: null })),
}))

jest.mock('@/app/(dashboard)/tables/components/stat-cards', () => ({
  StatCards: () => <div data-testid="stat-cards" />,
}))

jest.mock('@/app/(dashboard)/tables/components/table-form-dialog', () => ({
  TableFormDialog: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}))

jest.mock('@/app/(dashboard)/tables/components/success-alert', () => ({
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

const { useAuthenticatedTables, useMutation } = jest.requireMock('@/hooks/use-authenticated-api')

describe('TablesPage - conflito de exclusão', () => {
  beforeEach(() => {
    jest.clearAllMocks()

    ;(useMutation as jest.Mock).mockReturnValue({
      mutate: jest.fn(),
      loading: false,
      error: null,
    })
  })

  it('renderiza a lista de mesas mockadas', async () => {
    const refetch = jest.fn()
    ;(useAuthenticatedTables as jest.Mock).mockReturnValue({
      data: [
        {
          id: 1,
          uuid: 'table-uuid',
          name: 'Mesa 1',
          identify: 'T1',
          capacity: 4,
          description: 'Perto da janela',
          created_at: new Date().toISOString(),
          created_at_formatted: '07/11/2025',
        },
      ],
      loading: false,
      error: null,
      refetch,
      isAuthenticated: true,
    })

    render(<TablesPage />)

    await waitFor(() => {
      expect(screen.getByText('Mesa 1')).toBeInTheDocument()
    })
  })
})
