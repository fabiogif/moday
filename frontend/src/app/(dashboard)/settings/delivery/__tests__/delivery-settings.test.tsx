import React from 'react'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import '@testing-library/jest-dom'
import DeliverySettingsPage from '../page'
import { apiClient } from '@/lib/api-client'
import { useAuth } from '@/contexts/auth-context'

// Mock do api-client
jest.mock('@/lib/api-client', () => ({
  apiClient: {
    get: jest.fn(),
    put: jest.fn(),
  },
}))

// Mock do useAuth
jest.mock('@/contexts/auth-context', () => ({
  useAuth: jest.fn(),
}))

// Mock do useToast
jest.mock('@/hooks/use-toast', () => ({
  useToast: () => ({
    toast: jest.fn(),
  }),
}))

// Mock das zonas de taxa por bairro (CRUD próprio, testado separadamente)
jest.mock('@/hooks/use-delivery-fee-zones', () => ({
  useDeliveryFeeZones: () => ({ data: [], refetch: jest.fn() }),
  useDeliveryFeeZoneMutation: () => ({ mutate: jest.fn(), loading: false }),
}))

const mockUserData = {
  success: true,
  data: {
    tenant: {
      uuid: 'test-uuid-123',
      settings: {
        delivery_pickup: {
          pickup_enabled: true,
          pickup_time_minutes: 35,
          pickup_discount_enabled: false,
          pickup_discount_percent: 0,
          delivery_enabled: true,
          delivery_minimum_order_enabled: true,
          delivery_minimum_order_value: 20.00,
          delivery_free_above_enabled: false,
          delivery_free_above_value: 300.00,
        }
      }
    }
  }
}

describe('DeliverySettingsPage - Configurações de Delivery e Retirada', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(useAuth as jest.Mock).mockReturnValue({
      user: { id: 1, name: 'Test User' },
      isAuthenticated: true,
    })
    ;(apiClient.get as jest.Mock).mockResolvedValue(mockUserData)
  })

  /**
   * TESTE 1: Renderizar página corretamente
   */
  test('should render delivery settings page', async () => {
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
      // "Retirada no Local" e "Delivery (Entrega)" aparecem tanto no título do card quanto no resumo
      expect(screen.getAllByText('Retirada no Local').length).toBeGreaterThan(0)
      expect(screen.getAllByText('Delivery (Entrega)').length).toBeGreaterThan(0)
    })
  })

  /**
   * TESTE 2: Carregar configurações salvas
   */
  test('should load saved settings from API', async () => {
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(apiClient.get).toHaveBeenCalledWith('/api/auth/me')
    })

    // Verificar que os valores foram carregados
    const pickupTimeInput = await screen.findByLabelText(/Tempo para Retirada/i)
    expect(pickupTimeInput).toHaveValue(35)
  })

  /**
   * TESTE 3: Desabilitar retirada no local
   */
  test('should disable pickup fields when pickup is disabled', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    // Encontrar switch de retirada
    const pickupSwitch = screen.getByRole('switch', { name: /Permitir Retirada no Local/i })
    
    // Desabilitar retirada
    await user.click(pickupSwitch)

    // Verificar que o campo de tempo ficou desabilitado
    const pickupTimeInput = screen.getByLabelText(/Tempo para Retirada/i)
    expect(pickupTimeInput).toBeDisabled()
  })

  /**
   * TESTE 4: Habilitar desconto para retirada
   */
  test('should show discount field when pickup discount is enabled', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    // Habilitar desconto
    const discountSwitch = screen.getByRole('switch', { name: /Desconto para Retirada/i })
    await user.click(discountSwitch)

    // Verificar que o campo de percentual ficou habilitado
    const discountInput = screen.getByLabelText(/Percentual de Desconto/i)
    expect(discountInput).not.toBeDisabled()
  })

  /**
   * TESTE 5: Desabilitar delivery
   */
  test('should disable delivery fields when delivery is disabled', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    // Desabilitar delivery
    const deliverySwitch = screen.getByRole('switch', { name: /Permitir Delivery/i })
    await user.click(deliverySwitch)

    // Verificar que os switches dependentes ficaram desabilitados
    const minimumOrderSwitch = screen.getByRole('switch', { name: /Exigir Pedido Mínimo/i })
    expect(minimumOrderSwitch).toBeDisabled()
  })

  /**
   * TESTE 6: Habilitar pedido mínimo no delivery
   */
  test('should show minimum order value when enabled', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    // Campo de valor mínimo deve estar habilitado (já vem ativo por padrão)
    const minimumValueInput = screen.getByLabelText(/Valor Mínimo do Pedido/i)
    expect(minimumValueInput).not.toBeDisabled()
    expect(minimumValueInput).toHaveValue(20)
  })

  /**
   * TESTE 7: Habilitar entrega grátis
   */
  test('should show free delivery value when enabled', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    // Habilitar entrega grátis
    const freeDeliverySwitch = screen.getByRole('switch', { name: /Entrega Grátis Acima de/i })
    await user.click(freeDeliverySwitch)

    // Verificar que o campo de valor ficou habilitado
    const freeValueInput = screen.getByLabelText(/Valor para Entrega Grátis/i)
    expect(freeValueInput).not.toBeDisabled()
  })

  /**
   * TESTE 8: Salvar configurações
   */
  test('should save settings when clicking save button', async () => {
    const user = userEvent.setup()
    
    ;(apiClient.put as jest.Mock).mockResolvedValue({
      success: true,
      message: 'Configurações salvas',
    })

    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    // Alterar tempo de retirada
    const pickupTimeInput = screen.getByLabelText(/Tempo para Retirada/i)
    await user.clear(pickupTimeInput)
    await user.type(pickupTimeInput, '45')

    // Salvar
    const saveButton = screen.getByRole('button', { name: /Salvar/i })
    await user.click(saveButton)

    await waitFor(() => {
      expect(apiClient.put).toHaveBeenCalledWith(
        '/api/tenant/test-uuid-123',
        expect.objectContaining({
          settings: expect.objectContaining({
            delivery_pickup: expect.objectContaining({
              pickup_time_minutes: 45
            })
          })
        })
      )
    })
  })

  /**
   * TESTE 9: Validar valores numéricos positivos
   */
  test('should only accept positive numbers', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    const pickupTimeInput = screen.getByLabelText(/Tempo para Retirada/i)
    
    // Input deve ter min="0"
    expect(pickupTimeInput).toHaveAttribute('min', '0')
    expect(pickupTimeInput).toHaveAttribute('type', 'number')
  })

  /**
   * TESTE 10: Exibir resumo das configurações
   */
  test('should display configuration preview', async () => {
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Resumo das Configurações')).toBeInTheDocument()
    })

    // Verificar preview de retirada
    expect(screen.getByText(/Pronto em aproximadamente 35 minutos/i)).toBeInTheDocument()

    // Verificar preview de delivery
    expect(screen.getByText(/Pedido mínimo: R\$ 20\.00/i)).toBeInTheDocument()
  })

  /**
   * TESTE 11: Campos desabilitados têm classe visual
   */
  test('should apply disabled styles to disabled inputs', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    // Desabilitar retirada
    const pickupSwitch = screen.getByRole('switch', { name: /Permitir Retirada no Local/i })
    await user.click(pickupSwitch)

    // Verificar classe de desabilitado
    const pickupTimeInput = screen.getByLabelText(/Tempo para Retirada/i)
    expect(pickupTimeInput).toHaveClass('cursor-not-allowed')
  })

  /**
   * TESTE 12: Mostrar alerta de mudanças não salvas
   */
  test('should show unsaved changes alert', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    // Fazer uma mudança
    const pickupTimeInput = screen.getByLabelText(/Tempo para Retirada/i)
    await user.clear(pickupTimeInput)
    await user.type(pickupTimeInput, '40')

    // Verificar alerta
    await waitFor(() => {
      expect(screen.getByText(/Você tem alterações não salvas/i)).toBeInTheDocument()
    })
  })

  /**
   * TESTE 13: Card de documento na nota (CPF/CNPJ)
   */
  test('should render invoice document card with ask and required toggles', async () => {
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Documento na Nota')).toBeInTheDocument()
    })

    const askSwitch = screen.getByRole('switch', { name: /Perguntar CPF ou CNPJ na Nota/i })
    const requiredSwitch = screen.getByRole('switch', { name: /CPF ou CNPJ Obrigatório/i })

    expect(askSwitch).not.toBeChecked()
    expect(requiredSwitch).toBeDisabled()
  })

  /**
   * TESTE 14: Habilitar obrigatoriedade de documento apenas quando "perguntar" está ativo
   */
  test('should enable required document switch only when ask is enabled', async () => {
    const user = userEvent.setup()
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Documento na Nota')).toBeInTheDocument()
    })

    const askSwitch = screen.getByRole('switch', { name: /Perguntar CPF ou CNPJ na Nota/i })
    await user.click(askSwitch)

    const requiredSwitch = screen.getByRole('switch', { name: /CPF ou CNPJ Obrigatório/i })
    expect(requiredSwitch).not.toBeDisabled()
  })

  /**
   * TESTE 15: Card de taxa de entrega com modo padrão "Taxa Única"
   */
  test('should render delivery fee card defaulting to single fee mode', async () => {
    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Taxa de Entrega')).toBeInTheDocument()
    })

    expect(screen.getByText('Modelo de Cobrança')).toBeInTheDocument()
    expect(screen.getByText('Valor da Taxa (R$)')).toBeInTheDocument()
  })

  /**
   * TESTE 16: Salvar inclui invoice_document e delivery_fee no payload
   */
  test('should include invoice_document and delivery_fee in save payload', async () => {
    const user = userEvent.setup()

    ;(apiClient.put as jest.Mock).mockResolvedValue({
      success: true,
      message: 'Configurações salvas',
    })

    render(<DeliverySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Delivery e Retirada')).toBeInTheDocument()
    })

    const askSwitch = screen.getByRole('switch', { name: /Perguntar CPF ou CNPJ na Nota/i })
    await user.click(askSwitch)

    const saveButton = screen.getByRole('button', { name: /Salvar/i })
    await user.click(saveButton)

    await waitFor(() => {
      expect(apiClient.put).toHaveBeenCalledWith(
        '/api/tenant/test-uuid-123',
        expect.objectContaining({
          settings: expect.objectContaining({
            invoice_document: expect.objectContaining({ ask_enabled: true }),
            delivery_pickup: expect.objectContaining({
              delivery_fee: expect.objectContaining({ mode: 'unica' }),
            }),
          })
        })
      )
    })
  })
})

