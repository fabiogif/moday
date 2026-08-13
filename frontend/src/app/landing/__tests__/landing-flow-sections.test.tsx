import { render, screen } from '@testing-library/react'
import {
  AppMobileFlowSection,
  FinanceFlowSection,
  OperationFlowSection,
} from '../components/landing-flow-sections'

jest.mock('next/link', () => {
  return ({ children, href }: { children: React.ReactNode; href: string }) => {
    return <a href={href}>{children}</a>
  }
})

jest.mock('@/hooks/use-landing-cta-click', () => ({
  useLandingCTAClick: () => jest.fn(),
}))

describe('LandingFlowSections', () => {
  it('deve renderizar seção Operação Flow com animação e features', () => {
    render(<OperationFlowSection />)

    expect(screen.getByText(/Operação Flow/i)).toBeInTheDocument()
    expect(screen.getByText(/Venda em todos os canais/i)).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Explorar Operação/i })).toHaveAttribute(
      'href',
      '/auth/register',
    )
    expect(screen.getByText(/Captura instantânea de clientes/i)).toBeInTheDocument()
    expect(screen.getByText(/Pedidos unificados/i)).toBeInTheDocument()
  })

  it('deve renderizar seção Financeiro Flow com grid de features', () => {
    render(<FinanceFlowSection />)

    expect(screen.getByText(/Financeiro Flow/i)).toBeInTheDocument()
    expect(screen.getByText(/Seja proativo com os números/i)).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Explorar Financeiro/i })).toHaveAttribute(
      'href',
      '/auth/register',
    )
    expect(screen.getByText(/Fluxo de caixa em tempo real/i)).toBeInTheDocument()
  })

  it('deve renderizar seção App Mobile com push e fluxo de status', () => {
    render(<AppMobileFlowSection />)

    expect(screen.getByText(/App Mobile/i)).toBeInTheDocument()
    expect(screen.getByText(/Aceite pedidos pelo celular/i)).toBeInTheDocument()
    expect(screen.getByText(/Push com som, mesmo com app fechado/i)).toBeInTheDocument()
    expect(screen.getByText(/Aceitar e avançar status/i)).toBeInTheDocument()
  })
})
