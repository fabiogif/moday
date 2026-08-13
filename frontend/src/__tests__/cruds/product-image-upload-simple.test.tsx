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

describe('Product Image Upload - Simplified', () => {
  const mockProducts = [generateProduct({ id: 1, name: 'Produto Teste' })]

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
      refetch: jest.fn(),
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

  it('should load products page without errors', async () => {
    render(<ProductsPage />)

    await waitFor(() => {
      expect(screen.getByText('Produto Teste')).toBeInTheDocument()
    })
  })
})
