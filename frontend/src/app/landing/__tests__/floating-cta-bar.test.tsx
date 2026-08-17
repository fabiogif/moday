import { render, screen, act } from '@testing-library/react'
import { FloatingCTABar } from '../components/floating-cta-bar'

jest.mock('next/link', () => {
  return ({ children, href }: { children: React.ReactNode; href: string }) => {
    return <a href={href}>{children}</a>
  }
})

describe('FloatingCTABar', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'scrollY', {
      writable: true,
      configurable: true,
      value: 0,
    })
  })

  it('não deve renderizar antes do scroll threshold', () => {
    render(<FloatingCTABar />)
    expect(screen.queryByRole('region', { name: /ação rápida de cadastro/i })).not.toBeInTheDocument()
  })

  it('deve renderizar após scroll acima do threshold', () => {
    render(<FloatingCTABar />)

    act(() => {
      Object.defineProperty(window, 'scrollY', { value: 400, configurable: true })
      window.dispatchEvent(new Event('scroll'))
    })

    expect(screen.getByRole('region', { name: /ação rápida de cadastro/i })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Teste grátis por 7 dias/i })).toHaveAttribute(
      'href',
      '/auth/register'
    )
    expect(screen.getByRole('link', { name: /Ver planos/i })).toHaveAttribute('href', '#pricing')
  })
})
