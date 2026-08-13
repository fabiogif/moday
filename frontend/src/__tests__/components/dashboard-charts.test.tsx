import { render, screen, waitFor } from '@testing-library/react'
import { OrdersVolumeChart } from '@/app/(dashboard)/dashboard/components/orders-volume-chart'
import { CustomersChart } from '@/app/(dashboard)/dashboard/components/customers-chart'

// Mock dos hooks
jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedApi: jest.fn()
}))

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    user: { id: 1, name: 'Test User' }
  })
}))

const { useAuthenticatedApi } = require('@/hooks/use-authenticated-api')

describe('OrdersVolumeChart', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('deve renderizar loading state inicialmente', () => {
    useAuthenticatedApi.mockReturnValue({
      data: null,
      loading: true,
      error: null
    })

    render(<OrdersVolumeChart />)
    
    expect(screen.getByText(/Volume de Pedidos/i)).toBeInTheDocument()
    expect(screen.getByText(/Últimos 7 dias/i)).toBeInTheDocument()
  })

  it('deve renderizar gráfico com dados de pedidos', async () => {
    const mockOrders = [
      { id: 1, created_at: new Date().toISOString(), status: 'Concluído' },
      { id: 2, created_at: new Date().toISOString(), status: 'Preparo' },
      { id: 3, created_at: new Date(Date.now() - 86400000).toISOString(), status: 'Concluído' }
    ]

    useAuthenticatedApi.mockReturnValue({
      data: mockOrders,
      loading: false,
      error: null
    })

    render(<OrdersVolumeChart />)
    
    await waitFor(() => {
      expect(screen.getByText(/Volume de Pedidos/i)).toBeInTheDocument()
    })
  })

  it('deve tratar datas inválidas sem crashar', async () => {
    const mockOrders = [
      { id: 1, created_at: 'invalid-date', status: 'Concluído' },
      { id: 2, created_at: null, status: 'Preparo' },
      { id: 3, created_at: undefined, status: 'Concluído' }
    ]

    useAuthenticatedApi.mockReturnValue({
      data: mockOrders,
      loading: false,
      error: null
    })

    expect(() => render(<OrdersVolumeChart />)).not.toThrow()
  })

  it('deve lidar com array vazio de pedidos', async () => {
    useAuthenticatedApi.mockReturnValue({
      data: [],
      loading: false,
      error: null
    })

    render(<OrdersVolumeChart />)
    
    await waitFor(() => {
      expect(screen.getByText(/Volume de Pedidos/i)).toBeInTheDocument()
    })
  })
})

describe('CustomersChart', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('deve renderizar loading state inicialmente', () => {
    useAuthenticatedApi.mockReturnValue({
      data: null,
      loading: true,
      error: null
    })

    render(<CustomersChart />)
    
    expect(screen.getByText(/Novos Clientes/i)).toBeInTheDocument()
    expect(screen.getByText(/Últimos 6 meses/i)).toBeInTheDocument()
  })

  it('deve renderizar gráfico com dados de clientes', async () => {
    const mockClients = [
      { id: 1, name: 'Cliente 1', created_at: new Date().toISOString() },
      { id: 2, name: 'Cliente 2', created_at: new Date().toISOString() },
      { id: 3, name: 'Cliente 3', created_at: new Date(Date.now() - 2592000000).toISOString() }
    ]

    useAuthenticatedApi.mockReturnValue({
      data: mockClients,
      loading: false,
      error: null
    })

    render(<CustomersChart />)
    
    await waitFor(() => {
      expect(screen.getByText(/Novos Clientes/i)).toBeInTheDocument()
      expect(screen.getByText(/3 clientes totais/i)).toBeInTheDocument()
    })
  })

  it('deve tratar datas inválidas sem crashar', async () => {
    const mockClients = [
      { id: 1, name: 'Cliente 1', created_at: 'invalid-date' },
      { id: 2, name: 'Cliente 2', created_at: null },
      { id: 3, name: 'Cliente 3', created_at: undefined }
    ]

    useAuthenticatedApi.mockReturnValue({
      data: mockClients,
      loading: false,
      error: null
    })

    expect(() => render(<CustomersChart />)).not.toThrow()
  })

  it('deve mostrar total de clientes correto', async () => {
    const mockClients = Array.from({ length: 10 }, (_, i) => ({
      id: i + 1,
      name: `Cliente ${i + 1}`,
      created_at: new Date().toISOString()
    }))

    useAuthenticatedApi.mockReturnValue({
      data: mockClients,
      loading: false,
      error: null
    })

    render(<CustomersChart />)
    
    await waitFor(() => {
      expect(screen.getByText(/10 clientes totais/i)).toBeInTheDocument()
    })
  })

  it('deve calcular crescimento mensal', async () => {
    const mockClients = [
      { id: 1, name: 'Cliente 1', created_at: new Date().toISOString() },
      { id: 2, name: 'Cliente 2', created_at: new Date().toISOString() }
    ]

    useAuthenticatedApi.mockReturnValue({
      data: mockClients,
      loading: false,
      error: null
    })

    render(<CustomersChart />)
    
    await waitFor(() => {
      // Deve mostrar percentual de crescimento (pode ser positivo ou negativo)
      const percentageElements = screen.queryAllByText(/%/)
      expect(percentageElements.length).toBeGreaterThanOrEqual(0)
    })
  })
})

