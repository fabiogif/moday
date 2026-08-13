import { render, screen, within } from '@testing-library/react'
import FinancialDashboardPage from '../page'
import { useExpenseStats } from '@/hooks/use-expenses'
import { useAccountPayableStats } from '@/hooks/use-accounts-payable'
import { useAccountReceivableStats } from '@/hooks/use-accounts-receivable'
import { formatFinancialCurrency } from '@/lib/financial-dashboard-metrics'

jest.mock('@/hooks/use-expenses')
jest.mock('@/hooks/use-accounts-payable')
jest.mock('@/hooks/use-accounts-receivable')

const mockPush = jest.fn()

jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

const receivableStats = {
  total_pending: 3000,
  total_received: 2000,
  total_overdue: 500,
}

const payableStats = {
  total_pending: 1500,
  total_paid: 1000,
  total_overdue: 500,
}

const expenseStats = {
  total_month: 800,
  pending: 200,
  paid_month: 600,
}

function normalizeCurrencyText(text: string): string {
  return text.replace(/\s/g, '')
}

function hasCurrency(value: number) {
  const expected = normalizeCurrencyText(formatFinancialCurrency(value))
  return (text: string, element?: Element | null) =>
    normalizeCurrencyText(element?.textContent ?? text).includes(expected)
}

function getKpiCard(label: string): HTMLElement {
  const description = screen.getByText(label)
  const card = description.closest('[data-slot="card"]')
  if (!card) {
    throw new Error(`Card não encontrado para o KPI "${label}"`)
  }
  return card as HTMLElement
}

function getKpiCardValue(label: string): string {
  const card = getKpiCard(label)
  const title = card.querySelector('[data-slot="card-title"]')
  return title?.textContent ?? ''
}

describe('FinancialDashboardPage', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(useAccountReceivableStats as jest.Mock).mockReturnValue({ data: receivableStats })
    ;(useAccountPayableStats as jest.Mock).mockReturnValue({ data: payableStats })
    ;(useExpenseStats as jest.Mock).mockReturnValue({ data: expenseStats })
  })

  it('renderiza o título do painel financeiro', () => {
    render(<FinancialDashboardPage />)

    expect(screen.getByText('Painel de Controle Financeiro')).toBeInTheDocument()
    expect(screen.getByText('Acesso Rápido')).toBeInTheDocument()
  })

  it('exibe KPIs calculados a partir dos campos corretos da API', () => {
    render(<FinancialDashboardPage />)

    expect(normalizeCurrencyText(getKpiCardValue('Total a Receber'))).toBe(
      normalizeCurrencyText(formatFinancialCurrency(5000)),
    )
    expect(normalizeCurrencyText(getKpiCardValue('Total a Pagar'))).toBe(
      normalizeCurrencyText(formatFinancialCurrency(2000)),
    )
    expect(normalizeCurrencyText(getKpiCardValue('Saldo Projetado'))).toBe(
      normalizeCurrencyText(formatFinancialCurrency(2200)),
    )
    expect(normalizeCurrencyText(getKpiCardValue('Despesas do Mês'))).toBe(
      normalizeCurrencyText(formatFinancialCurrency(800)),
    )
  })

  it('exibe detalhes do KPI Total a Receber com total_received', () => {
    render(<FinancialDashboardPage />)

    const receivableCard = getKpiCard('Total a Receber')
    expect(
      within(receivableCard).getByText((text) => text.includes('Recebido:') && hasCurrency(2000)(text))
    ).toBeInTheDocument()
    expect(within(receivableCard).getByText(/40\s*%/)).toBeInTheDocument()
  })

  it('exibe detalhes do KPI Total a Pagar com total_overdue', () => {
    render(<FinancialDashboardPage />)

    expect(screen.getByText(/Vencido:/)).toBeInTheDocument()
    expect(screen.getAllByText(hasCurrency(500)).length).toBeGreaterThan(0)
    expect(screen.getByText(/25\s*%/)).toBeInTheDocument()
  })

  it('exibe valores do Acesso Rápido a partir dos campos corretos', () => {
    render(<FinancialDashboardPage />)

    expect(screen.getByText((text) => text.includes('pendente') && hasCurrency(3000)(text))).toBeInTheDocument()
    expect(screen.getByText((text) => text.includes('em aberto') && hasCurrency(2000)(text))).toBeInTheDocument()
    expect(screen.getByText((text) => text.includes('este mês') && hasCurrency(800)(text))).toBeInTheDocument()
    expect(screen.getByText('Gerencie seus fornecedores')).toBeInTheDocument()
    expect(screen.getByText('Categorias financeiras')).toBeInTheDocument()
    expect(screen.getByText('Contas e transações')).toBeInTheDocument()
  })

  it('exibe alerta de contas vencidas quando há total_overdue', () => {
    render(<FinancialDashboardPage />)

    expect(screen.getByText(/Você tem .+ em contas vencidas/)).toBeInTheDocument()
    expect(screen.getAllByText(hasCurrency(500)).length).toBeGreaterThan(0)
  })

  it('exibe zeros nos KPIs quando os hooks não retornam dados', () => {
    ;(useAccountReceivableStats as jest.Mock).mockReturnValue({ data: undefined })
    ;(useAccountPayableStats as jest.Mock).mockReturnValue({ data: undefined })
    ;(useExpenseStats as jest.Mock).mockReturnValue({ data: undefined })

    render(<FinancialDashboardPage />)

    expect(normalizeCurrencyText(getKpiCardValue('Total a Receber'))).toBe(
      normalizeCurrencyText(formatFinancialCurrency(0)),
    )
    expect(normalizeCurrencyText(getKpiCardValue('Total a Pagar'))).toBe(
      normalizeCurrencyText(formatFinancialCurrency(0)),
    )
    expect(normalizeCurrencyText(getKpiCardValue('Saldo Projetado'))).toBe(
      normalizeCurrencyText(formatFinancialCurrency(0)),
    )
    expect(normalizeCurrencyText(getKpiCardValue('Despesas do Mês'))).toBe(
      normalizeCurrencyText(formatFinancialCurrency(0)),
    )
    expect(screen.getByText((text) => text.includes('pendente') && hasCurrency(0)(text))).toBeInTheDocument()
    expect(screen.getByText((text) => text.includes('em aberto') && hasCurrency(0)(text))).toBeInTheDocument()
    expect(screen.getByText((text) => text.includes('este mês') && hasCurrency(0)(text))).toBeInTheDocument()
  })
})
