import { render, screen } from '@testing-library/react'
import { HeroSection } from '../components/hero-section'
import { TRIAL_CTA_LABEL, TRIAL_MICRO_COPY } from '@/lib/landing-copy'

jest.mock('next/link', () => {
  return ({ children, href }: { children: React.ReactNode; href: string }) => {
    return <a href={href}>{children}</a>
  }
})

jest.mock('next/image', () => {
  return ({ src, alt }: { src: string; alt: string }) => {
    return <img src={src} alt={alt} />
  }
})

jest.mock('next/navigation', () => ({
  useSearchParams: () => new URLSearchParams(),
}))

describe('HeroSection', () => {
  it('deve renderizar o título principal com proposta de valor', () => {
    render(<HeroSection />)
    expect(
      screen.getByText(/Venda mais e cometa menos erros no seu restaurante/i)
    ).toBeInTheDocument()
  })

  it('deve renderizar informação do teste de 7 dias', () => {
    render(<HeroSection />)
    expect(screen.getByRole('link', { name: new RegExp(TRIAL_CTA_LABEL, 'i') })).toBeInTheDocument()
    expect(screen.getByText(TRIAL_MICRO_COPY)).toBeInTheDocument()
  })

  it('deve renderizar micro-copy de cadastro', () => {
    render(<HeroSection />)
    expect(screen.getByText(/Cadastro em 2 min/i)).toBeInTheDocument()
  })

  it('deve ter link de cadastro com teste grátis', () => {
    render(<HeroSection />)
    const registerLink = screen.getByRole('link', { name: new RegExp(TRIAL_CTA_LABEL, 'i') })
    expect(registerLink).toHaveAttribute('href', '/auth/register')
  })

  it('deve exibir animação do produto com pedidos em tempo real', () => {
    render(<HeroSection />)
    expect(screen.getByText('Pedidos em tempo real')).toBeInTheDocument()
    expect(screen.getByText('Saldo do mês')).toBeInTheDocument()
    expect(screen.getByText('#2848 confirmado')).toBeInTheDocument()
  })
})
