import { render, screen } from '@testing-library/react'
import { FeaturesSection } from '../components/features-section'

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

jest.mock('@/hooks/use-landing-cta-click', () => ({
  useLandingCTAClick: () => jest.fn(),
}))

describe('FeaturesSection', () => {
  it('deve renderizar o título da seção', () => {
    render(<FeaturesSection />)
    expect(
      screen.getByText(/Métricas e gráficos que mostram o pulso do negócio/i)
    ).toBeInTheDocument()
  })

  it('deve renderizar cards principais de features', () => {
    render(<FeaturesSection />)
    const expectedFeatures = [
      /PDV Touch-First/i,
      /Avaliações Moderadas/i,
      /Controle de Mesas/i,
      /Cardápio com Variações/i,
      /Pedidos Omnichannel/i,
      /Controle de Estoque/i,
      /Relatórios em Tempo Real/i,
      /App Alba Tec Restaurante/i,
    ]

    expectedFeatures.forEach((pattern) => {
      expect(screen.getAllByText(pattern).length).toBeGreaterThanOrEqual(1)
    })
  })

  it('deve renderizar badges NOVO nos 3 primeiros cards', () => {
    render(<FeaturesSection />)
    const novoBadges = screen.getAllByText(/NOVO/i)
    expect(novoBadges.length).toBeGreaterThanOrEqual(3)
  })

  it('deve renderizar métricas de benefício', () => {
    render(<FeaturesSection />)
    expect(screen.getByText(/taxa de conversão em tempo real/i)).toBeInTheDocument()
    expect(screen.getByText(/Variações e opcionais em tempo real/i)).toBeInTheDocument()
  })
})
