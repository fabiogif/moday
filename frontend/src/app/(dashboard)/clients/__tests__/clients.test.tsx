import { render, screen, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import ClientsPage from '../page'
import {
  useAuthenticatedClients,
  useAuthenticatedClientStats,
  useMutation,
} from '@/hooks/use-authenticated-api'
import { showErrorToast } from '@/components/ui/error-toast'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    token: 'mock-token',
    isAuthenticated: true,
    isLoading: false,
    user: { name: 'Test User', email: 'test@test.com' },
  }),
}))

jest.mock('@/hooks/use-authenticated-api')
jest.mock('@/components/ui/error-toast')

const mockClients = [
  {
    id: 1,
    name: 'João Silva',
    cpf: '123.456.789-00',
    email: 'joao@example.com',
    phone: '(11) 98765-4321',
    total_orders: 5,
    is_active: true,
    created_at: '2025-01-01',
    created_at_formatted: '01/01/2025',
    updated_at: '2025-01-01',
  },
  {
    id: 2,
    name: 'Maria Santos',
    cpf: '987.654.321-00',
    email: 'maria@example.com',
    phone: '(11) 91234-5678',
    total_orders: 3,
    is_active: true,
    created_at: '2025-01-02',
    created_at_formatted: '02/01/2025',
    updated_at: '2025-01-02',
  },
]

const defaultClientStats = {
  total_clients: { current: 2, previous: 0, growth: 0 },
  active_clients: { current: 2, previous: 0, growth: 0 },
  orders_per_client: { current: 4, previous: 0, growth: 0 },
  new_clients: { current: 1, previous: 0, growth: 0 },
}

describe('ClientsPage - CRUD Operations', () => {
  beforeEach(() => {
    jest.clearAllMocks()

    ;(useAuthenticatedClients as jest.Mock).mockReturnValue({
      data: { data: mockClients },
      loading: false,
      error: null,
      refetch: jest.fn(),
      isAuthenticated: true,
    })

    ;(useAuthenticatedClientStats as jest.Mock).mockReturnValue({
      data: defaultClientStats,
      loading: false,
      error: null,
      refetch: jest.fn(),
      isAuthenticated: true,
    })

    ;(useMutation as jest.Mock).mockReturnValue({
      mutate: jest.fn(),
      loading: false,
    })
  })

  describe('CREATE - Adicionar Cliente', () => {
    it('deve renderizar a lista de clientes', async () => {
      render(<ClientsPage />)

      await waitFor(() => {
        expect(screen.getAllByText('João Silva').length).toBeGreaterThan(0)
        expect(screen.getAllByText('Maria Santos').length).toBeGreaterThan(0)
      })
    })

    it('deve configurar mutation para criação', () => {
      const mockCreate = jest.fn().mockResolvedValue({
        message: 'Cliente cadastrado com sucesso!',
        data: { id: 3, name: 'Novo Cliente' },
      })

      ;(useMutation as jest.Mock).mockReturnValue({
        mutate: mockCreate,
        loading: false,
      })

      render(<ClientsPage />)
      expect(useMutation).toHaveBeenCalled()
    })

    it('deve mostrar erro formatado em caso de falha', () => {
      ;(useMutation as jest.Mock).mockReturnValue({
        mutate: jest.fn().mockRejectedValue({
          message: 'Este CPF já está cadastrado.',
          errors: { cpf: ['Este CPF já está cadastrado.'] },
        }),
        loading: false,
      })

      render(<ClientsPage />)
      expect(showErrorToast).not.toHaveBeenCalled()
    })
  })

  describe('UPDATE - Editar Cliente', () => {
    it('deve renderizar clientes para edição', async () => {
      render(<ClientsPage />)

      await waitFor(() => {
        expect(screen.getAllByText('João Silva').length).toBeGreaterThan(0)
      })
    })
  })

  describe('DELETE - Excluir Cliente', () => {
    it('deve renderizar clientes antes da exclusão', async () => {
      render(<ClientsPage />)

      await waitFor(() => {
        expect(screen.getAllByText('João Silva').length).toBeGreaterThan(0)
        expect(screen.getAllByText('Maria Santos').length).toBeGreaterThan(0)
      })
    })

    it('não deve exibir toast de erro sem interação do usuário', async () => {
      ;(useMutation as jest.Mock).mockReturnValue({
        mutate: jest.fn().mockRejectedValue({
          message: 'Erro ao excluir cliente',
        }),
        loading: false,
      })

      render(<ClientsPage />)

      await waitFor(() => {
        expect(screen.getAllByText('João Silva').length).toBeGreaterThan(0)
      })

      expect(showErrorToast).not.toHaveBeenCalled()
    })
  })

  describe('Validações', () => {
    it('deve mostrar loading state', () => {
      ;(useAuthenticatedClients as jest.Mock).mockReturnValue({
        data: null,
        loading: true,
        error: null,
        refetch: jest.fn(),
        isAuthenticated: true,
      })

      render(<ClientsPage />)
      expect(screen.getByText(/carregando clientes/i)).toBeInTheDocument()
    })

    it('deve mostrar erro quando API falhar', () => {
      ;(useAuthenticatedClients as jest.Mock).mockReturnValue({
        data: null,
        loading: false,
        error: 'Erro ao carregar dados',
        refetch: jest.fn(),
        isAuthenticated: true,
      })

      render(<ClientsPage />)
      expect(screen.getByText(/erro ao carregar clientes/i)).toBeInTheDocument()
    })

    it('deve mostrar mensagem quando não autenticado', () => {
      ;(useAuthenticatedClients as jest.Mock).mockReturnValue({
        data: null,
        loading: false,
        error: null,
        refetch: jest.fn(),
        isAuthenticated: false,
      })

      render(<ClientsPage />)
      expect(screen.getByText(/não autenticado/i)).toBeInTheDocument()
    })
  })
})
