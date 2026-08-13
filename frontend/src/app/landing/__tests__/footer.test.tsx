import { render, screen } from '@testing-library/react'
import { LandingFooter } from '../components/footer'

describe('LandingFooter', () => {
  it('deve renderizar o nome da empresa', () => {
    render(<LandingFooter />)
    const companyNames = screen.getAllByText(/Alba Tec/i)
    expect(companyNames.length).toBeGreaterThan(0)
  })

  it('deve renderizar as colunas principais', () => {
    render(<LandingFooter />)
    expect(screen.getByText(/Produto/i)).toBeInTheDocument()
    expect(screen.getByText(/Empresa/i)).toBeInTheDocument()
  })

  it('deve renderizar links de navegação', () => {
    render(<LandingFooter />)
    expect(screen.getByText(/Recursos/i)).toBeInTheDocument()
    expect(screen.getByText(/Planos/i)).toBeInTheDocument()
    expect(screen.getByText(/FAQ/i)).toBeInTheDocument()
    expect(screen.getByText(/Contato/i)).toBeInTheDocument()
  })

  it('deve renderizar formulário de newsletter', () => {
    render(<LandingFooter />)
    expect(screen.getByText(/Fique por dentro/i)).toBeInTheDocument()
    expect(screen.getByPlaceholderText(/Digite seu e-mail/i)).toBeInTheDocument()
    expect(screen.getByText(/Inscrever/i)).toBeInTheDocument()
  })

  it('deve renderizar copyright', () => {
    render(<LandingFooter />)
    expect(screen.getByText(/Todos os direitos reservados/i)).toBeInTheDocument()
  })
})
