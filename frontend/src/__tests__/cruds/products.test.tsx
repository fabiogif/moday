import { screen, waitFor } from '@testing-library/react'
import { render, generateProduct } from '../utils/test-utils'
import ProductsPage from '@/app/(dashboard)/products/page'
import {
  useAuthenticatedProducts,
  useMutation,
  useMutationWithValidation,
} from '@/hooks/use-authenticated-api'
import { useAuth } from '@/contexts/auth-context'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: jest.fn(),
}))

const mockUseAuth = useAuth as jest.MockedFunction<typeof useAuth>
const mockUseAuthenticatedProducts = useAuthenticatedProducts as jest.MockedFunction<
  typeof useAuthenticatedProducts
>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>
const mockUseMutationWithValidation = useMutationWithValidation as jest.MockedFunction<
  typeof useMutationWithValidation
>

describe('Products CRUD', () => {
  const mockProducts = [
    generateProduct({ id: 1, name: 'iPhone 14' }),
    generateProduct({ id: 2, name: 'Samsung Galaxy' }),
  ]

  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuth.mockReturnValue({
      isLoading: false,
      isAuthenticated: true,
    } as ReturnType<typeof useAuth>)

    mockUseAuthenticatedProducts.mockReturnValue({
      data: mockProducts,
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

    mockUseMutationWithValidation.mockReturnValue({
      mutate: jest.fn().mockResolvedValue({}),
      loading: false,
      error: null,
    })
  })

  it('should render products list', async () => {
    render(<ProductsPage />)

    await waitFor(() => {
      expect(screen.getByText('iPhone 14')).toBeInTheDocument()
      expect(screen.getByText('Samsung Galaxy')).toBeInTheDocument()
    })
  })

  it('should display loading state', () => {
    mockUseAuthenticatedProducts.mockReturnValue({
      data: [],
      loading: true,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<ProductsPage />)
    expect(screen.getByText('Carregando produtos...')).toBeInTheDocument()
  })

  it('should display error state', () => {
    mockUseAuthenticatedProducts.mockReturnValue({
      data: null,
      loading: false,
      error: 'Falha na API',
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<ProductsPage />)
    expect(screen.getByText('Erro ao carregar produtos: Falha na API')).toBeInTheDocument()
  })

  it('should show authentication error when not authenticated', () => {
    mockUseAuth.mockReturnValue({
      isLoading: false,
      isAuthenticated: false,
    } as ReturnType<typeof useAuth>)

    mockUseAuthenticatedProducts.mockReturnValue({
      data: [],
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: false,
    })

    render(<ProductsPage />)
    expect(
      screen.getByText('Usuário não autenticado. Faça login para continuar.')
    ).toBeInTheDocument()
  })
})
