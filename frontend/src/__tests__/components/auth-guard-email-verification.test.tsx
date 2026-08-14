import { render, screen, waitFor } from '@testing-library/react'
import { AuthGuard } from '@/components/auth-guard'

const replace = jest.fn()

jest.mock('next/navigation', () => ({
  useRouter: () => ({ replace, push: jest.fn() }),
  usePathname: () => '/dashboard',
}))

jest.mock('@/hooks/use-trial-guard', () => ({
  useTrialGuard: jest.fn(),
}))

const useAuthMock = jest.fn()

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => useAuthMock(),
}))

describe('AuthGuard — verificação de e-mail', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('redireciona para /auth/verify-email quando email_verified é false', async () => {
    useAuthMock.mockReturnValue({
      isAuthenticated: true,
      isLoading: false,
      user: { id: '1', name: 'Admin', email: 'a@b.com', email_verified: false },
    })

    render(
      <AuthGuard>
        <div>Painel</div>
      </AuthGuard>,
    )

    await waitFor(() => {
      expect(replace).toHaveBeenCalledWith('/auth/verify-email')
    })
    expect(screen.queryByText('Painel')).not.toBeInTheDocument()
  })

  it('renderiza children quando o e-mail está verificado', () => {
    useAuthMock.mockReturnValue({
      isAuthenticated: true,
      isLoading: false,
      user: { id: '1', name: 'Admin', email: 'a@b.com', email_verified: true },
    })

    render(
      <AuthGuard>
        <div>Painel</div>
      </AuthGuard>,
    )

    expect(screen.getByText('Painel')).toBeInTheDocument()
    expect(replace).not.toHaveBeenCalledWith('/auth/verify-email')
  })
})
