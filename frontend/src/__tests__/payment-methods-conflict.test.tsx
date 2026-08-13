import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import PaymentMethodsPage from '@/app/(dashboard)/payment-methods/page'

jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedPaymentMethods: jest.fn(),
  useMutation: jest.fn(),
}))

jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
    info: jest.fn(),
  },
}))

jest.mock('@/app/(dashboard)/payment-methods/components/payment-method-form-dialog', () => ({
  PaymentMethodFormDialog: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}))

const { useAuthenticatedPaymentMethods, useMutation } = jest.requireMock('@/hooks/use-authenticated-api')
const { toast } = jest.requireMock('sonner')

describe('PaymentMethodsPage - conflito de exclusão', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('exibe mensagem amigável quando exclusão retorna 409', async () => {
    const refetch = jest.fn()
    ;(useAuthenticatedPaymentMethods as jest.Mock).mockReturnValue({
      data: [
        {
          uuid: 'pm-uuid',
          name: 'Cartão',
          description: 'Cartão de crédito',
          is_active: true,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
      ],
      loading: false,
      error: null,
      refetch,
      isAuthenticated: true,
    })

    const mutateCreate = jest.fn()
    const mutateUpdate = jest.fn()
    const mutateDelete = jest.fn().mockRejectedValue({
      status: 409,
      message: 'Forma de pagamento não pode ser excluída, existe um pedido ativo ou não arquivado vinculado.',
    })

    ;(useMutation as jest.Mock)
      .mockReturnValueOnce({ mutate: mutateCreate, loading: false })
      .mockReturnValueOnce({ mutate: mutateUpdate, loading: false })
      .mockReturnValueOnce({ mutate: mutateDelete, loading: false })

    render(<PaymentMethodsPage />)

    const user = userEvent.setup()

    await user.click(screen.getByRole('button', { name: /abrir menu/i }))
    await user.click(screen.getByText('Excluir'))
    await user.click(screen.getByRole('button', { name: /excluir/i }))

    await waitFor(() => {
      expect(toast.error).toHaveBeenCalledWith(
        'Forma de pagamento não pode ser excluída, existe um pedido ativo ou não arquivado vinculado.'
      )
    })
  })
})


