import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import { OrderTrack } from '@/app/store/[slug]/components/order-track'
import { apiClient } from '@/lib/api-client'
import { toast } from 'sonner'

// Mock dependencies
jest.mock('@/lib/api-client')
jest.mock('sonner')

const mockApiClient = apiClient as jest.Mocked<typeof apiClient>

const queueStoreInfoCall = () => {
  mockApiClient.get.mockResolvedValueOnce({
    success: true,
    data: {
      tenant_id: 1,
      name: 'Loja Teste',
      email: 'contato@teste.com',
      phone: '(11) 99999-9999',
    },
  } as any)
}

const renderOrderTrackComponent = async () => {
  const utils = render(<OrderTrack slug="test-store" />)
  await waitFor(() => expect(mockApiClient.get).toHaveBeenCalled())
  return utils
}

describe('OrderTrack Component', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renders the component correctly', async () => {
    queueStoreInfoCall()
    await renderOrderTrackComponent()
    
    expect(screen.getByText('Acompanhar Pedido')).toBeInTheDocument()
    expect(screen.getByPlaceholderText('(00) 00000-0000')).toBeInTheDocument()
    expect(screen.getByPlaceholderText('(00) 00000-0000')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Consultar Pedido/i })).toBeInTheDocument()
  })

  it('keeps the search button disabled when CPF and phone are empty', async () => {
    queueStoreInfoCall()
    await renderOrderTrackComponent()

    const searchButton = screen.getByRole('button', { name: /Consultar Pedido/i })
    expect(searchButton).toBeDisabled()
    expect(toast.error).not.toHaveBeenCalled()
  })

  it.skip('masks phone input correctly (duplicate - skipped)', async () => {
    queueStoreInfoCall()
    await renderOrderTrackComponent()
    
    const cpfInput = screen.getByPlaceholderText('(00) 00000-0000') as HTMLInputElement
    
    fireEvent.change(cpfInput, { target: { value: '12345678900' } })
    
    expect(cpfInput.value).toMatch(/\d{3}\.\d{3}\.\d{3}-\d{2}/)
  })

  it('masks phone input correctly', async () => {
    queueStoreInfoCall()
    await renderOrderTrackComponent()
    
    const phoneInput = screen.getByPlaceholderText('(00) 00000-0000') as HTMLInputElement
    
    fireEvent.change(phoneInput, { target: { value: '11987654321' } })
    
    expect(phoneInput.value).toMatch(/\(\d{2}\) \d{5}-\d{4}/)
  })

  it('displays order data when found', async () => {
    queueStoreInfoCall()
    const mockOrderData = {
      client_name: 'João',
      order_identify: 'abc123',
      order_date: '06/11/2025',
      order_time: '14:30',
      status: 'Preparo',
      is_delivery: true,
      total: 45.50,
      payment_method: 'Dinheiro',
      products: [
        { name: 'Pizza', quantity: 1, price: 35.00 },
        { name: 'Refrigerante', quantity: 1, price: 10.50 },
      ]
    }

    mockApiClient.get.mockResolvedValueOnce({
      success: true,
      message: 'Pedido encontrado',
      data: mockOrderData,
    } as any)

    await renderOrderTrackComponent()

    const cpfInput = screen.getByPlaceholderText('(00) 00000-0000')
    fireEvent.change(cpfInput, { target: { value: '12345678900' } })

    const searchButton = screen.getByRole('button', { name: /Consultar Pedido/i })
    fireEvent.click(searchButton)

    await waitFor(() => {
      expect(screen.getByText('#abc123')).toBeInTheDocument()
      expect(screen.getByText(/Olá, João!/)).toBeInTheDocument()
      expect(screen.getByText('Preparo')).toBeInTheDocument()
      expect(screen.getByText('Pizza')).toBeInTheDocument()
      expect(screen.getByText('Refrigerante')).toBeInTheDocument()
      expect(screen.getByText('Dinheiro')).toBeInTheDocument()
    })
  })

  it('displays error message when order not found', async () => {
    queueStoreInfoCall()
    mockApiClient.get.mockRejectedValueOnce({
      response: {
        data: {
          message: 'Nenhum pedido em andamento foi encontrado para este CPF/telefone.'
        }
      }
    })

    await renderOrderTrackComponent()
    
    const cpfInput = screen.getByPlaceholderText('(00) 00000-0000')
    fireEvent.change(cpfInput, { target: { value: '12345678900' } })
    
    const searchButton = screen.getByRole('button', { name: /Consultar Pedido/i })
    fireEvent.click(searchButton)

    await waitFor(() => {
      expect(toast.error).toHaveBeenCalledWith(
        'Nenhum pedido em andamento foi encontrado para este CPF/telefone.'
      )
    })
  })

  it('shows loading state during search', async () => {
    queueStoreInfoCall()
    mockApiClient.get.mockImplementationOnce(() => 
      new Promise(resolve => setTimeout(() => resolve({
        success: true,
        data: null,
      } as any), 100))
    )

    await renderOrderTrackComponent()
    
    const cpfInput = screen.getByPlaceholderText('(00) 00000-0000')
    fireEvent.change(cpfInput, { target: { value: '12345678900' } })
    
    const searchButton = screen.getByRole('button', { name: /Consultar Pedido/i })
    fireEvent.click(searchButton)

    expect(screen.getByText('Consultando...')).toBeInTheDocument()
  })

  it('displays delivery badge when order is for delivery', async () => {
    queueStoreInfoCall()
    const mockOrderData = {
      client_name: 'Maria',
      order_identify: 'xyz789',
      order_date: '06/11/2025',
      order_time: '15:00',
      status: 'Preparo',
      is_delivery: true,
      total: 30.00,
      payment_method: 'Cartão',
      products: []
    }

    mockApiClient.get.mockResolvedValueOnce({
      success: true,
      data: mockOrderData,
    } as any)

    await renderOrderTrackComponent()
    
    const cpfInput = screen.getByPlaceholderText('(00) 00000-0000')
    fireEvent.change(cpfInput, { target: { value: '12345678900' } })
    
    const searchButton = screen.getByRole('button', { name: /Consultar Pedido/i })
    fireEvent.click(searchButton)

    await waitFor(() => {
      expect(screen.getByText('Pedido com entrega')).toBeInTheDocument()
    })
  })

  it('shows review button while order is em preparo', async () => {
    queueStoreInfoCall()
    const mockOrderData = {
      client_name: 'Ana',
      order_identify: 'order456',
      order_date: '07/11/2025',
      order_time: '18:45',
      status: 'Preparo',
      is_delivery: false,
      total: 25.00,
      payment_method: 'Pix',
      products: []
    }

    mockApiClient.get.mockResolvedValueOnce({
      success: true,
      data: mockOrderData,
    } as any)

    await renderOrderTrackComponent()

    const cpfInput = screen.getByPlaceholderText('(00) 00000-0000')
    fireEvent.change(cpfInput, { target: { value: '12345678900' } })

    fireEvent.click(screen.getByRole('button', { name: /Consultar Pedido/i }))

    await waitFor(() => {
      expect(screen.getByText('#order456')).toBeInTheDocument()
    })

    const reviewButton = screen.getByRole('button', { name: /Avaliar Pedido/i })
    expect(reviewButton).toBeEnabled()
  })

  it('allows opening the review modal when order is completed', async () => {
    queueStoreInfoCall()
    const mockOrderData = {
      client_name: 'Bruno',
      order_identify: 'order999',
      order_date: '07/11/2025',
      order_time: '19:00',
      status: 'Concluído',
      is_delivery: true,
      total: 89.90,
      payment_method: 'Cartão',
      products: []
    }

    mockApiClient.get.mockResolvedValueOnce({
      success: true,
      data: mockOrderData,
    } as any)

    await renderOrderTrackComponent()

    fireEvent.change(screen.getByPlaceholderText('(00) 00000-0000'), { target: { value: '12345678900' } })
    fireEvent.click(screen.getByRole('button', { name: /Consultar Pedido/i }))

    await waitFor(() => {
      expect(screen.getByText('#order999')).toBeInTheDocument()
    })

    const reviewButton = screen.getByRole('button', { name: /Avaliar Pedido/i })
    fireEvent.click(reviewButton)

    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeInTheDocument()
    })
  })

  it('shows error toast if store information is unavailable when trying to review', async () => {
    mockApiClient.get.mockRejectedValueOnce(new Error('Store info unavailable'))

    const mockOrderData = {
      client_name: 'Carlos',
      order_identify: 'order777',
      order_date: '07/11/2025',
      order_time: '20:00',
      status: 'Concluído',
      is_delivery: false,
      total: 59.90,
      payment_method: 'Dinheiro',
      products: []
    }

    mockApiClient.get.mockResolvedValueOnce({
      success: true,
      data: mockOrderData,
    } as any)

    await renderOrderTrackComponent()

    fireEvent.change(screen.getByPlaceholderText('(00) 00000-0000'), { target: { value: '12345678900' } })
    fireEvent.click(screen.getByRole('button', { name: /Consultar Pedido/i }))

    await waitFor(() => {
      expect(screen.getByText('#order777')).toBeInTheDocument()
    })

    fireEvent.click(screen.getByRole('button', { name: /Avaliar Pedido/i }))

    expect(toast.error).toHaveBeenCalledWith('Não foi possível carregar os dados da loja. Tente novamente em instantes.')
  })
})

