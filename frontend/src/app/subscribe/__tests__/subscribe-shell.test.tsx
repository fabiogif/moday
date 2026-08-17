/**
 * @jest-environment jsdom
 */
import { render, screen } from '@testing-library/react'
import { SubscribeShell } from '@/app/subscribe/components/subscribe-shell'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({ isAuthenticated: true }),
}))

jest.mock('@/hooks/use-theme', () => ({
  useTheme: () => ({ setTheme: jest.fn() }),
}))

jest.mock('@/components/albatec-logo', () => ({
  AlbaTecLogo: ({ href }: { href?: string }) => <a href={href || '/'}>Alba Tec</a>,
  LANDING_FOOTER_BRAND_ICON_SIZE: 56,
}))

describe('SubscribeShell', () => {
  it('exibe marca, navegação e rodapé com links da landing', () => {
    render(
      <SubscribeShell>
        <div>conteúdo</div>
      </SubscribeShell>
    )

    expect(screen.getAllByText('Alba Tec').length).toBeGreaterThan(0)
    expect(screen.getByText('conteúdo')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Voltar aos planos/i })).toHaveAttribute(
      'href',
      '/landing#pricing'
    )
    expect(screen.getByRole('link', { name: /Painel/i })).toHaveAttribute('href', '/dashboard')
    expect(screen.getByRole('link', { name: /Recursos/i })).toHaveAttribute(
      'href',
      '/landing#features'
    )
    expect(screen.getByRole('link', { name: /Contato/i })).toHaveAttribute(
      'href',
      '/landing#contact'
    )
  })
})
