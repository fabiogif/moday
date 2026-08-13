import { screen, waitFor } from '@testing-library/react'
import { render, generateCategory } from '../utils/test-utils'
import CategoriesPage from '@/app/(dashboard)/categories/page'
import {
  useAuthenticatedCategories,
  useAuthenticatedCategoryStats,
  useMutation,
} from '@/hooks/use-authenticated-api'
import { useAuth } from '@/contexts/auth-context'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: jest.fn(),
}))

const mockUseAuth = useAuth as jest.MockedFunction<typeof useAuth>
const mockUseAuthenticatedCategories = useAuthenticatedCategories as jest.MockedFunction<
  typeof useAuthenticatedCategories
>
const mockUseAuthenticatedCategoryStats =
  useAuthenticatedCategoryStats as jest.MockedFunction<typeof useAuthenticatedCategoryStats>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

const defaultCategoryStats = {
  total_categories: 2,
  active_categories: 2,
  inactive_categories: 0,
  avg_products_per_category: 5,
  total_products: 10,
}

describe('Categories CRUD', () => {
  const mockCategories = [
    generateCategory({ id: 1, name: 'Electronics' }),
    generateCategory({ id: 2, name: 'Clothing' }),
  ]

  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuth.mockReturnValue({
      isLoading: false,
      isAuthenticated: true,
    } as ReturnType<typeof useAuth>)

    mockUseAuthenticatedCategories.mockReturnValue({
      data: mockCategories,
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    mockUseAuthenticatedCategoryStats.mockReturnValue({
      data: defaultCategoryStats,
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

  it('should render categories list', async () => {
    render(<CategoriesPage />)

    await waitFor(() => {
      expect(screen.getByText('Electronics')).toBeInTheDocument()
      expect(screen.getByText('Clothing')).toBeInTheDocument()
    })
  })

  it('should display loading state', () => {
    mockUseAuthenticatedCategories.mockReturnValue({
      data: [],
      loading: true,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<CategoriesPage />)
    expect(screen.getByText('Carregando categorias...')).toBeInTheDocument()
  })

  it('should display error state', () => {
    mockUseAuthenticatedCategories.mockReturnValue({
      data: null,
      loading: false,
      error: 'Falha na API',
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<CategoriesPage />)
    expect(screen.getByText('Erro ao carregar categorias: Falha na API')).toBeInTheDocument()
  })

  it('should show authentication error when not authenticated', () => {
    mockUseAuth.mockReturnValue({
      isLoading: false,
      isAuthenticated: false,
    } as ReturnType<typeof useAuth>)

    mockUseAuthenticatedCategories.mockReturnValue({
      data: [],
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: false,
    })

    render(<CategoriesPage />)
    expect(
      screen.getByText('Usuário não autenticado. Faça login para continuar.')
    ).toBeInTheDocument()
  })
})
