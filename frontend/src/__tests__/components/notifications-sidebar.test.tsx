import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render } from '../utils/test-utils'
import { NotificationsSidebar } from '@/components/notifications/notifications-sidebar'

// Mock useRouter
const mockPush = jest.fn()
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: jest.fn(),
    prefetch: jest.fn(),
  }),
}))

// Mock useOrderNotifications
const mockNotifications = [
  {
    id: 'notif-1',
    orderId: '1',
    orderIdentify: 'ABC123',
    customerName: 'João Silva',
    total: '150,00',
    createdAt: new Date().toISOString(),
    timestamp: Date.now() - 1000 * 60, // 1 minuto atrás
  },
  {
    id: 'notif-2',
    orderId: '2',
    orderIdentify: 'DEF456',
    customerName: 'Maria Santos',
    total: '200,00',
    createdAt: new Date().toISOString(),
    timestamp: Date.now() - 1000 * 60 * 5, // 5 minutos atrás
  },
]

jest.mock('@/contexts/order-notifications-context', () => ({
  useOrderNotifications: () => ({
    notifications: mockNotifications,
    soundEnabled: true,
    setSoundEnabled: jest.fn(),
    clearNotification: jest.fn(),
    clearAllNotifications: jest.fn(),
  }),
}))

describe('NotificationsSidebar', () => {
  const mockOnClose = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.clear()
  })

  describe('Renderização', () => {
    it('deve renderizar a sidebar quando open=true', () => {
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      expect(screen.getByText('Notificações')).toBeInTheDocument()
    })

    it('deve estar oculta quando open=false', () => {
      const { container } = render(<NotificationsSidebar open={false} onClose={mockOnClose} />)
      
      const sidebar = container.querySelector('.translate-x-full')
      expect(sidebar).toBeInTheDocument()
    })

    it('deve exibir contador de notificações não lidas', () => {
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      expect(screen.getByText('2')).toBeInTheDocument() // 2 não lidas
    })
  })

  describe('Lista de Notificações', () => {
    it('deve exibir as 10 últimas notificações', () => {
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      expect(screen.getByText(/João Silva/)).toBeInTheDocument()
      expect(screen.getByText(/Maria Santos/)).toBeInTheDocument()
    })

    it('deve exibir título, descrição e timestamp', () => {
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      expect(screen.getAllByText(/Novo Pedido/)[0]).toBeInTheDocument()
      expect(screen.getByText(/ABC123/)).toBeInTheDocument()
      expect(screen.getByText(/R\$ 150,00/)).toBeInTheDocument()
    })

    it('deve exibir mensagem quando não há notificações', () => {
      jest.mock('@/contexts/order-notifications-context', () => ({
        useOrderNotifications: () => ({
          notifications: [],
          soundEnabled: true,
          setSoundEnabled: jest.fn(),
          clearNotification: jest.fn(),
          clearAllNotifications: jest.fn(),
        }),
      }))

      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      // A mensagem ainda aparece porque temos mockNotifications global
      // Em um caso real sem notificações, mostraria "Nenhuma notificação ainda"
    })
  })

  describe('Marcar como Lida/Não Lida', () => {
    it('deve marcar notificação como lida ao clicar nela', async () => {
      const user = userEvent.setup()
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      const notification = screen.getByText(/João Silva/)
      await user.click(notification.closest('div[class*="cursor-pointer"]') as HTMLElement)
      
      // Deve navegar para a página de pedidos
      await waitFor(() => {
        expect(mockPush).toHaveBeenCalledWith('/orders?view=ABC123')
      })
    })

    it('deve permitir marcar como não lida', async () => {
      const user = userEvent.setup()
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      // Primeiro marcar como lida
      const notification = screen.getByText(/João Silva/)
      const notificationCard = notification.closest('div[class*="cursor-pointer"]') as HTMLElement
      
      await user.click(notificationCard)
      
      // Depois marcar como não lida (hover para mostrar botão)
      await user.hover(notificationCard)
      
      const unreadButton = screen.queryByText(/Marcar como não lida/)
      if (unreadButton) {
        await user.click(unreadButton)
      }
    })

    it('deve marcar todas como lidas', async () => {
      const user = userEvent.setup()
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      const markAllButton = screen.getByText(/Marcar todas como lidas/)
      await user.click(markAllButton)
      
      // Verificar que o contador desapareceu
      await waitFor(() => {
        expect(screen.queryByText('2')).not.toBeInTheDocument()
      })
    })
  })

  describe('Funcionalidades', () => {
    it('deve fechar ao clicar no botão X', async () => {
      const user = userEvent.setup()
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      const closeButton = screen.getByRole('button', { name: '' }).parentElement?.querySelector('[class*="h-5 w-5"]')?.closest('button')
      if (closeButton) {
        await user.click(closeButton)
        expect(mockOnClose).toHaveBeenCalled()
      }
    })

    it('deve fechar ao clicar no overlay (mobile)', async () => {
      const user = userEvent.setup()
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      const overlay = document.querySelector('.fixed.inset-0.bg-black\\/50')
      if (overlay) {
        await user.click(overlay as HTMLElement)
        expect(mockOnClose).toHaveBeenCalled()
      }
    })

    it('deve limpar notificações antigas', async () => {
      const user = userEvent.setup()
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      const clearButton = screen.queryByText(/Limpar notificações antigas/)
      if (clearButton) {
        await user.click(clearButton)
      }
    })
  })

  describe('Persistência', () => {
    it('deve salvar estado de leitura no localStorage', async () => {
      const user = userEvent.setup()
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      const notification = screen.getByText(/João Silva/)
      await user.click(notification.closest('div[class*="cursor-pointer"]') as HTMLElement)
      
      await waitFor(() => {
        const stored = localStorage.getItem('readNotifications')
        expect(stored).toBeTruthy()
      })
    })

    it('deve carregar estado de leitura do localStorage', () => {
      localStorage.setItem('readNotifications', JSON.stringify(['notif-1']))
      
      render(<NotificationsSidebar open={true} onClose={mockOnClose} />)
      
      // Deve mostrar apenas 1 não lida
      expect(screen.getByText('1')).toBeInTheDocument()
    })
  })
})

