import { render, waitFor } from '@testing-library/react'
import { OrderNotificationsProvider } from '@/contexts/order-notifications-context'

// Polling de segurança (WebSocket desconectado): a URL tem de sair do buildApiUrl, não da env crua
jest.mock('next/navigation', () => ({ useRouter: () => ({ push: jest.fn() }) }))
jest.mock('sonner', () => ({ toast: Object.assign(jest.fn(), { success: jest.fn(), error: jest.fn() }) }))
jest.mock('@/lib/notification-sound', () => ({ playUrgentSound: jest.fn() }))
jest.mock('@/hooks/use-realtime', () => ({ useRealtimeOrders: () => ({ isConnected: false }) }))
jest.mock('@/hooks/use-order-refresh', () => ({ useOrderRefresh: () => ({ triggerRefresh: jest.fn() }) }))
jest.mock('@/contexts/auth-context', () => ({ useAuth: () => ({ user: { tenant_id: 7 } }) }))
jest.mock('@/lib/auth-storage', () => ({ getAuthToken: () => 'tok' }))

describe('OrderNotificationsProvider — polling', () => {
  const originalEnv = process.env.NEXT_PUBLIC_API_URL

  beforeEach(() => {
    delete process.env.NEXT_PUBLIC_API_URL
    global.fetch = jest.fn().mockResolvedValue({ ok: true, json: async () => ({ data: [] }) }) as jest.Mock
  })

  afterAll(() => {
    process.env.NEXT_PUBLIC_API_URL = originalEnv
  })

  it('chama a lista de pedidos pela base da API, sem "undefined" na URL', async () => {
    render(<OrderNotificationsProvider><div /></OrderNotificationsProvider>)

    await waitFor(() => expect(global.fetch).toHaveBeenCalled())
    const url = String((global.fetch as jest.Mock).mock.calls[0][0])
    expect(url).not.toContain('undefined')
    expect(url).toMatch(/\/api\/order\?per_page=1&sort=created_at&order=desc$/)
  })
})
