import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AccountReceivableFormDialog } from '../components/account-receivable-form-dialog'

jest.mock('@/lib/api-client', () => ({
  apiClient: {
    get: jest.fn(),
  },
  endpoints: {
    orders: {
      searchByNumber: '/api/orders/search',
      getDetails: (id: number) => `/api/order/${id}`,
    },
  },
}))

const categories = [
  { id: 1, name: 'Vendas', type: 'receita', color: '#000', is_active: true },
]

describe('AccountReceivableFormDialog - wizard de passos', () => {
  const setup = (
    overrides: Partial<Parameters<typeof AccountReceivableFormDialog>[0]> = {}
  ) => {
    const onSubmit = jest.fn().mockResolvedValue(undefined)
    const onOpenChange = jest.fn()
    render(
      <AccountReceivableFormDialog
        open={true}
        onOpenChange={onOpenChange}
        account={null}
        categories={categories as any}
        onSubmit={onSubmit}
        {...overrides}
      />
    )
    return { onSubmit, onOpenChange }
  }

  test('renderiza apenas campos da etapa 1 ao abrir', () => {
    setup()

    expect(screen.getByLabelText(/Descrição/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Número do Pedido/i)).toBeInTheDocument()
    expect(screen.queryByLabelText(/Data de Emissão/i)).not.toBeInTheDocument()
    expect(screen.queryByText(/Resumo da conta/i)).not.toBeInTheDocument()
  })

  test('não avança sem descrição obrigatória', async () => {
    const user = userEvent.setup()
    setup()

    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/Descrição é obrigatória/i)).toBeInTheDocument()
    expect(screen.queryByLabelText(/Data de Emissão/i)).not.toBeInTheDocument()
  })

  test('avança pelas etapas e submete na revisão', async () => {
    const user = userEvent.setup()
    const { onSubmit } = setup()

    await user.type(screen.getByLabelText(/Descrição/i), 'Venda balcão')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByLabelText(/Valor/i)).toBeInTheDocument()
    await user.clear(screen.getByLabelText(/Valor/i))
    await user.type(screen.getByLabelText(/Valor/i), '99.90')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/Resumo da conta/i)).toBeInTheDocument()
    expect(screen.getByText('Venda balcão')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /^Criar$/i }))

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        description: 'Venda balcão',
        amount: 99.9,
        status: 'pendente',
      })
    )
  })

  test('voltar preserva dados da etapa anterior', async () => {
    const user = userEvent.setup()
    setup()

    await user.type(screen.getByLabelText(/Descrição/i), 'Serviço')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByLabelText(/Data de Emissão/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Voltar/i }))

    expect(await screen.findByLabelText(/Descrição/i)).toHaveValue('Serviço')
  })
})
