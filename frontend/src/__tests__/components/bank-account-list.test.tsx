import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { BankAccountList } from '@/app/(dashboard)/contas-bancarias/components/bank-account-list'
import { BankAccount } from '@/types/bank-account'

const mockAccounts: BankAccount[] = [
  {
    uuid: '123-456',
    account_type: 'checking',
    bank_name: 'Banco do Brasil',
    bank_code: '001',
    agency: '1234',
    agency_digit: '5',
    account_number_masked: '12****78-9',
    account_holder_name: 'Restaurant ABC Ltda',
    account_holder_document_masked: '12.***.***/****-01',
    account_holder_type: 'company',
    pix_key_type: 'cnpj',
    pix_key: '12.345.678/0001-01',
    is_primary: true,
    is_active: true,
    is_verified: true,
    verified_at: '2025-11-01T10:00:00Z',
    created_at: '2025-11-01T10:00:00Z',
  },
  {
    uuid: '789-012',
    account_type: 'savings',
    bank_name: 'Nubank',
    bank_code: '260',
    agency: '0001',
    agency_digit: null,
    account_number_masked: '98****56-4',
    account_holder_name: 'Restaurant ABC Ltda',
    account_holder_document_masked: '12.***.***/****-01',
    account_holder_type: 'company',
    pix_key_type: 'email',
    pix_key: 'financeiro@restaurant.com',
    is_primary: false,
    is_active: true,
    is_verified: false,
    verified_at: null,
    created_at: '2025-11-02T10:00:00Z',
  },
]

describe('BankAccountList', () => {
  const mockOnEdit = jest.fn()
  const mockOnDelete = jest.fn()
  const mockOnSetPrimary = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renders all accounts', () => {
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    expect(screen.getByText('Banco do Brasil')).toBeInTheDocument()
    expect(screen.getByText('Nubank')).toBeInTheDocument()
  })

  it('displays primary badge for primary account', () => {
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    const primaryBadge = screen.getByText('Principal')
    expect(primaryBadge).toBeInTheDocument()
  })

  it('displays verified status', () => {
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    expect(screen.getByText('Verificada')).toBeInTheDocument()
    expect(screen.getByText('Pendente')).toBeInTheDocument()
  })

  it('displays masked account numbers', () => {
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    expect(screen.getByText(/12\*\*\*\*78-9/)).toBeInTheDocument()
    expect(screen.getByText(/98\*\*\*\*56-4/)).toBeInTheDocument()
  })

  it('displays PIX keys', () => {
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    expect(screen.getByText('12.345.678/0001-01')).toBeInTheDocument()
    expect(screen.getByText('financeiro@restaurant.com')).toBeInTheDocument()
  })

  it('shows "Tornar Principal" button for non-primary accounts', () => {
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    expect(screen.getByText('Tornar Principal')).toBeInTheDocument()
  })

  it('does not show delete button for primary account', () => {
    render(
      <BankAccountList
        accounts={[mockAccounts[0]]}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    const deleteButtons = screen.queryAllByTitle('Excluir conta')
    expect(deleteButtons).toHaveLength(0)
  })

  it('calls onEdit when edit button clicked', async () => {
    const user = userEvent.setup()
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    const editButtons = screen.getAllByTitle('Editar conta')
    await user.click(editButtons[0])

    expect(mockOnEdit).toHaveBeenCalledWith(mockAccounts[0])
  })

  it('calls onDelete when delete button clicked', async () => {
    const user = userEvent.setup()
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    const deleteButton = screen.getByTitle('Excluir conta')
    await user.click(deleteButton)

    expect(mockOnDelete).toHaveBeenCalledWith(mockAccounts[1])
  })

  it('calls onSetPrimary when set primary button clicked', async () => {
    const user = userEvent.setup()
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    const setPrimaryButton = screen.getByText('Tornar Principal')
    await user.click(setPrimaryButton)

    expect(mockOnSetPrimary).toHaveBeenCalledWith(mockAccounts[1])
  })

  it('displays account type badge', () => {
    render(
      <BankAccountList
        accounts={mockAccounts}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    expect(screen.getByText('Corrente')).toBeInTheDocument()
    expect(screen.getByText('Poupança')).toBeInTheDocument()
  })

  it('shows inactive badge for inactive accounts', () => {
    const inactiveAccount = { ...mockAccounts[0], is_active: false }
    
    render(
      <BankAccountList
        accounts={[inactiveAccount]}
        onEdit={mockOnEdit}
        onDelete={mockOnDelete}
        onSetPrimary={mockOnSetPrimary}
      />
    )

    expect(screen.getByText('Inativa')).toBeInTheDocument()
  })
})

