import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import '@testing-library/jest-dom'
import ProductsPage from '../../products/page'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    token: 'mock-token',
    isAuthenticated: true,
    isLoading: false,
    user: { name: 'Test User', email: 'test@test.com' },
  }),
}))

jest.mock('@/hooks/use-authenticated-api')

import { useAuthenticatedProducts, useMutation, useAuthenticatedProductStats } from '@/hooks/use-authenticated-api'

describe('Products delete rules - frontend feedback', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(useAuthenticatedProducts as jest.Mock).mockReturnValue({
      data: [
        {
          id: 1,
          name: 'Produto A',
          description: 'Desc',
          price: 10,
          categories: [],
          price_cost: 5,
          qtd_stock: 10,
          is_active: true,
          created_at: '',
          createdAt: ''
        }
      ],
      loading: false,
      error: null,
      refetch: jest.fn(),
      isAuthenticated: true,
    })

    ;(useAuthenticatedProductStats as jest.Mock).mockReturnValue({
      data: { total: 1, active: 1, inactive: 0, out_of_stock: 0 },
      loading: false,
      error: null,
    })
  })

  it('exibe mensagem detalhada quando backend retorna pedidos em preparo ao excluir produto', async () => {
    const mockReject = jest.fn().mockRejectedValue({
      data: {
        message: 'Não é possível excluir: produto presente no pedido #FRRJRGED com status Em Preparo',
        errors: { orders_in_preparing: ['FRRJRGED', 'ABCD1234'] }
      }
    })
    ;(useMutation as jest.Mock)
      .mockReturnValueOnce({ mutate: jest.fn(), loading: false, error: null })
      .mockReturnValueOnce({ mutate: mockReject, loading: false, error: null })

    render(<ProductsPage />)

    const user = userEvent.setup()

    // Abrir menu de ações da linha
    const actionMenus = await screen.findAllByRole('button', { name: /Abrir menu/i })
    await user.click(actionMenus[0])

    // Abrir dialog de exclusão
    const excluirOption = await screen.findByText('Excluir')
    await user.click(excluirOption)

    const confirmBtns = await screen.findAllByRole('button', { name: 'Excluir Produto' })
    await user.click(confirmBtns[0])

    await waitFor(() => {
      expect(mockReject).toHaveBeenCalled()
    })

    // Verifica alerta com a mensagem do backend e a lista dos pedidos
    await screen.findByText(/Não é possível excluir: produto presente no pedido/i)
    expect(screen.getByText(/Pedidos: FRRJRGED, ABCD1234/)).toBeInTheDocument()
  })
})
