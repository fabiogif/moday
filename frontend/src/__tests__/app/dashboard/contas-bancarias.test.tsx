import { render, screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import ContasBancariasPage from '@/app/(dashboard)/contas-bancarias/page'
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

describe('ContasBancariasPage', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(api.get as jest.Mock).mockResolvedValue({
      success: true,
      data: mockAccounts,
    })
  })

  it('renders the page with title', () => {
    render(<ContasBancariasPage />)
    
    expect(screen.getByText('Contas Bancárias')).toBeInTheDocument()
    expect(screen.getByText('Gerencie as contas bancárias para recebimento')).toBeInTheDocument()
  })

  it('renders add new account button', () => {
    render(<ContasBancariasPage />)
    
    expect(screen.getByText('Nova Conta')).toBeInTheDocument()
  })

  it('loads and displays bank accounts', async () => {
    render(<ContasBancariasPage />)

    await waitFor(() => {
      expect(api.get).toHaveBeenCalledWith('/api/bank-accounts')
    })

    await waitFor(() => {
      expect(screen.getAllByText('Banco do Brasil').length).toBeGreaterThan(0)
      expect(screen.getByText('Nubank')).toBeInTheDocument()
    })
  })

  it('displays primary account badge', async () => {
    render(<ContasBancariasPage />)

    await waitFor(() => {
      const badges = screen.getAllByText('Principal')
      expect(badges.length).toBeGreaterThan(0)
    })
  })

  it('displays verified status', async () => {
    render(<ContasBancariasPage />)

    await waitFor(() => {
      expect(screen.getByText('Verificada')).toBeInTheDocument()
      expect(screen.getByText('Pendente')).toBeInTheDocument()
    })
  })

  it('displays PIX information when available', async () => {
    render(<ContasBancariasPage />)

    await waitFor(() => {
      expect(screen.getAllByText('12.345.678/0001-01').length).toBeGreaterThan(0)
      expect(screen.getByText('financeiro@restaurant.com')).toBeInTheDocument()
    })
  })

  it('shows empty state when no accounts', async () => {
    (api.get as jest.Mock).mockResolvedValue({
      success: true,
      data: [],
    })

    render(<ContasBancariasPage />)

    await waitFor(() => {
      expect(screen.getByText('Nenhuma conta bancária cadastrada')).toBeInTheDocument()
      expect(screen.getByText('Cadastrar Primeira Conta')).toBeInTheDocument()
    })
  })

  it('opens form dialog when clicking add button', async () => {
    const user = userEvent.setup()
    render(<ContasBancariasPage />)

    const addButton = screen.getByText('Nova Conta')
    await user.click(addButton)

    await waitFor(() => {
      expect(screen.getByText('Nova Conta Bancária')).toBeInTheDocument()
    })
  })

  it('handles delete account', async () => {
    const user = userEvent.setup()
    global.confirm = jest.fn(() => true)
    ;(api.delete as jest.Mock).mockResolvedValue({ success: true })

    render(<ContasBancariasPage />)

    await waitFor(() => {
      expect(screen.getByText('Nubank')).toBeInTheDocument()
    })

    const deleteButtons = screen.getAllByTitle('Excluir conta')
    await user.click(deleteButtons[0])

    expect(global.confirm).toHaveBeenCalled()
    
    await waitFor(() => {
      expect(api.delete).toHaveBeenCalledWith('/api/bank-accounts/789-012')
      expect(toast.success).toHaveBeenCalledWith('Conta bancária excluída com sucesso!')
    })
  })

  it('handles set as primary', async () => {
    const user = userEvent.setup()
    ;(api.post as jest.Mock).mockResolvedValue({ success: true })

    render(<ContasBancariasPage />)

    await waitFor(() => {
      expect(screen.getByText('Nubank')).toBeInTheDocument()
    })

    const setPrimaryButton = screen.getByText('Tornar Principal')
    await user.click(setPrimaryButton)

    await waitFor(() => {
      expect(api.post).toHaveBeenCalledWith('/api/bank-accounts/789-012/set-primary', {})
      expect(toast.success).toHaveBeenCalledWith('Conta definida como principal!')
    })
  })

  it('handles error when loading accounts', async () => {
    (api.get as jest.Mock).mockRejectedValue({
      message: 'Network error',
    })

    render(<ContasBancariasPage />)

    await waitFor(() => {
      expect(toast.error).toHaveBeenCalled()
    })
  })

  it('displays account count correctly', async () => {
    render(<ContasBancariasPage />)

    await waitFor(() => {
      expect(screen.getByText('2 contas cadastradas')).toBeInTheDocument()
    })
  })

  it('shows loading state', async () => {
    ;(api.get as jest.Mock).mockImplementation(() => new Promise(() => {}))

    render(<ContasBancariasPage />)

    expect(document.querySelector('.animate-spin')).toBeInTheDocument()
  })
})

