import {
  buildPlanLimitItems,
  buildPlanModuleGroups,
  buildPlanDetailItems,
} from '@/lib/plan-features'

describe('plan-features', () => {
  const basicPlan = {
    max_users: 5,
    max_products: 100,
    max_orders_per_month: 100,
    has_marketing: true,
    has_order_completion_email: true,
    has_reports: false,
    details: [{ name: 'Suporte por e-mail' }],
  }

  it('deve montar limites do plano', () => {
    const limits = buildPlanLimitItems(basicPlan)
    expect(limits).toEqual([
      { label: 'Usuários', value: 'Até 5 usuários' },
      { label: 'Produtos', value: 'Até 100 produtos' },
      { label: 'Pedidos/mês', value: 'Até 100 pedidos' },
    ])
  })

  it('deve montar módulos e ações com status incluído', () => {
    const groups = buildPlanModuleGroups(basicPlan)
    const marketing = groups.find((g) => g.id === 'marketing')
    const reports = groups.find((g) => g.id === 'reports')

    expect(marketing?.options.find((o) => o.key === 'has_marketing')?.included).toBe(true)
    expect(marketing?.options.find((o) => o.key === 'has_order_completion_email')?.included).toBe(true)
    expect(reports?.options.find((o) => o.key === 'has_reports')?.included).toBe(false)
  })

  it('deve montar detalhes adicionais', () => {
    expect(buildPlanDetailItems(basicPlan)).toEqual(['Suporte por e-mail'])
  })

  it('deve tratar limites ilimitados', () => {
    const limits = buildPlanLimitItems({
      max_users: null,
      max_products: 999999,
      max_orders_per_month: undefined,
    })

    expect(limits.every((l) => l.value.includes('Ilimitad'))).toBe(true)
  })
})
