import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import ProductsPage from '@/app/(dashboard)/products/page'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    token: 'mock-token',
    isAuthenticated: true,
    isLoading: false,
    user: { name: 'Test User', email: 'test@test.com' },
  }),
}))

jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedProducts: jest.fn(),
  useMutation: jest.fn(() => ({ mutate: jest.fn(), loading: false, error: null })),
}))

jest.mock('@/app/(dashboard)/products/components/product-stat-cards', () => ({
  ProductStatCards: () => <div data-testid="product-stat-cards" />,
}))

jest.mock('@/app/(dashboard)/products/components/success-alert', () => ({
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

jest.mock('@/app/(dashboard)/products/components/data-table', () => ({
  DataTable: ({ onDeleteProduct }: any) => (
    <button onClick={() => onDeleteProduct(1)} data-testid="delete-product">
      Excluir Produto
    </button>
  ),
}))

const { useAuthenticatedProducts, useMutation } = jest.requireMock('@/hooks/use-authenticated-api')

describe('ProductsPage - conflito de exclusão', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('exibe alerta amigável quando exclusão retorna 409', async () => {
    const refetch = jest.fn()
    ;(useAuthenticatedProducts as jest.Mock).mockReturnValue({
      data: [],
      loading: false,
      error: null,
      refetch,
      isAuthenticated: true,
    })

    const mutateCreate = jest.fn()
    const mutateDelete = jest.fn().mockRejectedValue({
      status: 409,
      message: 'Produto não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.',
      data: {},
    })

    ;(useMutation as jest.Mock)
      .mockReturnValueOnce({ mutate: mutateCreate, loading: false })
      .mockReturnValueOnce({ mutate: mutateDelete, loading: false })

    render(<ProductsPage />)

    const user = userEvent.setup()
    await user.click(screen.getByTestId('delete-product'))

    await waitFor(() => {
      expect(screen.getByText('Atenção!')).toBeInTheDocument()
      expect(
        screen.getByText('Produto não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.')
      ).toBeInTheDocument()
    })
  })
})


