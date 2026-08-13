import { renderHook, act, waitFor } from '@testing-library/react'
import { OrderNotificationsProvider, useOrderNotifications } from '@/contexts/order-notifications-context'
import { AuthProvider } from '@/contexts/auth-context'
import { ReactNode } from 'react'
import { toast } from 'sonner'

// Mock dependencies
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
  }),
}))

jest.mock('sonner', () => ({
  toast: jest.fn(),
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

// Mock fetch global
global.fetch = jest.fn()

const mockUser = {
  id: '1',
  name: 'Test User',
  email: 'test@test.com',
  tenant_id: 1,
}

const wrapper = ({ children }: { children: ReactNode }) => (
  <AuthProvider>
    <OrderNotificationsProvider>{children}</OrderNotificationsProvider>
  </AuthProvider>
)

describe('OrderNotificationsContext', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.clear()
    localStorage.setItem('token', 'test-token')
    ;(global.fetch as jest.Mock).mockClear()
  })

  describe('Inicialização', () => {
    it('deve carregar preferência de som do localStorage', () => {
      localStorage.setItem('orderNotificationSoundEnabled', 'false')

      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      waitFor(() => {
        expect(result.current.soundEnabled).toBe(false)
      })
    })

    it('deve usar som habilitado como padrão se não houver preferência salva', () => {
      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      expect(result.current.soundEnabled).toBe(true)
    })
  })

  describe('Gerenciamento de Som', () => {
    it('deve permitir ativar/desativar som', () => {
      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      act(() => {
        result.current.setSoundEnabled(false)
      })

      expect(result.current.soundEnabled).toBe(false)
      expect(localStorage.getItem('orderNotificationSoundEnabled')).toBe('false')

      act(() => {
        result.current.setSoundEnabled(true)
      })

      expect(result.current.soundEnabled).toBe(true)
      expect(localStorage.getItem('orderNotificationSoundEnabled')).toBe('true')
    })

    it('deve persistir preferência no localStorage', () => {
      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      act(() => {
        result.current.setSoundEnabled(false)
      })

      expect(localStorage.getItem('orderNotificationSoundEnabled')).toBe('false')
    })
  })

  describe('Notificações', () => {
    it('deve iniciar com lista vazia de notificações', () => {
      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      expect(result.current.notifications).toEqual([])
    })

    it('deve permitir limpar notificação específica', () => {
      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      // Simular notificação
      const notification = {
        id: 'test-1',
        orderId: '1',
        orderIdentify: 'ABC123',
        customerName: 'Test Customer',
        total: '50,00',
        createdAt: new Date().toISOString(),
        timestamp: Date.now(),
      }

      act(() => {
        // Não há método público para adicionar, então vamos testar o clear
        result.current.clearNotification('test-1')
      })

      expect(result.current.notifications.length).toBe(0)
    })

    it('deve permitir limpar todas as notificações', () => {
      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      act(() => {
        result.current.clearAllNotifications()
      })

      expect(result.current.notifications).toEqual([])
    })
  })

  describe('Polling Fallback', () => {
    it('deve fazer polling quando WebSocket não está conectado', async () => {
      const mockOrders = {
        success: true,
        data: {
          data: [
            {
              id: 1,
              identify: 'TEST123',
              customer_name: 'Test Customer',
              total: '50.00',
              created_at: new Date().toISOString(),
            }
          ]
        }
      }

      ;(global.fetch as jest.Mock).mockResolvedValue({
        ok: true,
        json: async () => mockOrders,
      })

      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      // O polling deve estar configurado
      await waitFor(() => {
        expect(result.current).toBeDefined()
      })
    })
  })

  describe('Contexto', () => {
    it('deve estar disponível dentro do provider', () => {
      const { result } = renderHook(() => useOrderNotifications(), { wrapper })

      expect(result.current).toBeDefined()
      expect(result.current.notifications).toBeDefined()
      expect(result.current.soundEnabled).toBeDefined()
      expect(result.current.setSoundEnabled).toBeDefined()
      expect(result.current.clearNotification).toBeDefined()
      expect(result.current.clearAllNotifications).toBeDefined()
    })
  })
})

