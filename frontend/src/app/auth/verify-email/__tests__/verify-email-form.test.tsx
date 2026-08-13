import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { VerifyEmailForm } from '../components/verify-email-form'
import {
  EmailVerificationError,
  resendVerificationEmail,
  verifyEmailCode,
} from '@/lib/auth-email-verification'

const push = jest.fn()
const replace = jest.fn()
const setUser = jest.fn()

jest.mock('@/lib/auth-email-verification', () => ({
  ...jest.requireActual('@/lib/auth-email-verification'),
  verifyEmailCode: jest.fn(),
  resendVerificationEmail: jest.fn(),
}))

jest.mock('sonner', () => ({
  toast: { success: jest.fn(), error: jest.fn() },
}))

jest.mock('next/navigation', () => ({
  useRouter: () => ({ push, replace }),
  useSearchParams: () => new URLSearchParams(),
}))

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    user: {
      id: '1',
      name: 'Admin',
      email: 'joao@empresa.com',
      email_verified: false,
    },
    setUser,
    isAuthenticated: true,
    isLoading: false,
  }),
}))

jest.mock('@/lib/auth-storage', () => ({
  persistAuthUser: jest.fn(),
}))

describe('VerifyEmailForm', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renderiza destino mascarado e input de código', () => {
    render(<VerifyEmailForm />)

    expect(screen.getByText('Confirme seu e-mail')).toBeInTheDocument()
    expect(screen.getByText(/código enviado para/i)).toBeInTheDocument()
    expect(screen.getByText('j***@empresa.com')).toBeInTheDocument()
    expect(screen.getByLabelText(/código de 6 dígitos/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /confirmar e-mail/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /reenviar código/i })).toBeInTheDocument()
  })

  it('mostra erro quando o código é inválido', async () => {
    const user = userEvent.setup()
    ;(verifyEmailCode as jest.Mock).mockRejectedValue(
      new EmailVerificationError('Código incorreto. Verifique o e-mail e tente novamente.', 'invalid_code'),
    )

    render(<VerifyEmailForm />)

    await user.type(screen.getByLabelText(/código de 6 dígitos/i), '123456')
    await user.click(screen.getByRole('button', { name: /confirmar e-mail/i }))

    await waitFor(() => {
      expect(verifyEmailCode).toHaveBeenCalledWith('123456')
      expect(screen.getByRole('alert')).toHaveTextContent(/código incorreto/i)
    })
  })

  it('confirma o e-mail e redireciona ao dashboard', async () => {
    const user = userEvent.setup()
    ;(verifyEmailCode as jest.Mock).mockResolvedValue('E-mail confirmado com sucesso.')

    render(<VerifyEmailForm />)

    await user.type(screen.getByLabelText(/código de 6 dígitos/i), '654321')
    await user.click(screen.getByRole('button', { name: /confirmar e-mail/i }))

    await waitFor(() => {
      expect(verifyEmailCode).toHaveBeenCalledWith('654321')
      expect(screen.getByText(/e-mail confirmado/i)).toBeInTheDocument()
      expect(setUser).toHaveBeenCalled()
    })
  })

  it('permite reenviar o código', async () => {
    const user = userEvent.setup()
    ;(resendVerificationEmail as jest.Mock).mockResolvedValue({
      message: 'Novo código enviado para o seu e-mail.',
    })

    render(<VerifyEmailForm />)

    await user.click(screen.getByRole('button', { name: /reenviar código/i }))

    await waitFor(() => {
      expect(resendVerificationEmail).toHaveBeenCalled()
    })
  })

  it('mostra erro quando o código expirou', async () => {
    const user = userEvent.setup()
    ;(verifyEmailCode as jest.Mock).mockRejectedValue(
      new EmailVerificationError('Código expirado. Solicite um novo código.', 'expired_code'),
    )

    render(<VerifyEmailForm />)

    await user.type(screen.getByLabelText(/código de 6 dígitos/i), '123456')
    await user.click(screen.getByRole('button', { name: /confirmar e-mail/i }))

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent(/código expirado/i)
    })
  })

  it('valida código com menos de 6 dígitos', async () => {
    const user = userEvent.setup()

    render(<VerifyEmailForm />)

    await user.type(screen.getByLabelText(/código de 6 dígitos/i), '123')
    await user.click(screen.getByRole('button', { name: /confirmar e-mail/i }))

    await waitFor(() => {
      expect(screen.getByText(/o código deve ter 6 dígitos/i)).toBeInTheDocument()
    })
    expect(verifyEmailCode).not.toHaveBeenCalled()
  })
})
