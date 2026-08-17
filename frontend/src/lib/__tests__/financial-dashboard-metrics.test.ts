import {
  computeFinancialDashboardMetrics,
  formatFinancialCurrency,
  parseFinancialAmount,
} from '../financial-dashboard-metrics'

describe('parseFinancialAmount', () => {
  it('returns 0 for nullish values', () => {
    expect(parseFinancialAmount(null)).toBe(0)
    expect(parseFinancialAmount(undefined)).toBe(0)
  })

  it('parses numbers and localized strings', () => {
    expect(parseFinancialAmount(1500.5)).toBe(1500.5)
    expect(parseFinancialAmount('1.500,50')).toBe(1500.5)
    expect(parseFinancialAmount('1500.50')).toBe(1500.5)
  })
})

describe('computeFinancialDashboardMetrics', () => {
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

  it('calcula Total a Receber a partir de total_pending + total_received', () => {
    const metrics = computeFinancialDashboardMetrics(receivableStats, payableStats, expenseStats)

    expect(metrics.totalReceivable).toBe(5000)
    expect(metrics.totalReceived).toBe(2000)
    expect(metrics.receiveProgress).toBe(40)
  })

  it('calcula Total a Pagar a partir de total_pending + total_overdue', () => {
    const metrics = computeFinancialDashboardMetrics(receivableStats, payableStats, expenseStats)

    expect(metrics.payableTotal).toBe(2000)
    expect(metrics.totalPayablePending).toBe(1500)
    expect(metrics.totalOverdue).toBe(500)
    expect(metrics.overduePercent).toBe(25)
  })

  it('calcula Despesas do Mês a partir de expenseStats.total_month', () => {
    const metrics = computeFinancialDashboardMetrics(receivableStats, payableStats, expenseStats)

    expect(metrics.totalExpenses).toBe(800)
  })

  it('calcula Saldo Projetado subtraindo despesas e contas a pagar', () => {
    const metrics = computeFinancialDashboardMetrics(receivableStats, payableStats, expenseStats)

    // 5000 - 800 - 1500 - 500 = 2200
    expect(metrics.balance).toBe(2200)
  })

  it('retorna zeros quando os stats não estão disponíveis', () => {
    const metrics = computeFinancialDashboardMetrics(null, null, null)

    expect(metrics).toEqual({
      totalReceivable: 0,
      totalReceived: 0,
      totalExpenses: 0,
      totalPayablePending: 0,
      totalOverdue: 0,
      payableTotal: 0,
      balance: 0,
      receiveProgress: 0,
      overduePercent: 0,
    })
  })
})

describe('formatFinancialCurrency', () => {
  it('formata valores em BRL', () => {
    expect(formatFinancialCurrency(2200)).toBe('R$\u00a02.200,00')
  })
})
