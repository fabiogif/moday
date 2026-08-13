import { render, screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { BankAccountForm } from '@/app/(dashboard)/contas-bancarias/components/bank-account-form'
import api from '@/lib/api-client'
import { toast } from 'sonner'

jest.mock('@/lib/api-client', () => {
  const { endpoints } = jest.requireActual('@/lib/api-client')
  return {
    __esModule: true,
    endpoints,
    default: {
      get: jest.fn(),
      post: jest.fn(),
      put: jest.fn(),
      delete: jest.fn(),
    },
  }
})
jest.mock('sonner')

const mockBanks = [
  { code: '001', name: 'Banco do Brasil', full_name: 'Banco do Brasil S.A.', supports_pix: true },
  { code: '260', name: 'Nubank', full_name: 'Nu Pagamentos S.A.', supports_pix: true },
]

const mockAccounts = [
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
]

describe('BankAccountForm', () => {
  const mockOnClose = jest.fn()
  const mockOnSuccess = jest.fn()

  const waitForBanksToLoad = async () => {
    await waitFor(() => {
      expect(screen.getByText('Selecione o banco')).toBeInTheDocument()
    })
  }

  const getBankSelect = () =>
    screen.getByText('Selecione o banco').closest('button')!

  const fillNewAccountForm = async (user: ReturnType<typeof userEvent.setup>) => {
    await waitForBanksToLoad()
    fireEvent.click(getBankSelect())
    fireEvent.click(screen.getByRole('option', { name: '001 - Banco do Brasil' }))
    fireEvent.change(screen.getByPlaceholderText('0001'), { target: { value: '1234' } })
    fireEvent.change(screen.getByPlaceholderText('12345678'), { target: { value: '87654321' } })
    fireEvent.change(screen.getAllByPlaceholderText('0')[1], { target: { value: '9' } })
    fireEvent.change(screen.getByPlaceholderText(/Nome completo/), { target: { value: 'Restaurant ABC' } })
    fireEvent.change(screen.getByPlaceholderText(/00.000/), { target: { value: '12345678000101' } })
  }

  beforeEach(() => {
    jest.clearAllMocks()
    ;(api.get as jest.Mock).mockResolvedValue({
      success: true,
      data: mockBanks,
    })
  })

  it('renders form title for new account', () => {
    render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    expect(screen.getByText('Nova Conta Bancária')).toBeInTheDocument()
  })

  it('renders form title for editing account', () => {
    const mockAccount = {
      uuid: '123',
      account_type: 'checking',
      bank_name: 'Banco do Brasil',
      is_primary: false,
    } as any

    render(
      <BankAccountForm
        open={true}
        account={mockAccount}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    expect(screen.getByText('Editar Conta Bancária')).toBeInTheDocument()
  })

  it('loads banks on mount', async () => {
    render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    await waitFor(() => {
      expect(api.get).toHaveBeenCalledWith('/api/bank-accounts/banks')
    })
  })

  it('validates required fields', async () => {
    render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    await waitFor(() => {
      expect(screen.getByText('Cadastrar')).toBeInTheDocument()
    })

    fireEvent.submit(screen.getByRole('button', { name: 'Cadastrar' }).closest('form')!)

    await waitFor(() => {
      expect(toast.error).toHaveBeenCalledWith('Selecione um banco')
    })
  })

  it('submits form with valid data', async () => {
    const user = userEvent.setup()
    ;(api.post as jest.Mock).mockResolvedValue({ success: true })

    render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    await fillNewAccountForm(user)
    await user.click(screen.getByText('Cadastrar'))

    await waitFor(
      () => {
        expect(api.post).toHaveBeenCalledWith(
          '/api/bank-accounts',
          expect.objectContaining({
            bank_code: '001',
            agency: '1234',
            account_number: '87654321',
            account_digit: '9',
          })
        )
        expect(toast.success).toHaveBeenCalledWith('Conta bancária cadastrada com sucesso!')
        expect(mockOnSuccess).toHaveBeenCalled()
      },
      { timeout: 10000 }
    )
  }, 15000)

  it('formats CPF/CNPJ with mask', async () => {
    render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    await waitFor(() => {
      expect(screen.getByPlaceholderText(/00.000/)).toBeInTheDocument()
    })

    const docInput = screen.getByPlaceholderText(/00.000/)
    fireEvent.change(docInput, { target: { value: '12345678000101' } })

    expect(docInput).toHaveValue('12.345.678/0001-01')
  })

  it('disables PIX key input when type not selected', () => {
    render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    const pixKeyInput = screen.getByPlaceholderText('Digite a chave PIX')
    expect(pixKeyInput).toBeDisabled()
  })

  it('calls onClose when cancel button clicked', async () => {
    const user = userEvent.setup()
    render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    const cancelButton = screen.getByText('Cancelar')
    await user.click(cancelButton)

    expect(mockOnClose).toHaveBeenCalled()
  })

  it('handles API error gracefully', async () => {
    const user = userEvent.setup()
    ;(api.post as jest.Mock).mockRejectedValue({
      message: 'Erro de validação',
    })

    render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    await fillNewAccountForm(user)
    await user.click(screen.getByText('Cadastrar'))

    await waitFor(
      () => {
        expect(toast.error).toHaveBeenCalledWith('Erro de validação')
        expect(mockOnSuccess).not.toHaveBeenCalled()
      },
      { timeout: 10000 }
    )
  }, 15000)

  it('shows primary checkbox for new accounts only', () => {
    const { rerender } = render(
      <BankAccountForm
        open={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    expect(screen.getByLabelText(/Definir como conta principal/)).toBeInTheDocument()

    rerender(
      <BankAccountForm
        open={true}
        account={mockAccounts[0]}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    expect(screen.queryByLabelText(/Definir como conta principal/)).not.toBeInTheDocument()
  })

  it('disables bank/account fields when editing', async () => {
    ;(api.get as jest.Mock).mockImplementation((url: string) => {
      if (url === '/api/bank-accounts/banks') {
        return Promise.resolve({ success: true, data: mockBanks })
      }
      if (url.includes('/api/bank-accounts/123-456')) {
        return Promise.resolve({
          success: true,
          data: {
            ...mockAccounts[0],
            account_number: '12345678',
            account_digit: '9',
            account_holder_document: '12345678000101',
          }
        })
      }
      return Promise.resolve({ success: true, data: [] })
    })

    render(
      <BankAccountForm
        open={true}
        account={mockAccounts[0]}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
      />
    )

    await waitFor(() => {
      const agencyInput = screen.getByPlaceholderText('0001')
      expect(agencyInput).toBeDisabled()
    })
  })
})
