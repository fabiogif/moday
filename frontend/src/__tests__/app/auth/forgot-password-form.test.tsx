import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ForgotPasswordForm } from '@/app/auth/forgot-password/components/forgot-password-form'

let mockSearchParams = new URLSearchParams()

jest.mock('next/navigation', () => ({
  useSearchParams: () => mockSearchParams,
}))

jest.mock('sonner', () => ({
  toast: { success: jest.fn(), error: jest.fn() },
}))

global.fetch = jest.fn()

describe('ForgotPasswordForm', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockSearchParams = new URLSearchParams()
  })

  it('pré-preenche o e-mail vindo da query string (?email=)', () => {
    mockSearchParams = new URLSearchParams({ email: 'usuario@example.com' })

    render(<ForgotPasswordForm />)

    expect(screen.getByLabelText('E-mail')).toHaveValue('usuario@example.com')
  })

  it('deixa o campo de e-mail vazio quando não há query string', () => {
    render(<ForgotPasswordForm />)

    expect(screen.getByLabelText('E-mail')).toHaveValue('')
  })

  it('mostra confirmação de envio após submeter', async () => {
    const user = userEvent.setup()
    mockSearchParams = new URLSearchParams({ email: 'usuario@example.com' })

    ;(global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      headers: { get: () => 'application/json' },
      json: async () => ({
        success: true,
        message: 'Se este e-mail estiver cadastrado, enviamos um link de recuperação.',
      }),
    })

    render(<ForgotPasswordForm />)

    await user.click(screen.getByRole('button', { name: /enviar link de redefinição/i }))

    await waitFor(() => {
      expect(screen.getByText('E-mail enviado para')).toBeInTheDocument()
    })
    expect(screen.getByText('usuario@example.com')).toBeInTheDocument()
  })
})
