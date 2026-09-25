import { render, screen } from '@testing-library/react'
import StoreLayout from '../layout'
import ClientLoginPage from '../login/page'
import ClientRegisterPage from '../register/page'

jest.mock('next/navigation', () => ({
  useParams: () => ({ slug: 'test-store' }),
  useRouter: () => ({ push: jest.fn(), replace: jest.fn() }),
}))

jest.mock('next/link', () => ({
  __esModule: true,
  default: ({ children, href }: { children: React.ReactNode; href: string }) => <a href={href}>{children}</a>,
}))

jest.mock('sonner', () => ({ toast: { success: jest.fn(), error: jest.fn() } }))

// Regressão: login/cadastro chamavam useClientAuth() sem ClientAuthProvider acima e quebravam ao abrir
describe('Páginas de conta do cliente na loja', () => {
  it('login renderiza o formulário dentro do layout da loja', () => {
    render(<StoreLayout><ClientLoginPage /></StoreLayout>)
    expect(screen.getAllByLabelText(/e-?mail/i).length).toBeGreaterThan(0)
  })

  it('cadastro renderiza o formulário dentro do layout da loja', () => {
    render(<StoreLayout><ClientRegisterPage /></StoreLayout>)
    expect(screen.getAllByLabelText(/e-?mail/i).length).toBeGreaterThan(0)
  })
})
