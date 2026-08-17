/**
 * Testes para o componente PlanCard
 */

import { render, screen, fireEvent } from '@testing-library/react'
import { PlanCard, type Plan } from '@/components/plan-card'

const mockPlan: Plan = {
  id: 1,
  name: 'Básico',
  url: 'basico',
  price: 49.90,
  description: 'Plano básico',
  max_users: 5,
  max_products: 100,
  max_orders_per_month: 100,
  has_marketing: true,
  has_order_completion_email: true,
  has_reports: true,
  details: [
    { name: 'Suporte por email' },
    { name: 'Cardápio digital' },
  ],
}

describe('PlanCard', () => {
  it('deve renderizar informações do plano corretamente', () => {
    const onMigrate = jest.fn()

    render(<PlanCard plan={mockPlan} isCurrentPlan={false} onMigrate={onMigrate} />)

    expect(screen.getByText('Básico')).toBeInTheDocument()
    expect(screen.getByText('R$ 49,90')).toBeInTheDocument()
    expect(screen.getByText('/mês')).toBeInTheDocument()
    expect(screen.getByText(/Até 5 usuários/i)).toBeInTheDocument()
    expect(screen.getByText(/Até 100 produtos/i)).toBeInTheDocument()
    expect(screen.getByText(/Até 100 pedidos/i)).toBeInTheDocument()
  })

  it('deve exibir badge "Plano Atual" quando isCurrentPlan é true', () => {
    const onMigrate = jest.fn()

    render(<PlanCard plan={mockPlan} isCurrentPlan={true} onMigrate={onMigrate} />)

    expect(screen.getAllByText('Plano Atual').length).toBeGreaterThan(0)
  })

  it('deve desabilitar botão quando isCurrentPlan é true', () => {
    const onMigrate = jest.fn()

    render(<PlanCard plan={mockPlan} isCurrentPlan={true} onMigrate={onMigrate} />)

    const button = screen.getByRole('button', { name: /Plano Atual/i })
    expect(button).toBeDisabled()
  })

  it('deve chamar onMigrate ao clicar no botão', () => {
    const onMigrate = jest.fn()

    render(<PlanCard plan={mockPlan} isCurrentPlan={false} onMigrate={onMigrate} />)

    const button = screen.getByText('Migrar para este Plano')
    fireEvent.click(button)

    expect(onMigrate).toHaveBeenCalledWith(mockPlan.id)
  })

  it('deve exibir "Ilimitado" para limites null ou >= 999999', () => {
    const unlimitedPlan: Plan = {
      ...mockPlan,
      max_users: 999999,
      max_products: null,
      max_orders_per_month: null,
    }

    const onMigrate = jest.fn()

    render(<PlanCard plan={unlimitedPlan} isCurrentPlan={false} onMigrate={onMigrate} />)

    expect(screen.getAllByText(/Ilimitados/i).length).toBeGreaterThanOrEqual(3)
  })

  it('deve exibir ícone de coroa para planos premium', () => {
    const premiumPlan: Plan = {
      ...mockPlan,
      max_users: 999999,
      max_products: 999999,
      max_orders_per_month: null,
    }

    const onMigrate = jest.fn()

    const { container } = render(
      <PlanCard plan={premiumPlan} isCurrentPlan={false} onMigrate={onMigrate} />
    )

    // Verificar se há um ícone de coroa (Crown)
    const crownIcon = container.querySelector('svg')
    expect(crownIcon).toBeInTheDocument()
  })

  it('deve exibir detalhes do plano quando disponíveis', () => {
    const onMigrate = jest.fn()

    render(<PlanCard plan={mockPlan} isCurrentPlan={false} onMigrate={onMigrate} />)

    expect(screen.getByText('Suporte por email')).toBeInTheDocument()
    expect(screen.getByText('Cardápio digital')).toBeInTheDocument()
  })

  it('deve exibir módulos e ações do plano', () => {
    const onMigrate = jest.fn()

    render(<PlanCard plan={mockPlan} isCurrentPlan={false} onMigrate={onMigrate} />)

    expect(screen.getByText(/Módulo Marketing/i)).toBeInTheDocument()
    expect(screen.getByText('Cupons e campanhas')).toBeInTheDocument()
    expect(screen.getByText('E-mail de confirmação de pedido')).toBeInTheDocument()
    expect(screen.getByText('Acesso a relatórios')).toBeInTheDocument()
    expect(screen.getByText(/Até 5 usuários/i)).toBeInTheDocument()
  })

  it('deve desabilitar botão durante migração', () => {
    const onMigrate = jest.fn()

    render(
      <PlanCard 
        plan={mockPlan} 
        isCurrentPlan={false} 
        onMigrate={onMigrate} 
        isMigrating={true}
      />
    )

    const button = screen.getByText('Migrando...')
    expect(button.closest('button')).toBeDisabled()
  })
})

