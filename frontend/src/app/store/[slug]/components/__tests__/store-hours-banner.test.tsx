import { act, fireEvent, render, screen } from '@testing-library/react'
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
    jest.useFakeTimers()
    global.fetch = jest.fn()
  })

  afterEach(() => {
    jest.useRealTimers()
    jest.restoreAllMocks()
  })

  it('mostra o status fechado e recolhe os horários depois de alguns segundos', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => closedPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    expect(await screen.findByText('🔴 Loja fechada no momento')).toBeInTheDocument()
    expect(screen.getByText(/Nossos horários de funcionamento/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /loja fechada no momento/i })).toHaveAttribute('aria-expanded', 'true')

    act(() => {
      jest.advanceTimersByTime(4500)
    })

    expect(screen.getByRole('button', { name: /loja fechada no momento/i })).toHaveAttribute('aria-expanded', 'false')
    expect(screen.getByText('🔴 Loja fechada no momento')).toBeInTheDocument()
  })

  it('expande os horários ao clicar no status quando eles foram informados', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => closedPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)
    await screen.findByText('🔴 Loja fechada no momento')

    act(() => {
      jest.advanceTimersByTime(4500)
    })

    fireEvent.click(screen.getByRole('button', { name: /loja fechada no momento/i }))

    expect(screen.getByRole('button', { name: /loja fechada no momento/i })).toHaveAttribute('aria-expanded', 'true')
    expect(screen.getByText(/Nossos horários de funcionamento/i)).toBeInTheDocument()
  })

  it('mostra Loja aberta e permite expandir os horários do dia', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => openPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    expect(await screen.findByText('🟢 Loja aberta')).toBeInTheDocument()
    expect(screen.getByText(/Horários de hoje/i)).toBeInTheDocument()

    act(() => {
      jest.advanceTimersByTime(4500)
    })

    fireEvent.click(screen.getByRole('button', { name: /loja aberta/i }))
    expect(screen.getByRole('button', { name: /loja aberta/i })).toHaveAttribute('aria-expanded', 'true')
    expect(screen.getByText(/Horários de hoje/i)).toBeInTheDocument()
  })

  it('não mostra controle de expansão quando não há horários informados', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      json: async () => closedWithoutHoursPayload,
    })

    render(<StoreHoursBanner slug="loja-teste" />)

    expect(await screen.findByText('🔴 Loja fechada no momento')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /ver horários/i })).not.toBeInTheDocument()
  })
})
