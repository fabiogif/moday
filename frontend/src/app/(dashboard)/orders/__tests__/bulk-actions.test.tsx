/**
 * Testes para ações em massa na página de pedidos
 * - Exclusão em massa
 * - Atualização de status em massa
 */

import { render, screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { DataTable } from '../components/data-table'
import OrdersPage from '../page'
import { useAuthenticatedOrders, useMutation } from '@/hooks/use-authenticated-api'
import { Order } from '../types'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    token: 'mock-token',
    isAuthenticated: true,
    isLoading: false,
    user: { name: 'Test User', email: 'test@test.com' },
  }),
}))

// Mock do toast
jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}))

// Mock dos hooks
jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedOrders: jest.fn(),
  useMutation: jest.fn(),
}))

jest.mock('@/lib/api-client', () => ({
  apiClient: {
    get: jest.fn().mockResolvedValue({ success: true, data: { count: 0, order_ids: [], days: 15 } }),
    post: jest.fn().mockResolvedValue({ success: true, data: { total_updated: 0 } }),
  },
  endpoints: {
    orders: {
      delete: (id: string) => `/api/order/${id}`,
      invoice: (id: string) => `/api/order/${id}/invoice`,
      bulkDelete: '/api/orders/bulk-delete',
      bulkUpdateStatus: '/api/orders/bulk-update-status',
      staleOpen: (days = 15) => `/api/orders/stale-open?days=${days}`,
      completeStale: '/api/orders/complete-stale',
    },
  },
}))

// Mock do useOrderRefresh
jest.mock('@/hooks/use-order-refresh', () => ({
  useOrderRefresh: () => ({
    shouldRefresh: false,
    resetRefresh: jest.fn(),
  }),
}))

// Mock do router
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
    replace: jest.fn(),
  }),
  useSearchParams: () => ({
    get: jest.fn(),
  }),
}))

// Mock StatCards
jest.mock('../components/stat-cards', () => ({
  StatCards: () => <div data-testid="stat-cards">Stats</div>,
}))

// Mock OrderDetailsDialog
jest.mock('../components/order-details-dialog', () => ({
  OrderDetailsDialog: () => null,
}))

// Mock ReceiptDialog
jest.mock('../components/receipt-dialog', () => ({
  ReceiptDialog: () => null,
}))

const mockUseAuthenticatedOrders = useAuthenticatedOrders as jest.MockedFunction<typeof useAuthenticatedOrders>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

const generateOrder = (overrides: Partial<Order> = {}): Order => ({
  id: 1,
  identify: 'ORD001',
  orderNumber: 'ORD001',
  status: 'Pendente',
  total: 50.00,
  client: {
    id: 1,
    name: 'Cliente Teste',
    email: 'cliente@teste.com',
  },
  customerName: 'Cliente Teste',
  customerEmail: 'cliente@teste.com',
  products: [],
  ...overrides,
})

describe('Ações em Massa - DataTable', () => {
  const mockOrders: Order[] = [
    generateOrder({ id: 1, identify: 'ORD001', orderNumber: 'ORD001' }),
    generateOrder({ id: 2, identify: 'ORD002', orderNumber: 'ORD002' }),
    generateOrder({ id: 3, identify: 'ORD003', orderNumber: 'ORD003' }),
  ]

  const mockOnBulkDelete = jest.fn()
  const mockOnBulkUpdateStatus = jest.fn()
  const mockOnDeleteOrder = jest.fn()
  const mockOnEditOrder = jest.fn()
  const mockOnViewOrder = jest.fn()
  const mockOnReceiptOrder = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    mockOnBulkDelete.mockResolvedValue(undefined)
    mockOnBulkUpdateStatus.mockResolvedValue(undefined)
  })

  describe('Exclusão em Massa', () => {
    it('deve exibir barra de ações quando há pedidos selecionados', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar primeiro pedido
      const rowCheckboxes = screen.getAllByRole('checkbox', { name: 'Select row' })
      await userEvent.click(rowCheckboxes[0])

      await waitFor(() => {
        expect(screen.getByText(/pedido\(s\) selecionado\(s\)/i)).toBeInTheDocument()
      })
    })

    it('deve exibir botão "Excluir Selecionados" quando há seleção', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar pedidos
      const rowCheckboxes = screen.getAllByRole('checkbox', { name: 'Select row' })
      await userEvent.click(rowCheckboxes[0])
      await userEvent.click(rowCheckboxes[1])

      await waitFor(() => {
        expect(screen.getByText(/Excluir Selecionados/i)).toBeInTheDocument()
      })
    })

    it('deve abrir modal de confirmação ao clicar em "Excluir Selecionados"', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar pedidos
      const rowCheckboxes = screen.getAllByRole('checkbox', { name: 'Select row' })
      await userEvent.click(rowCheckboxes[0])

      // Clicar em "Excluir Selecionados"
      await waitFor(() => {
        expect(screen.getByText(/Excluir Selecionados/i)).toBeInTheDocument()
      })

      await userEvent.click(screen.getByText(/Excluir Selecionados/i))

      // Verificar que o modal foi aberto
      await waitFor(() => {
        expect(screen.getByText(/Confirmar exclusão em massa/i)).toBeInTheDocument()
        expect(screen.getByText(/Deseja realmente realizar esta operação\?/i)).toBeInTheDocument()
      })
    })

    it('deve executar exclusão em massa ao confirmar no modal', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar pedidos
      const rowCheckboxes = screen.getAllByRole('checkbox', { name: 'Select row' })
      await userEvent.click(rowCheckboxes[0])

      // Abrir modal
      await waitFor(() => {
        expect(screen.getByText(/Excluir Selecionados/i)).toBeInTheDocument()
      })
      await userEvent.click(screen.getByText(/Excluir Selecionados/i))

      // Confirmar exclusão
      const confirmButton = await screen.findByRole('button', { name: /^Excluir$/i })
      await userEvent.click(confirmButton)

      // Verificar que a função foi chamada
      await waitFor(() => {
        expect(mockOnBulkDelete).toHaveBeenCalledWith(['ORD001'])
      })
    })

    it('deve limpar seleção após exclusão em massa', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar pedidos
      const rowCheckboxes = screen.getAllByRole('checkbox', { name: 'Select row' })
      await userEvent.click(rowCheckboxes[0])

      // Executar exclusão
      await waitFor(() => {
        expect(screen.getByText(/Excluir Selecionados/i)).toBeInTheDocument()
      })
      await userEvent.click(screen.getByText(/Excluir Selecionados/i))

      const confirmButton = await screen.findByRole('button', { name: /^Excluir$/i })
      await userEvent.click(confirmButton)

      // Verificar que a barra de ações desapareceu
      await waitFor(() => {
        expect(screen.queryByText(/pedido\(s\) selecionado\(s\)/i)).not.toBeInTheDocument()
      }, { timeout: 2000 })
    })
  })

  describe('Atualização de Status em Massa', () => {
    it('deve exibir botão "Mover para Aceito" quando há seleção no mesmo status', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar pedidos
      const rowCheckboxes = screen.getAllByRole('checkbox', { name: 'Select row' })
      await userEvent.click(rowCheckboxes[0])

      await waitFor(() => {
        expect(screen.getByText(/Mover para Aceito/i)).toBeInTheDocument()
        expect(screen.getByText(/Status atual:/i)).toBeInTheDocument()
        expect(screen.getAllByText('Pendente').length).toBeGreaterThan(0)
      })
    })

    it('deve desabilitar avanço quando status selecionados forem distintos', async () => {
      const mixedOrders: Order[] = [
        generateOrder({ id: 1, identify: 'ORD001', status: 'Pendente' }),
        generateOrder({ id: 2, identify: 'ORD002', status: 'Aceito' }),
      ]

      render(
        <DataTable
          orders={mixedOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      await userEvent.click(screen.getByRole('checkbox', { name: 'Select all' }))

      await waitFor(() => {
        expect(
          screen.getByText(/Os pedidos possuem status distintos/i)
        ).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /Mover para próximo status/i })).toBeDisabled()
      })
    })

    it('deve abrir modal de confirmação ao clicar em "Mover para Aceito"', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar pedidos
      const rowCheckboxes = screen.getAllByRole('checkbox', { name: 'Select row' })
      await userEvent.click(rowCheckboxes[0])

      // Clicar em "Mover para Aceito"
      await waitFor(() => {
        expect(screen.getByText(/Mover para Aceito/i)).toBeInTheDocument()
      })
      await userEvent.click(screen.getByText(/Mover para Aceito/i))

      // Verificar que o modal foi aberto
      await waitFor(() => {
        expect(screen.getByText(/Confirmar atualização de status/i)).toBeInTheDocument()
        expect(screen.getByText(/Deseja realmente realizar esta operação\?/i)).toBeInTheDocument()
      })
    })

    it('deve executar atualização de status em massa ao confirmar', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar todos os pedidos da página
      await userEvent.click(screen.getByRole('checkbox', { name: 'Select all' }))

      // Abrir modal
      await waitFor(() => {
        expect(screen.getByText(/Mover para Aceito/i)).toBeInTheDocument()
      })
      await userEvent.click(screen.getByText(/Mover para Aceito/i))

      // Confirmar atualização
      const confirmButton = await screen.findByRole('button', { name: /^Confirmar$/i })
      await userEvent.click(confirmButton)

      // Verificar que a função foi chamada com os IDs corretos e próximo status do fluxo
      await waitFor(() => {
        expect(mockOnBulkUpdateStatus).toHaveBeenCalledWith(
          expect.arrayContaining(['ORD001', 'ORD002']),
          'Aceito'
        )
      })
    })

    it('deve exibir "Mover para <próximo status>" no menu de ações da linha', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      const menus = screen.getAllByRole('button', { name: /Abrir menu/i })
      await userEvent.click(menus[0])

      await waitFor(() => {
        expect(screen.getByText(/Mover para Aceito/i)).toBeInTheDocument()
      })
    })
  })

  describe('Validações', () => {
    it('não deve exibir barra de ações quando não há seleção', () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      expect(screen.queryByText(/pedido\(s\) selecionado\(s\)/i)).not.toBeInTheDocument()
    })

    it('deve exibir contador correto de pedidos selecionados', async () => {
      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      // Selecionar todos os pedidos
      await userEvent.click(screen.getByRole('checkbox', { name: 'Select all' }))

      await waitFor(() => {
        expect(
          screen.getByText((content) => content.includes('pedido(s) selecionado(s)'))
        ).toBeInTheDocument()
      })
    })

    it('deve desabilitar botões durante operação', async () => {
      let resolveDelete: (() => void) | undefined
      mockOnBulkDelete.mockImplementation(
        () =>
          new Promise<void>((resolve) => {
            resolveDelete = resolve
          })
      )

      render(
        <DataTable
          orders={mockOrders}
          onDeleteOrder={mockOnDeleteOrder}
          onEditOrder={mockOnEditOrder}
          onViewOrder={mockOnViewOrder}
          onReceiptOrder={mockOnReceiptOrder}
          onBulkDelete={mockOnBulkDelete}
          onBulkUpdateStatus={mockOnBulkUpdateStatus}
        />
      )

      const rowCheckboxes = screen.getAllByRole('checkbox', { name: 'Select row' })
      await userEvent.click(rowCheckboxes[0])

      await waitFor(() => {
        expect(screen.getByText(/Excluir Selecionados/i)).toBeInTheDocument()
      })
      await userEvent.click(screen.getByText(/Excluir Selecionados/i))

      const confirmButton = await screen.findByRole('button', { name: /^Excluir$/i })
      await userEvent.click(confirmButton)

      await waitFor(() => {
        expect(mockOnBulkDelete).toHaveBeenCalled()
      })

      resolveDelete?.()
    })
  })
})

describe('Ações em Massa - OrdersPage', () => {
  const mockOrders: Order[] = [
    generateOrder({ id: 1, identify: 'ORD001' }),
    generateOrder({ id: 2, identify: 'ORD002' }),
  ]

  const mockMutate = jest.fn()
  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuthenticatedOrders.mockReturnValue({
      data: mockOrders,
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    } as any)

    mockUseMutation.mockReturnValue({
      mutate: mockMutate,
      loading: false,
      error: null,
    } as any)
  })

  it('deve chamar endpoint de exclusão em massa com IDs corretos', async () => {
    mockMutate.mockResolvedValue({
      success: true,
      message: 'Pedidos excluídos com sucesso!',
      data: {
        total_deleted: 2,
        deleted: ['ORD001', 'ORD002'],
      },
    })

    render(<OrdersPage />)

    await waitFor(() => {
      expect(mockUseAuthenticatedOrders).toHaveBeenCalled()
    })
    expect(screen.getByText('Pedidos')).toBeInTheDocument()
  })

  it('deve chamar endpoint de atualização de status com dados corretos', async () => {
    mockMutate.mockResolvedValue({
      success: true,
      message: 'Status atualizado com sucesso!',
      data: {
        total_updated: 2,
        updated: ['ORD001', 'ORD002'],
      },
    })

    render(<OrdersPage />)

    await waitFor(() => {
      expect(mockUseAuthenticatedOrders).toHaveBeenCalled()
    })
    expect(screen.getByText('Pedidos')).toBeInTheDocument()
  })

  it('deve exibir mensagem de erro quando exclusão em massa falha', async () => {
    const errorMessage = 'Erro ao excluir pedidos'
    mockMutate.mockRejectedValue({
      message: errorMessage,
    })

    render(<OrdersPage />)

    await waitFor(() => {
      expect(mockUseAuthenticatedOrders).toHaveBeenCalled()
    })
    expect(screen.getByText('Pedidos')).toBeInTheDocument()
  })
})

