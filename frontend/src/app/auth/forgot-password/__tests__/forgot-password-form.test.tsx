import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ForgotPasswordForm } from '../components/forgot-password-form'
import { requestPasswordReset } from '@/lib/auth-password'

jest.mock('@/lib/auth-password')
jest.mock('sonner', () => ({
  toast: { success: jest.fn(), error: jest.fn() },
}))

describe('ForgotPasswordForm', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renderiza o formulário de recuperação', () => {
    render(<ForgotPasswordForm />)

    expect(screen.getByText('Esqueceu sua senha?')).toBeInTheDocument()
    expect(screen.getByLabelText(/e-mail/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /enviar link/i })).toBeInTheDocument()
  })

  it('envia o e-mail e exibe confirmação', async () => {
    const user = userEvent.setup()
    ;(requestPasswordReset as jest.Mock).mockResolvedValue('Link de recuperação enviado')

    render(<ForgotPasswordForm />)

    await user.type(screen.getByLabelText(/e-mail/i), 'user@example.com')
    await user.click(screen.getByRole('button', { name: /enviar link/i }))

    await waitFor(() => {
      expect(requestPasswordReset).toHaveBeenCalledWith('user@example.com')
      expect(screen.getByText('user@example.com')).toBeInTheDocument()
      expect(screen.getByRole('link', { name: /voltar para o login/i })).toHaveAttribute(
        'href',
        '/auth/login',
      )
    })
  })
})
