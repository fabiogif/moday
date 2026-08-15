import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { LoginForm3 } from '@/app/(auth)/login/components/login-form-3'

jest.mock('next/navigation', () => ({
  useRouter: () => ({ push: jest.fn(), replace: jest.fn() }),
  useSearchParams: () => new URLSearchParams(),
}))

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({ login: jest.fn() }),
}))

jest.mock('@/lib/auth-storage', () => ({
  getRememberedEmail: () => '',
  setRememberedEmail: jest.fn(),
}))

jest.mock('sonner', () => ({
  toast: { success: jest.fn(), error: jest.fn() },
}))

describe('LoginForm3 — mostrar/ocultar senha', () => {
  it('renderiza o botão de ícone para exibir a senha', () => {
    render(<LoginForm3 />)

    const toggle = screen.getByRole('button', { name: /mostrar senha/i })
    const field = screen.getByPlaceholderText('Digite sua senha')

    expect(toggle).toBeInTheDocument()
    expect(field).toHaveAttribute('type', 'password')
  })

  it('alterna o campo entre password e text ao clicar no ícone', async () => {
    const user = userEvent.setup()
    render(<LoginForm3 />)

    const field = screen.getByPlaceholderText('Digite sua senha')
    await user.click(screen.getByRole('button', { name: /mostrar senha/i }))

    expect(field).toHaveAttribute('type', 'text')
    expect(screen.getByRole('button', { name: /ocultar senha/i })).toBeInTheDocument()
  })
})

describe('LoginForm3 — link "Esqueceu a senha?"', () => {
  it('aponta para /auth/forgot-password sem query quando o e-mail ainda não foi digitado', () => {
    render(<LoginForm3 />)

    expect(screen.getByRole('link', { name: /esqueceu a senha/i })).toHaveAttribute(
      'href',
      '/auth/forgot-password',
    )
  })

  it('inclui o e-mail digitado como query string, para pré-preencher o forgot-password', async () => {
    const user = userEvent.setup()
    render(<LoginForm3 />)

    await user.type(screen.getByLabelText('E-mail'), 'usuario@example.com')

    expect(screen.getByRole('link', { name: /esqueceu a senha/i })).toHaveAttribute(
      'href',
      '/auth/forgot-password?email=usuario%40example.com',
    )
  })
})
