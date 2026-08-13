import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import AdminForgotPasswordPage from '../page'
import { requestPasswordReset } from '@/lib/auth-password'

jest.mock('@/lib/auth-password')
jest.mock('sonner', () => ({
  toast: { success: jest.fn(), error: jest.fn() },
}))

describe('AdminForgotPasswordPage', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renderiza formulário admin de recuperação', () => {
    render(<AdminForgotPasswordPage />)

    expect(screen.getByText('Recuperar senha')).toBeInTheDocument()
    expect(screen.getByLabelText(/e-mail/i)).toBeInTheDocument()
  })

  it('usa endpoint admin ao enviar', async () => {
    const user = userEvent.setup()
    ;(requestPasswordReset as jest.Mock).mockResolvedValue('Link enviado')

    render(<AdminForgotPasswordPage />)

    await user.type(screen.getByLabelText(/e-mail/i), 'admin@albtec.app')
    await user.click(screen.getByRole('button', { name: /enviar link/i }))

    await waitFor(() => {
      expect(requestPasswordReset).toHaveBeenCalledWith('admin@albtec.app', 'admin')
    })
  })
})
