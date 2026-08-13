import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import AdminLoginPage from '@/app/admin/login/page'
import { AdminAuthProvider } from '@/contexts/admin-auth-context'

// Mock do next/navigation
const mockPush = jest.fn()
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: jest.fn(),
    pathname: '/admin/login',
  }),
}))

// Mock do fetch
global.fetch = jest.fn()

const renderLoginPage = () => {
  return render(
    <AdminAuthProvider>
      <AdminLoginPage />
    </AdminAuthProvider>
  )
}

describe('Admin Login Page', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.clear()
    ;(global.fetch as jest.Mock).mockClear()
  })

  it('should render login form', () => {
    renderLoginPage()

    expect(screen.getByText('Painel Administrativo')).toBeInTheDocument()
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/senha/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /entrar/i })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /esqueceu a senha/i })).toHaveAttribute(
      'href',
      '/admin/forgot-password',
    )
  })

  it('should validate required fields', async () => {
    const user = userEvent.setup()
    renderLoginPage()

    const submitButton = screen.getByRole('button', { name: /entrar/i })
    
    await user.click(submitButton)

    // Form HTML5 validation deve impedir submit
    const emailInput = screen.getByLabelText(/email/i) as HTMLInputElement
    const passwordInput = screen.getByLabelText(/senha/i) as HTMLInputElement
    
    expect(emailInput.validity.valid).toBe(false)
    expect(passwordInput.validity.valid).toBe(false)
  })

  it('should login successfully with valid credentials', async () => {
    const user = userEvent.setup()

    const mockResponse = {
      success: true,
      data: {
        admin: {
          id: 1,
          name: 'Admin Test',
          email: 'admin@albtec.app',
          role: 'super_admin',
        },
        token: 'test-token-123',
      },
    }

    ;(global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      headers: { get: () => 'application/json' },
      json: async () => mockResponse,
    })

    renderLoginPage()

    const emailInput = screen.getByLabelText(/email/i)
    const passwordInput = screen.getByLabelText(/senha/i)
    const submitButton = screen.getByRole('button', { name: /entrar/i })

    await user.type(emailInput, 'admin@albtec.app')
    await user.type(passwordInput, 'admin123')
    await user.click(submitButton)

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith('/admin/dashboard')
    })
  })

  it('should show error message on invalid credentials', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: false,
      headers: { get: () => 'application/json' },
      json: async () => ({
        success: false,
        message: 'Credenciais inválidas',
      }),
    })

    renderLoginPage()

    const emailInput = screen.getByLabelText(/email/i)
    const passwordInput = screen.getByLabelText(/senha/i)
    const submitButton = screen.getByRole('button', { name: /entrar/i })

    await user.type(emailInput, 'wrong@test.com')
    await user.type(passwordInput, 'wrongpass')
    await user.click(submitButton)

    await waitFor(() => {
      expect(screen.getByText('Credenciais inválidas')).toBeInTheDocument()
    })
  })

  it('should show loading state during login', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock).mockImplementation(
      () =>
        new Promise((resolve) =>
          setTimeout(
            () =>
              resolve({
                ok: true,
                json: async () => ({ success: true, data: {} }),
              }),
            100
          )
        )
    )

    renderLoginPage()

    const emailInput = screen.getByLabelText(/email/i)
    const passwordInput = screen.getByLabelText(/senha/i)
    const submitButton = screen.getByRole('button', { name: /entrar/i })

    await user.type(emailInput, 'admin@albtec.app')
    await user.type(passwordInput, 'admin123')
    await user.click(submitButton)

    expect(screen.getByText(/entrando/i)).toBeInTheDocument()
    expect(submitButton).toBeDisabled()
  })

  it('should disable form fields during loading', async () => {
    const user = userEvent.setup()

    ;(global.fetch as jest.Mock).mockImplementation(
      () => new Promise(() => {}) // Never resolves
    )

    renderLoginPage()

    const emailInput = screen.getByLabelText(/email/i)
    const passwordInput = screen.getByLabelText(/senha/i)
    const submitButton = screen.getByRole('button', { name: /entrar/i })

    await user.type(emailInput, 'admin@albtec.app')
    await user.type(passwordInput, 'admin123')
    await user.click(submitButton)

    await waitFor(() => {
      expect(emailInput).toBeDisabled()
      expect(passwordInput).toBeDisabled()
      expect(submitButton).toBeDisabled()
    })
  })
})

