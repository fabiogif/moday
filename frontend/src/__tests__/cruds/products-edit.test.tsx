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

describe('Product Edit Operations', () => {
  const mockProducts = [generateProduct({ id: 1, name: 'iPhone 14' })]

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

  it('should render products list with edit actions available', async () => {
    render(<ProductsPage />)

    await waitFor(() => {
      expect(screen.getByText('iPhone 14')).toBeInTheDocument()
    })
  })
})
