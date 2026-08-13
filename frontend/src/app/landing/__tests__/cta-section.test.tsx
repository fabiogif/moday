import { render, screen } from '@testing-library/react'
import { CTASection } from '../components/cta-section'

jest.mock('next/link', () => {
  return ({ children, href }: { children: React.ReactNode; href: string }) => {
    return <a href={href}>{children}</a>
  }
})

describe('CTASection', () => {
  it('deve renderizar o título principal', () => {
    render(<CTASection />)
    expect(screen.getByText(/Revolucione a gestão do seu/i)).toBeInTheDocument()
  })

  it('deve renderizar informação do teste de 7 dias', () => {
    render(<CTASection />)
    expect(screen.getByText(/Teste os planos Básico e Premium por 7 dias grátis/i)).toBeInTheDocument()
    expect(screen.getByText(/Teste grátis por 7 dias nos planos pagos/i)).toBeInTheDocument()
  })

  it('deve renderizar botões de CTA com copy unificado', () => {
    render(<CTASection />)
    expect(screen.getByRole('link', { name: /Teste grátis por 7 dias/i })).toHaveAttribute('href', '/auth/register')
    expect(screen.getByRole('link', { name: /Ver Planos e Preços/i })).toHaveAttribute('href', '#pricing')
  })

  it('deve renderizar trust indicators', () => {
    render(<CTASection />)
    expect(screen.getByText(/Sem cartão de crédito/i)).toBeInTheDocument()
    expect(screen.getByText(/Suporte especializado/i)).toBeInTheDocument()
  })
})
