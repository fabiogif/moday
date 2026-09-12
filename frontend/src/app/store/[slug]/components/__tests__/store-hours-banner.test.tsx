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

  it('mostra o badge Fechado e expande os horários ao clicar', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => closedPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    const badge = await screen.findByRole('button', { name: /Fechado/i })
    expect(badge).toHaveAttribute('aria-expanded', 'false')
    expect(screen.queryByText(/Horários de funcionamento/i)).not.toBeInTheDocument()

    fireEvent.click(badge)

    expect(badge).toHaveAttribute('aria-expanded', 'true')
    expect(screen.getByText(/Horários de funcionamento/i)).toBeInTheDocument()
  })

  it('mostra o badge Aberto e permite expandir os horários do dia', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => openPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    const badge = await screen.findByRole('button', { name: /Aberto/i })
    expect(badge).toHaveAttribute('aria-expanded', 'false')

    fireEvent.click(badge)
    expect(badge).toHaveAttribute('aria-expanded', 'true')
    expect(screen.getByText(/Horários de hoje/i)).toBeInTheDocument()
  })

  it('desabilita a expansão quando não há horários informados', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => closedWithoutHoursPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    const badge = await screen.findByRole('button', { name: /Fechado/i })
    expect(badge).toBeDisabled()
  })
})
