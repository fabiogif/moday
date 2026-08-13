import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import AdminPerfilPage from '../page'

const mockUpdateProfile = jest.fn()
const mockUpdatePassword = jest.fn()
const mockUpdateAdmin = jest.fn()
const mockLogout = jest.fn()

const mockAdmin = {
  id: 1,
  name: 'Admin Teste',
  email: 'admin@test.com',
  role: 'super_admin' as const,
  is_active: true,
  last_login_at: null,
  permissions: {},
}

jest.mock('@/contexts/admin-auth-context', () => ({
  useAdminAuth: () => ({
    admin: mockAdmin,
    updateAdmin: mockUpdateAdmin,
    logout: mockLogout,
  }),
}))

jest.mock('@/lib/admin-api-client', () => ({
  __esModule: true,
  default: {
    updateProfile: (...args: unknown[]) => mockUpdateProfile(...args),
    updatePassword: (...args: unknown[]) => mockUpdatePassword(...args),
  },
}))

jest.mock('@/hooks/use-toast', () => ({
  useToast: () => ({ toast: jest.fn() }),
}))

describe('AdminPerfilPage', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('deve renderizar blocos Dados Pessoais e Alterar Senha', () => {
    render(<AdminPerfilPage />)

    expect(screen.getByText('Dados Pessoais')).toBeInTheDocument()
    expect(screen.getByText('Alterar Senha')).toBeInTheDocument()
    expect(screen.getByLabelText('Nome')).toHaveValue('Admin Teste')
    expect(screen.getByLabelText('Email')).toHaveValue('admin@test.com')
  })

  it('deve salvar dados pessoais com sucesso', async () => {
    const user = userEvent.setup()
    mockUpdateProfile.mockResolvedValue({
      success: true,
      data: { id: 1, name: 'Novo Nome', email: 'novo@test.com', role: 'super_admin' },
    })

    render(<AdminPerfilPage />)

    await user.clear(screen.getByLabelText('Nome'))
    await user.type(screen.getByLabelText('Nome'), 'Novo Nome')
    await user.click(screen.getByRole('button', { name: /Salvar alterações/i }))

    await waitFor(() => {
      expect(mockUpdateProfile).toHaveBeenCalledWith({
        name: 'Novo Nome',
        email: 'admin@test.com',
      })
      expect(mockUpdateAdmin).toHaveBeenCalled()
    })
  })

  it('deve alterar senha com sucesso', async () => {
    jest.useFakeTimers()
    const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime })
    mockUpdatePassword.mockResolvedValue({ success: true })

    render(<AdminPerfilPage />)

    await user.type(screen.getByLabelText('Senha atual'), 'Password123!')
    await user.type(screen.getByLabelText('Nova senha'), 'NewPassword456!')
    await user.type(screen.getByLabelText('Confirmar nova senha'), 'NewPassword456!')
    await user.click(screen.getByRole('button', { name: /Alterar senha/i }))

    await waitFor(() => {
      expect(mockUpdatePassword).toHaveBeenCalledWith({
        current_password: 'Password123!',
        password: 'NewPassword456!',
        password_confirmation: 'NewPassword456!',
      })
    })

    jest.advanceTimersByTime(2000)
    expect(mockLogout).toHaveBeenCalled()
    jest.useRealTimers()
  })

  it('deve exibir erros de validação da senha', async () => {
    const user = userEvent.setup()
    mockUpdatePassword.mockRejectedValue({
      message: 'A senha atual está incorreta.',
      data: { errors: { current_password: ['A senha atual está incorreta.'] } },
    })

    render(<AdminPerfilPage />)

    await user.type(screen.getByLabelText('Senha atual'), 'errada')
    await user.type(screen.getByLabelText('Nova senha'), 'NewPassword456!')
    await user.type(screen.getByLabelText('Confirmar nova senha'), 'NewPassword456!')
    await user.click(screen.getByRole('button', { name: /Alterar senha/i }))

    expect(await screen.findByText('A senha atual está incorreta.')).toBeInTheDocument()
  })
})
