import { fireEvent, render, screen } from '@testing-library/react'
import { StoreHoursBanner } from '../store-hours-banner'

jest.mock('@/lib/api-config', () => ({
  buildApiUrl: (path: string) => path,
}))

const closedPayload = {
  success: true,
  data: {
    is_open: false,
    is_always_open: false,
    current_time: '22:00',
    current_day: 'Segunda',
    store_hours: {
      Segunda: [{ start: '11:00', end: '15:00', delivery_type: 'all' }],
      Terça: [{ start: '11:00', end: '15:00', delivery_type: 'all' }],
    },
  },
}

const openPayload = {
  success: true,
  data: {
    is_open: true,
    is_always_open: false,
    current_time: '12:00',
    current_day: 'Segunda',
    store_hours: {
      Segunda: [{ start: '11:00', end: '15:00', delivery_type: 'all' }],
    },
  },
}

const closedWithoutHoursPayload = {
  success: true,
  data: {
    is_open: false,
    is_always_open: false,
    current_time: '22:00',
    current_day: 'Segunda',
    store_hours: {},
  },
}

describe('StoreHoursBanner', () => {
  beforeEach(() => {
    global.fetch = jest.fn()
  })

  afterEach(() => {
    jest.restoreAllMocks()
  })

  it('inicia oculto ao carregar e expande ao clicar no status', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => closedPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    expect(await screen.findByText('🔴 Restaurante fechado no momento')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /restaurante fechado no momento/i })).toHaveAttribute('aria-expanded', 'false')

    fireEvent.click(screen.getByRole('button', { name: /restaurante fechado no momento/i }))

    expect(screen.getByRole('button', { name: /restaurante fechado no momento/i })).toHaveAttribute('aria-expanded', 'true')
    expect(screen.getByText(/Nossos horários de funcionamento/i)).toBeInTheDocument()
  })

  it('mostra Restaurante aberto e permite expandir os horários do dia', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => openPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    expect(await screen.findByText('🟢 Restaurante aberto')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /restaurante aberto/i })).toHaveAttribute('aria-expanded', 'false')

    fireEvent.click(screen.getByRole('button', { name: /restaurante aberto/i }))
    expect(screen.getByRole('button', { name: /restaurante aberto/i })).toHaveAttribute('aria-expanded', 'true')
    expect(screen.getByText(/Horários de hoje/i)).toBeInTheDocument()
  })

  it('não mostra controle de expansão quando não há horários informados', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => closedWithoutHoursPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    expect(await screen.findByText('🔴 Restaurante fechado no momento')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /ver horários/i })).not.toBeInTheDocument()
  })
})
