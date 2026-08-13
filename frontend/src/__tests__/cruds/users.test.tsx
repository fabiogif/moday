import { screen, waitFor } from '@testing-library/react'
import { render, generateUser } from '../utils/test-utils'
import UsersPage from '@/app/(dashboard)/users/page'
import { useAuthenticatedUsers, useMutation } from '@/hooks/use-authenticated-api'

const mockUseAuthenticatedUsers = useAuthenticatedUsers as jest.MockedFunction<typeof useAuthenticatedUsers>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

describe('Users CRUD', () => {
  const mockUsers = [
    generateUser({ id: 1, name: 'John Doe', email: 'john@example.com' }),
    generateUser({ id: 2, name: 'Jane Smith', email: 'jane@example.com' }),
  ]

  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuthenticatedUsers.mockReturnValue({
      data: { users: mockUsers },
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
      pagination: { current_page: 1, last_page: 1, per_page: 15, total: 2 },
    })

    mockUseMutation.mockReturnValue({
      mutate: jest.fn().mockResolvedValue({}),
      loading: false,
      error: null,
    })
  })

  it('should render users list', async () => {
    render(<UsersPage />)

    await waitFor(() => {
      expect(screen.getByText('John Doe')).toBeInTheDocument()
      expect(screen.getByText('Jane Smith')).toBeInTheDocument()
    })
  })

  it('should display loading state', () => {
    mockUseAuthenticatedUsers.mockReturnValue({
      data: null,
      loading: true,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<UsersPage />)
    expect(screen.getByText(/Carregando/i)).toBeInTheDocument()
  })

  it('should display error state', () => {
    mockUseAuthenticatedUsers.mockReturnValue({
      data: null,
      loading: false,
      error: 'Falha na API',
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<UsersPage />)
    expect(screen.getByText('Erro ao carregar usuários')).toBeInTheDocument()
    expect(screen.getByText('Falha na API')).toBeInTheDocument()
  })

  it('should render page title', () => {
    render(<UsersPage />)
    expect(screen.getByText('Usuários')).toBeInTheDocument()
  })
})
