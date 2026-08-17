import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ExpenseFormDialog } from '../components/expense-form-dialog'

const categories = [
  { id: 1, name: 'Operacional', type: 'despesa', color: '#000', is_active: true },
]

const suppliers = [
  { id: 1, name: 'Fornecedor A', document: '123', is_active: true },
]

describe('ExpenseFormDialog - wizard de passos', () => {
  const setup = (overrides: Partial<Parameters<typeof ExpenseFormDialog>[0]> = {}) => {
    const onSubmit = jest.fn().mockResolvedValue(undefined)
    const onOpenChange = jest.fn()
    render(
      <ExpenseFormDialog
        open={true}
        onOpenChange={onOpenChange}
        expense={null}
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
  })

  test('não avança sem descrição obrigatória', async () => {
    const user = userEvent.setup()
    setup()
    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    expect(await screen.findByText(/A descrição é obrigatória/i)).toBeInTheDocument()
  })

  test('avança até revisão sem submeter prematuramente', async () => {
    const user = userEvent.setup()
    const { onSubmit } = setup()

    await user.type(screen.getByLabelText(/Descrição/i), 'Aluguel')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByLabelText(/Valor/i)).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()

    await user.clear(screen.getByLabelText(/Valor/i))
    await user.type(screen.getByLabelText(/Valor/i), '1200')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/Resumo da despesa/i)).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  test('submete apenas na última etapa', async () => {
    const user = userEvent.setup()
    const { onSubmit } = setup()

    await user.type(screen.getByLabelText(/Descrição/i), 'Energia')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    await screen.findByLabelText(/Valor/i)
    await user.clear(screen.getByLabelText(/Valor/i))
    await user.type(screen.getByLabelText(/Valor/i), '250')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    await screen.findByText(/Resumo da despesa/i)

    await user.click(screen.getByRole('button', { name: /^Criar$/i }))

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          description: 'Energia',
          amount: 250,
          status: 'pendente',
        })
      )
    })
  })
})
