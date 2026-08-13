import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AccountPayableFormDialog } from '../components/account-payable-form-dialog'

const categories = [
  { id: 1, name: 'Aluguel', type: 'despesa', color: '#000', is_active: true },
]

const suppliers = [
  { id: 1, name: 'Fornecedor A', document: '123', is_active: true },
]

describe('AccountPayableFormDialog - wizard de passos', () => {
  const setup = (
    overrides: Partial<Parameters<typeof AccountPayableFormDialog>[0]> = {}
  ) => {
    const onSubmit = jest.fn().mockResolvedValue(undefined)
    const onOpenChange = jest.fn()
    render(
      <AccountPayableFormDialog
        open={true}
        onOpenChange={onOpenChange}
        account={null}
        categories={categories as any}
        suppliers={suppliers as any}
        onSubmit={onSubmit}
        {...overrides}
      />
    )
    return { onSubmit, onOpenChange }
  }

  test('renderiza apenas campos da etapa 1 ao abrir', () => {
    setup()

    expect(screen.getByLabelText(/Descrição/i)).toBeInTheDocument()
    expect(screen.getByText(/Informações Básicas/i)).toBeInTheDocument()
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

    await user.type(screen.getByLabelText(/Descrição/i), 'Conta de luz')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByLabelText(/Data de Emissão/i)).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()

    await user.clear(screen.getByLabelText(/Valor/i))
    await user.type(screen.getByLabelText(/Valor/i), '150.50')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/Resumo da conta/i)).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
    expect(screen.getByText('Conta de luz')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /^Criar$/i }))

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        description: 'Conta de luz',
        amount: 150.5,
        status: 'pendente',
      })
    )
  })

  test('voltar preserva dados da etapa anterior', async () => {
    const user = userEvent.setup()
    setup()

    await user.type(screen.getByLabelText(/Descrição/i), 'Aluguel')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByLabelText(/Data de Emissão/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Voltar/i }))

    expect(await screen.findByLabelText(/Descrição/i)).toHaveValue('Aluguel')
  })
})
