import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { act } from 'react-dom/test-utils'
import userEvent from '@testing-library/user-event'
import StoreHoursSettings from '../page'
import { StoreHourFormDialog } from '../components/store-hour-form-dialog'

// Mock dos hooks
jest.mock('@/hooks/use-store-hours', () => ({
  useStoreHours: jest.fn(),
  useStoreHourStats: jest.fn(),
  useStoreStatus: jest.fn(),
  useStoreHour: jest.fn(),
  useStoreHourMutation: jest.fn(),
}))

// Mock do API Client
jest.mock('@/lib/api-client', () => ({
  __esModule: true,
  default: {
    get: jest.fn(),
    post: jest.fn(),
    put: jest.fn(),
    delete: jest.fn(),
  },
  endpoints: {
    storeHours: {
      list: '/api/store-hours',
      stats: '/api/store-hours/stats',
      checkIsOpen: '/api/store-hours/check-is-open',
      setAlwaysOpen: '/api/store-hours/set-always-open',
      removeAlwaysOpen: '/api/store-hours/remove-always-open',
      create: '/api/store-hours',
      show: (uuid: string) => `/api/store-hours/${uuid}`,
      update: (uuid: string) => `/api/store-hours/${uuid}`,
      delete: (uuid: string) => `/api/store-hours/${uuid}`,
    },
  },
}))

// Mock do toast
jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}))

import { useStoreHours, useStoreHourStats } from '@/hooks/use-store-hours'
import apiClient, { endpoints } from '@/lib/api-client'
import { toast } from 'sonner'

const mockUseStoreHours = useStoreHours as jest.MockedFunction<typeof useStoreHours>
const mockUseStoreHourStats = useStoreHourStats as jest.MockedFunction<typeof useStoreHourStats>

describe('StoreHoursSettings', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  describe('Loading State', () => {
    it('should show loading spinner when data is loading', () => {
      mockUseStoreHours.mockReturnValue({
        data: null,
        loading: true,
        error: null,
        refetch: jest.fn(),
      } as any)

      mockUseStoreHourStats.mockReturnValue({
        data: null,
        loading: true,
        error: null,
        refetch: jest.fn(),
      } as any)

      render(<StoreHoursSettings />)

      expect(screen.getByRole('status')).toBeInTheDocument() // Loader2 spinner
    })
  })

  describe('Empty State', () => {
    it('should show empty state when no hours are configured', () => {
      mockUseStoreHours.mockReturnValue({
        data: [],
        loading: false,
        error: null,
        refetch: jest.fn(),
      } as any)

      mockUseStoreHourStats.mockReturnValue({
        data: {
          is_always_open: false,
          total_hours: 0,
          days_configured: 0,
          has_delivery: false,
          has_pickup: false,
        },
        loading: false,
        error: null,
        refetch: jest.fn(),
      } as any)

      render(<StoreHoursSettings />)

      expect(screen.getByText(/Nenhum horário configurado/i)).toBeInTheDocument()
      expect(screen.getByText(/Adicionar Primeiro Horário/i)).toBeInTheDocument()
    })
  })

  describe('Store Hours List', () => {
    const mockStoreHours = [
      {
        id: 1,
        uuid: 'uuid-1',
        tenant_id: 1,
        is_always_open: false,
        day_of_week: 1,
        day_name: 'Segunda-feira',
        day_name_short: 'Seg',
        delivery_type: 'both',
        delivery_type_label: 'Entrega e Retirada',
        start_time: '08:00',
        end_time: '18:00',
        is_active: true,
        created_at: '2025-11-03T00:00:00Z',
        updated_at: '2025-11-03T00:00:00Z',
      },
      {
        id: 2,
        uuid: 'uuid-2',
        tenant_id: 1,
        is_always_open: false,
        day_of_week: 2,
        day_name: 'Terça-feira',
        day_name_short: 'Ter',
        delivery_type: 'delivery',
        delivery_type_label: 'Apenas Entrega',
        start_time: '09:00',
        end_time: '17:00',
        is_active: true,
        created_at: '2025-11-03T00:00:00Z',
        updated_at: '2025-11-03T00:00:00Z',
      },
    ]

    it('should display list of store hours grouped by day', () => {
      mockUseStoreHours.mockReturnValue({
        data: mockStoreHours,
        loading: false,
        error: null,
        refetch: jest.fn(),
      } as any)

      mockUseStoreHourStats.mockReturnValue({
        data: {
          is_always_open: false,
          total_hours: 2,
          days_configured: 2,
          has_delivery: true,
          has_pickup: false,
        },
        loading: false,
        error: null,
        refetch: jest.fn(),
      } as any)

      render(<StoreHoursSettings />)

      expect(screen.getByText('Segunda-feira')).toBeInTheDocument()
      expect(screen.getByText('Terça-feira')).toBeInTheDocument()
      expect(screen.getByText('08:00 - 18:00')).toBeInTheDocument()
      expect(screen.getByText('09:00 - 17:00')).toBeInTheDocument()
    })

    it('should show active/inactive badge', () => {
      mockUseStoreHours.mockReturnValue({
        data: mockStoreHours,
        loading: false,
        error: null,
        refetch: jest.fn(),
      } as any)

      mockUseStoreHourStats.mockReturnValue({
        data: {
          is_always_open: false,
          total_hours: 2,
          days_configured: 2,
          has_delivery: true,
          has_pickup: false,
        },
        loading: false,
        error: null,
        refetch: jest.fn(),
      } as any)

      render(<StoreHoursSettings />)

      const badges = screen.getAllByText('Ativo')
      expect(badges.length).toBeGreaterThan(0)
    })
  })

  describe('Always Open Toggle', () => {
    it('should toggle always open on', async () => {
      const mockRefetch = jest.fn()
      const mockRefetchStats = jest.fn()

      mockUseStoreHours.mockReturnValue({
        data: [],
        loading: false,
        error: null,
        refetch: mockRefetch,
      } as any)

      mockUseStoreHourStats.mockReturnValue({
        data: {
          is_always_open: false,
          total_hours: 0,
          days_configured: 0,
          has_delivery: false,
          has_pickup: false,
        },
        loading: false,
        error: null,
        refetch: mockRefetchStats,
      } as any)

      ;(apiClient.post as jest.Mock).mockResolvedValue({
        success: true,
        message: 'Loja configurada como sempre aberta',
      })

      render(<StoreHoursSettings />)

      const toggle = screen.getByRole('switch')
      await act(async () => {
        fireEvent.click(toggle)
      })

      await waitFor(() => {
        expect(apiClient.post).toHaveBeenCalledWith(
          endpoints.storeHours.setAlwaysOpen,
          { delivery_type: 'both' }
        )
        expect(toast.success).toHaveBeenCalledWith('Loja configurada como sempre aberta')
        expect(mockRefetch).toHaveBeenCalled()
        expect(mockRefetchStats).toHaveBeenCalled()
      })
    })

    it('should toggle always open off', async () => {
      const mockRefetch = jest.fn()
      const mockRefetchStats = jest.fn()

      mockUseStoreHours.mockReturnValue({
        data: [],
        loading: false,
        error: null,
        refetch: mockRefetch,
      } as any)

      mockUseStoreHourStats.mockReturnValue({
        data: {
          is_always_open: true,
          total_hours: 0,
          days_configured: 0,
          has_delivery: false,
          has_pickup: false,
        },
        loading: false,
        error: null,
        refetch: mockRefetchStats,
      } as any)

      ;(apiClient.post as jest.Mock).mockResolvedValue({
        success: true,
        message: "Configuração 'sempre aberto' removida",
      })

      render(<StoreHoursSettings />)

      const toggle = screen.getByRole('switch')
      expect(toggle).toBeChecked()

      await act(async () => {
        fireEvent.click(toggle)
      })

      await waitFor(() => {
        expect(apiClient.post).toHaveBeenCalledWith(
          endpoints.storeHours.removeAlwaysOpen,
          {}
        )
        expect(toast.success).toHaveBeenCalledWith("Configuração 'sempre aberto' removida")
        expect(mockRefetch).toHaveBeenCalled()
        expect(mockRefetchStats).toHaveBeenCalled()
      })
    })
  })

  describe('Statistics Display', () => {
    it('should display statistics correctly', () => {
      mockUseStoreHours.mockReturnValue({
        data: [],
        loading: false,
        error: null,
        refetch: jest.fn(),
      } as any)

      mockUseStoreHourStats.mockReturnValue({
        data: {
          is_always_open: false,
          total_hours: 10,
          days_configured: 5,
          has_delivery: true,
          has_pickup: true,
        },
        loading: false,
        error: null,
        refetch: jest.fn(),
      } as any)

      render(<StoreHoursSettings />)

      expect(screen.getByText('10')).toBeInTheDocument() // Total de Horários
      expect(screen.getByText('5/7')).toBeInTheDocument() // Dias Configurados
    })
  })

  describe('Delete Store Hour', () => {
    it('should delete store hour successfully', async () => {
      const mockRefetch = jest.fn()
      const mockRefetchStats = jest.fn()

      const mockStoreHours = [
        {
          id: 1,
          uuid: 'uuid-1',
          tenant_id: 1,
          is_always_open: false,
          day_of_week: 1,
          day_name: 'Segunda-feira',
          delivery_type: 'both',
          delivery_type_label: 'Entrega e Retirada',
          start_time: '08:00',
          end_time: '18:00',
          is_active: true,
          created_at: '2025-11-03T00:00:00Z',
          updated_at: '2025-11-03T00:00:00Z',
        },
      ]

      mockUseStoreHours.mockReturnValue({
        data: mockStoreHours,
        loading: false,
        error: null,
        refetch: mockRefetch,
      } as any)

      mockUseStoreHourStats.mockReturnValue({
        data: {
          is_always_open: false,
          total_hours: 1,
          days_configured: 1,
          has_delivery: true,
          has_pickup: false,
        },
        loading: false,
        error: null,
        refetch: mockRefetchStats,
      } as any)

      ;(apiClient.delete as jest.Mock).mockResolvedValue({
        success: true,
        message: 'Horário excluído com sucesso',
      })

      render(<StoreHoursSettings />)

      const deleteButton = screen.getAllByRole('button').find(
        button => button.querySelector('svg')?.classList.contains('lucide-trash-2')
      )

      await act(async () => {
        fireEvent.click(deleteButton!)
      })

      // Confirmar no dialog
      const confirmButton = await screen.findByText('Excluir')
      await act(async () => {
        fireEvent.click(confirmButton)
      })

      await waitFor(() => {
        expect(apiClient.delete).toHaveBeenCalledWith(
          endpoints.storeHours.delete('uuid-1')
        )
        expect(toast.success).toHaveBeenCalledWith('Horário excluído com sucesso')
        expect(mockRefetch).toHaveBeenCalled()
        expect(mockRefetchStats).toHaveBeenCalled()
      })
    })
  })
})

describe('StoreHourFormDialog', () => {
  const mockOnSuccess = jest.fn()
  const mockOnOpenChange = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render form fields', () => {
    render(
      <StoreHourFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        onSuccess={mockOnSuccess}
      />
    )

    expect(screen.getByText(/Dias da Semana/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Segunda-feira/i)).toBeInTheDocument()
    expect(screen.getByText(/Horário de Início|Abre às/i)).toBeInTheDocument()
    expect(screen.getByText(/Horário de Término|Fecha às/i)).toBeInTheDocument()
    expect(screen.getByText(/Tipo de Serviço/i)).toBeInTheDocument()
    expect(screen.getByText(/Horário ativo/i)).toBeInTheDocument()
  })

  it('should prefill default values on create', () => {
    render(
      <StoreHourFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        onSuccess={mockOnSuccess}
      />
    )

    expect(screen.getByDisplayValue('08:00')).toBeInTheDocument()
    expect(screen.getByDisplayValue('18:00')).toBeInTheDocument()
  })

  it('should validate end time is after start time', async () => {
    render(
      <StoreHourFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        onSuccess={mockOnSuccess}
      />
    )

    const startTimeInput = screen.getByLabelText(/Horário de Início/i)
    const endTimeInput = screen.getByLabelText(/Horário de Término/i)

    await userEvent.clear(startTimeInput)
    await userEvent.type(startTimeInput, '18:00')

    await userEvent.clear(endTimeInput)
    await userEvent.type(endTimeInput, '08:00')

    const submitButton = screen.getByRole('button', { name: /Adicionar/i })

    await act(async () => {
      fireEvent.click(submitButton)
    })

    await waitFor(() => {
      expect(screen.getByText(/O horário de término.*deve ser posterior|horário de término é obrigatório/i)).toBeInTheDocument()
    })
  })

  it('should submit form successfully', async () => {
    ;(apiClient.post as jest.Mock).mockResolvedValue({
      success: true,
      message: 'Horário criado com sucesso',
    })

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: [],
    })

    render(
      <StoreHourFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        onSuccess={mockOnSuccess}
      />
    )

    const submitButton = screen.getByRole('button', { name: /Adicionar/i })

    await act(async () => {
      fireEvent.click(submitButton)
    })

    await waitFor(() => {
      expect(apiClient.post).toHaveBeenCalledWith(
        endpoints.storeHours.create,
        expect.objectContaining({
          day_of_week: 1,
          delivery_type: 'both',
          is_always_open: false,
        })
      )
      expect(toast.success).toHaveBeenCalledWith('Horário criado com sucesso')
      expect(mockOnSuccess).toHaveBeenCalled()
    })
  })

  it('should create hours for multiple selected days', async () => {
    ;(apiClient.post as jest.Mock).mockResolvedValue({
      success: true,
      message: 'Horário criado com sucesso',
    })

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: [],
    })

    render(
      <StoreHourFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        onSuccess={mockOnSuccess}
      />
    )

    fireEvent.click(screen.getByRole('button', { name: /Seg-Sex/i }))

    const submitButton = screen.getByRole('button', { name: /Adicionar/i })

    await act(async () => {
      fireEvent.click(submitButton)
    })

    await waitFor(() => {
      expect(apiClient.post).toHaveBeenCalledTimes(5)
      expect(toast.success).toHaveBeenCalledWith('5 horários criados com sucesso')
      expect(mockOnSuccess).toHaveBeenCalled()
    })
  })

  it('should detect overlapping hours', async () => {
    const existingHours = [
      {
        uuid: 'existing-uuid',
        day_of_week: 1,
        day_name: 'Segunda-feira',
        start_time: '08:00',
        end_time: '12:00',
        is_active: true,
      },
    ]

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: existingHours,
    })

    render(
      <StoreHourFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        onSuccess={mockOnSuccess}
      />
    )

    const submitButton = screen.getByRole('button', { name: /Adicionar/i })

    await act(async () => {
      fireEvent.click(submitButton)
    })

    await waitFor(() => {
      expect(toast.error).toHaveBeenCalledWith(
        expect.stringContaining('Já existe um horário cadastrado')
      )
      expect(mockOnSuccess).not.toHaveBeenCalled()
    })
  })

  it('should populate form when editing', () => {
    const existingHour = {
      id: 1,
      uuid: 'uuid-1',
      tenant_id: 1,
      is_always_open: false,
      day_of_week: 1,
      day_name: 'Segunda-feira',
      delivery_type: 'both' as const,
      delivery_type_label: 'Entrega e Retirada',
      start_time: '08:00',
      end_time: '18:00',
      is_active: true,
      created_at: '2025-11-03T00:00:00Z',
      updated_at: '2025-11-03T00:00:00Z',
    }

    render(
      <StoreHourFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        hour={existingHour}
        onSuccess={mockOnSuccess}
      />
    )

    expect(screen.getByText(/Dia da Semana/i)).toBeInTheDocument()
    expect(screen.getByDisplayValue('08:00')).toBeInTheDocument()
    expect(screen.getByDisplayValue('18:00')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Atualizar/i })).toBeInTheDocument()
  })
})

