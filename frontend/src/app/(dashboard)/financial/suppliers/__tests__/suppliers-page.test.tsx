import { render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import SuppliersPage from '../page'
import { useSuppliers, useSupplierMutation } from '@/hooks/use-suppliers'
import { toast } from 'sonner'

// Mock dos hooks
jest.mock('@/hooks/use-suppliers')
jest.mock('sonner')

const mockSuppliers = [
  {
    id: 1,
    uuid: 'uuid-1',
    name: 'Fornecedor A Ltda',
    fantasy_name: 'Fornecedor A',
    document: '12345678901234',
    document_type: 'cnpj' as const,
    email: 'contato@fornecedora.com',
    phone: '11987654321',
    is_active: true,
    created_at: '2025-10-30T10:00:00Z',
    updated_at: '2025-10-30T10:00:00Z',
  },
  {
    id: 2,
    uuid: 'uuid-2',
    name: 'Fornecedor B SA',
    document: '98765432109876',
    document_type: 'cnpj' as const,
    email: 'contato@fornecedorb.com',
    phone: '11912345678',
    is_active: false,
    created_at: '2025-10-30T11:00:00Z',
    updated_at: '2025-10-30T11:00:00Z',
  },
]

describe('SuppliersPage', () => {
  beforeEach(() => {
    ;(useSuppliers as jest.Mock).mockReturnValue({
      data: mockSuppliers,
      loading: false,
      refetch: jest.fn(),
    })
    ;(useSupplierMutation as jest.Mock).mockReturnValue({
      mutate: jest.fn(),
      loading: false,
    })
  })

  it('renders page title and description', () => {
    render(<SuppliersPage />)
    
    expect(screen.getByText('Fornecedores')).toBeInTheDocument()
    expect(screen.getByText('Gerencie seus fornecedores e dados de contato')).toBeInTheDocument()
  })

  it('renders new supplier button', () => {
    render(<SuppliersPage />)
    
    const button = screen.getByRole('button', { name: /novo fornecedor/i })
    expect(button).toBeInTheDocument()
  })

  it('renders search input', () => {
    render(<SuppliersPage />)
    
    const searchInput = screen.getByPlaceholderText(/buscar por nome/i)
    expect(searchInput).toBeInTheDocument()
  })

  it('displays suppliers in table', async () => {
    render(<SuppliersPage />)
    
    await waitFor(() => {
      expect(screen.getByText('Fornecedor A Ltda')).toBeInTheDocument()
      expect(screen.getByText('Fornecedor B SA')).toBeInTheDocument()
    })
  })

  it('displays supplier contact information', async () => {
    render(<SuppliersPage />)
    
    await waitFor(() => {
      expect(screen.getByText('contato@fornecedora.com')).toBeInTheDocument()
      expect(screen.getByText('11987654321')).toBeInTheDocument()
    })
  })

  it('displays active/inactive badges', async () => {
    render(<SuppliersPage />)
    
    await waitFor(() => {
      const badges = screen.getAllByText(/ativo|inativo/i)
      expect(badges.length).toBeGreaterThan(0)
    })
  })

  it('filters suppliers by search query', async () => {
    const user = userEvent.setup()
    render(<SuppliersPage />)
    
    const searchInput = screen.getByPlaceholderText(/buscar por nome/i)
    await user.type(searchInput, 'Fornecedor A')
    
    await waitFor(() => {
      expect(screen.getByText('Fornecedor A Ltda')).toBeInTheDocument()
      expect(screen.queryByText('Fornecedor B SA')).not.toBeInTheDocument()
    })
  })

  it('clears search when clicking X button', async () => {
    const user = userEvent.setup()
    render(<SuppliersPage />)
    
    const searchInput = screen.getByPlaceholderText(/buscar por nome/i)
    await user.type(searchInput, 'Teste')
    
    const clearButton = screen.getByRole('button', { name: '' })
    await user.click(clearButton)
    
    expect(searchInput).toHaveValue('')
  })

  it('shows loading state', () => {
    ;(useSuppliers as jest.Mock).mockReturnValue({
      data: null,
      loading: true,
      refetch: jest.fn(),
    })
    
    render(<SuppliersPage />)
    
    expect(screen.getByText('Lista de Fornecedores')).toBeInTheDocument()
    expect(document.querySelector('.animate-spin')).toBeInTheDocument()
  })

  it('shows empty state when no suppliers', () => {
    ;(useSuppliers as jest.Mock).mockReturnValue({
      data: [],
      loading: false,
      refetch: jest.fn(),
    })
    
    render(<SuppliersPage />)
    
    expect(screen.getByText(/nenhum fornecedor cadastrado/i)).toBeInTheDocument()
    expect(screen.getByText(/cadastrar primeiro fornecedor/i)).toBeInTheDocument()
  })

  it('opens form dialog when clicking new supplier button', async () => {
    const user = userEvent.setup()
    render(<SuppliersPage />)
    
    const newButton = screen.getByRole('button', { name: /^novo fornecedor$/i })
    await user.click(newButton)
    
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeInTheDocument()
      expect(within(screen.getByRole('dialog')).getByText('Novo Fornecedor')).toBeInTheDocument()
    })
  })

  it('displays edit and delete buttons for each supplier', async () => {
    render(<SuppliersPage />)
    
    await waitFor(() => {
      const editButtons = screen.getAllByTitle(/editar/i)
      const deleteButtons = screen.getAllByTitle(/excluir/i)
      
      expect(editButtons.length).toBe(2) // 2 fornecedores
      expect(deleteButtons.length).toBe(2)
    })
  })

  it('shows alert dialog when clicking delete', async () => {
    const user = userEvent.setup()
    render(<SuppliersPage />)
    
    const deleteButtons = await screen.findAllByTitle(/excluir/i)
    await user.click(deleteButtons[0])
    
    await waitFor(() => {
      expect(screen.getByText(/tem certeza que deseja excluir o fornecedor/i)).toBeInTheDocument()
    })
  })
})

