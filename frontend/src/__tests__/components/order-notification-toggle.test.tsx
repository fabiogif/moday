import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { OrderNotificationToggle } from '@/components/order-notification-toggle'
import { OrderNotificationsProvider } from '@/contexts/order-notifications-context'
import { AuthProvider } from '@/contexts/auth-context'
import { toast } from 'sonner'

// Mock dependencies
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
  }),
}))

jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
  },
}))

jest.mock('@/hooks/use-realtime', () => ({
  useRealtimeOrders: jest.fn(() => ({
    isConnected: false,
  })),
}))

jest.mock('@/lib/notification-sound', () => ({
  playNotificationSound: jest.fn(),
  initAudioContext: jest.fn(),
}))

// Importar mock após definir
import { playNotificationSound } from '@/lib/notification-sound'
const mockPlaySound = playNotificationSound as jest.Mock

describe('OrderNotificationToggle', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.clear()
    localStorage.setItem('token', 'test-token')
  })

  const renderComponent = () => {
    return render(
      <AuthProvider>
        <OrderNotificationsProvider>
          <OrderNotificationToggle />
        </OrderNotificationsProvider>
      </AuthProvider>
    )
  }

  describe('Renderização', () => {
    it('deve renderizar o card de notificações', () => {
      renderComponent()

      expect(screen.getByText('Notificações de Pedidos')).toBeInTheDocument()
      expect(screen.getByText('Configure alertas sonoros e visuais para novos pedidos')).toBeInTheDocument()
    })

    it('deve exibir switch de som', () => {
      renderComponent()

      const switchElement = screen.getByRole('switch')
      expect(switchElement).toBeInTheDocument()
      expect(screen.getByText('Som de Notificação')).toBeInTheDocument()
    })

    it('deve exibir botão de teste', () => {
      renderComponent()

      const testButton = screen.getByRole('button', { name: /Testar Som/i })
      expect(testButton).toBeInTheDocument()
    })

    it('deve exibir informações de uso', () => {
      renderComponent()

      expect(screen.getByText(/Como funciona/i)).toBeInTheDocument()
      expect(screen.getByText(/As notificações aparecem automaticamente/i)).toBeInTheDocument()
    })
  })

  describe('Funcionalidade do Switch', () => {
    it('deve permitir ativar/desativar som', async () => {
      renderComponent()

      const switchElement = screen.getByRole('switch')

      // Som deve estar ativo por padrão
      expect(switchElement).toBeChecked()

      // Desativar
      fireEvent.click(switchElement)

      await waitFor(() => {
        expect(switchElement).not.toBeChecked()
        expect(localStorage.getItem('orderNotificationSoundEnabled')).toBe('false')
      })

      // Ativar novamente
      fireEvent.click(switchElement)

      await waitFor(() => {
        expect(switchElement).toBeChecked()
        expect(localStorage.getItem('orderNotificationSoundEnabled')).toBe('true')
      })
    })
  })

  describe('Botão de Teste', () => {
    it('deve tocar som ao clicar em "Testar Som"', async () => {
      renderComponent()

      const testButton = screen.getByRole('button', { name: /Testar Som/i })

      fireEvent.click(testButton)

      await waitFor(() => {
        expect(mockPlaySound).toHaveBeenCalledTimes(1)
        expect(toast.success).toHaveBeenCalledWith(
          'Som de notificação reproduzido!',
          expect.objectContaining({ duration: 2000 })
        )
      })
    })

    it('deve desabilitar botão de teste quando som está desabilitado', () => {
      localStorage.setItem('orderNotificationSoundEnabled', 'false')
      
      renderComponent()

      const testButton = screen.getByRole('button', { name: /Testar Som/i })
      expect(testButton).toBeDisabled()
    })
  })

  describe('Ícones', () => {
    it('deve exibir ícone de volume ativo quando som está habilitado', () => {
      renderComponent()

      const title = screen.getByText('Notificações de Pedidos').closest('div')
      expect(title).toBeInTheDocument()
    })

    it('deve exibir ícone de volume desativado quando som está desabilitado', async () => {
      renderComponent()

      const switchElement = screen.getByRole('switch')
      fireEvent.click(switchElement)

      await waitFor(() => {
        const title = screen.getByText('Notificações de Pedidos').closest('div')
        expect(title).toBeInTheDocument()
      })
    })
  })

  describe('Persistência', () => {
    it('deve salvar preferência no localStorage ao mudar', async () => {
      renderComponent()

      const switchElement = screen.getByRole('switch')

      fireEvent.click(switchElement)

      await waitFor(() => {
        expect(localStorage.getItem('orderNotificationSoundEnabled')).toBe('false')
      })
    })

    it('deve carregar preferência do localStorage ao iniciar', () => {
      localStorage.setItem('orderNotificationSoundEnabled', 'false')

      renderComponent()

      const switchElement = screen.getByRole('switch')
      expect(switchElement).not.toBeChecked()
    })
  })
})

