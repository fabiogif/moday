import { screen, waitFor } from '@testing-library/react'
import { render, generatePermission } from '../utils/test-utils'
import PermissionsPage from '@/app/(dashboard)/permissions/page'
import { useAuthenticatedPermissions, useMutation } from '@/hooks/use-authenticated-api'

const mockUseAuthenticatedPermissions = useAuthenticatedPermissions as jest.MockedFunction<
  typeof useAuthenticatedPermissions
>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

describe('Permissions CRUD', () => {
  const mockPermissions = [
    generatePermission({ id: 1, name: 'Criar Usuários', slug: 'create-users' }),
    generatePermission({ id: 2, name: 'Editar Produtos', slug: 'edit-products' }),
    generatePermission({ id: 3, name: 'Excluir Pedidos', slug: 'delete-orders' }),
  ]

  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuthenticatedPermissions.mockReturnValue({
      data: mockPermissions,
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    mockUseMutation.mockReturnValue({
      mutate: jest.fn().mockResolvedValue({}),
      loading: false,
      error: null,
    })
  })

  it('should render permissions list', async () => {
    render(<PermissionsPage />)

    await waitFor(() => {
      expect(screen.getByText('Criar Usuários')).toBeInTheDocument()
      expect(screen.getByText('Editar Produtos')).toBeInTheDocument()
      expect(screen.getByText('Excluir Pedidos')).toBeInTheDocument()
    })
  })

  it('should display loading state', () => {
    mockUseAuthenticatedPermissions.mockReturnValue({
      data: [],
      loading: true,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<PermissionsPage />)
    expect(screen.getByText('Carregando página...')).toBeInTheDocument()
  })

  it('should display error state', () => {
    mockUseAuthenticatedPermissions.mockReturnValue({
      data: null,
      loading: false,
      error: 'Falha na API',
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<PermissionsPage />)
    expect(screen.getByText('Erro ao carregar permissões')).toBeInTheDocument()
    expect(screen.getByText('Falha na API')).toBeInTheDocument()
  })

  it('should render page title', () => {
    render(<PermissionsPage />)
    expect(screen.getByRole('heading', { name: 'Permissões' })).toBeInTheDocument()
  })
})
