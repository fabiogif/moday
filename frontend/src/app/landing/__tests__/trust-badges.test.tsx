import { render, screen } from '@testing-library/react'
import { TrustBadges } from '../components/trust-badges'

describe('TrustBadges', () => {
  it('deve renderizar todos os selos de credibilidade', () => {
    render(<TrustBadges />)

    expect(screen.getByText(/Sem cartão de crédito/i)).toBeInTheDocument()
    expect(screen.getByText(/Dados protegidos \(LGPD\)/i)).toBeInTheDocument()
    expect(screen.getByText(/Suporte em português/i)).toBeInTheDocument()
    expect(screen.getByText(/Conexão segura \(SSL\)/i)).toBeInTheDocument()
  })
})
