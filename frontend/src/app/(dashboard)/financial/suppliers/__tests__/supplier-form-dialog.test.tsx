import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { SupplierFormDialog } from '../components/supplier-form-dialog'

jest.mock('@/hooks/use-viacep', () => ({
  useViaCEP: () => ({ loading: false, searchCEP: jest.fn() }),
}))

jest.mock('@/hooks/use-receitaws', () => ({
  useReceitaWS: () => ({ loading: false, companyData: null, searchCNPJ: jest.fn() }),
}))

describe('SupplierFormDialog - wizard de passos', () => {
  const setup = (overrides: Partial<Parameters<typeof SupplierFormDialog>[0]> = {}) => {
    const onSubmit = jest.fn().mockResolvedValue(undefined)
    const onOpenChange = jest.fn()
    render(
      <SupplierFormDialog
        open={true}
        onOpenChange={onOpenChange}
        supplier={null}
        onSubmit={onSubmit}
        {...overrides}
      />
    )
    return { onSubmit, onOpenChange }
  }

  test('renders only step 1 fields on open', () => {
    setup()

    expect(screen.getByLabelText(/Nome\/Razão Social/i)).toBeInTheDocument()
    expect(screen.queryByLabelText(/Telefone \*/i)).not.toBeInTheDocument()
    expect(screen.queryByLabelText(/Logradouro/i)).not.toBeInTheDocument()
    expect(screen.getByText(/Digite o CNPJ para buscar dados da empresa/i)).toBeInTheDocument()
  })

  test('does not advance to step 2 when required fields are empty', async () => {
    const user = userEvent.setup()
    setup()

    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/O nome é obrigatório/i)).toBeInTheDocument()
    expect(screen.queryByLabelText(/Telefone \*/i)).not.toBeInTheDocument()
  })

  test('advances through all steps and submits collected data', async () => {
    const user = userEvent.setup()
    const { onSubmit } = setup()

    // Passo 1: Dados Principais
    await user.type(screen.getByLabelText(/Nome\/Razão Social/i), 'Fornecedor Teste Ltda')
    await user.type(screen.getByLabelText(/^CNPJ \*/i), '12.345.678/0001-90')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 2: Contato
    expect(await screen.findByLabelText(/Telefone \*/i)).toBeInTheDocument()
    await user.type(screen.getByLabelText(/Telefone \*/i), '11987654321')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 3: Endereço (opcional, avança sem preencher)
    expect(await screen.findByLabelText(/CEP/i)).toBeInTheDocument()
    expect(screen.getByText(/Digite o CEP para preencher automaticamente/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 4: Financeiro — botão final
    expect(await screen.findByLabelText(/Chave PIX/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Criar Fornecedor/i }))

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          name: 'Fornecedor Teste Ltda',
          document: '12.345.678/0001-90',
          phone: '(11) 98765-4321',
        })
      )
    })
  })

  test('goes back to the previous step preserving data', async () => {
    const user = userEvent.setup()
    setup()

    await user.type(screen.getByLabelText(/Nome\/Razão Social/i), 'Fornecedor X')
    await user.type(screen.getByLabelText(/^CNPJ \*/i), '11.111.111/0001-11')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByLabelText(/Telefone \*/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Voltar/i }))

    const nameInput = await screen.findByLabelText(/Nome\/Razão Social/i)
    expect(nameInput).toHaveValue('Fornecedor X')
  })
})
