import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ResetPasswordForm } from '../components/reset-password-form'
import { resetPassword } from '@/lib/auth-password'

const mockPush = jest.fn()

jest.mock('next/navigation', () => ({
  useRouter: () => ({ push: mockPush }),
}))

jest.mock('@/lib/auth-password')
jest.mock('sonner', () => ({
  toast: { success: jest.fn(), error: jest.fn() },
}))

describe('ResetPasswordForm', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('exibe erro de link inválido sem token/email', () => {
    render(<ResetPasswordForm token="" email="" />)

    expect(screen.getByText(/link é inválido ou expirou/i)).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /solicitar novo link/i })).toHaveAttribute(
      'href',
      '/auth/forgot-password',
    )
  })

  it('envia nova senha com token válido', async () => {
    const user = userEvent.setup()
    ;(resetPassword as jest.Mock).mockResolvedValue('Senha resetada com sucesso')

    render(<ResetPasswordForm token="valid-token" email="user@example.com" />)

    await user.type(screen.getByLabelText(/^nova senha$/i), 'newpass12')
    await user.type(screen.getByLabelText(/confirmar nova senha/i), 'newpass12')
    await user.click(screen.getByRole('button', { name: /redefinir senha/i }))

    await waitFor(() => {
      expect(resetPassword).toHaveBeenCalledWith({
        token: 'valid-token',
        email: 'user@example.com',
        password: 'newpass12',
        password_confirmation: 'newpass12',
      })
      expect(mockPush).toHaveBeenCalledWith('/auth/login')
    })
  })
})
