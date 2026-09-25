import { render, screen, waitFor, act } from '@testing-library/react'
import '@testing-library/jest-dom'
import { ClientAuthProvider, useClientAuth } from '@/contexts/client-auth-context'

let mockSlug = 'loja-a'
jest.mock('next/navigation', () => ({
  useParams: () => ({ slug: mockSlug }),
}))

global.fetch = jest.fn() as jest.Mock

let auth: ReturnType<typeof useClientAuth>
function Probe() {
  auth = useClientAuth()
  return <p>{auth.isLoading ? 'carregando' : auth.isAuthenticated ? `logado:${auth.client?.name}` : 'anonimo'}</p>
}

const renderStore = () => render(<ClientAuthProvider><Probe /></ClientAuthProvider>)

describe('ClientAuthProvider — sessão por loja', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.clear()
    mockSlug = 'loja-a'
  })

  it('cadastro guarda a sessão só para a loja e envia credentials', async () => {
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ data: { client: { uuid: 'c1', name: 'Ana', email: 'a@a.com', phone: '1' }, token: 'tok-a' } }),
    })
    renderStore()
    await screen.findByText('anonimo')

    await act(() => auth.register({ name: 'Ana', email: 'a@a.com', password: '123456', password_confirmation: '123456', phone: '1' }, 'loja-a'))

    expect(screen.getByText('logado:Ana')).toBeInTheDocument()
    expect(localStorage.getItem('client-auth-token:loja-a')).toBe('tok-a')
    expect(localStorage.getItem('client-auth-token')).toBeNull()
    expect(global.fetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/store/loja-a/auth/register'),
      expect.objectContaining({ credentials: 'include' })
    )
  })

  it('sessão da loja A não vale na loja B', async () => {
    localStorage.setItem('client-auth-user:loja-a', JSON.stringify({ uuid: 'c1', name: 'Ana' }))
    localStorage.setItem('client-auth-token:loja-a', 'tok-a')

    mockSlug = 'loja-b'
    renderStore()
    expect(await screen.findByText('anonimo')).toBeInTheDocument()

    mockSlug = 'loja-a'
    renderStore()
    expect(await screen.findByText('logado:Ana')).toBeInTheDocument()
  })

  it('descarta a sessão antiga sem loja', async () => {
    localStorage.setItem('client-auth-user', JSON.stringify({ uuid: 'c1', name: 'Ana' }))
    localStorage.setItem('client-auth-token', 'tok-antigo')

    renderStore()

    expect(await screen.findByText('anonimo')).toBeInTheDocument()
    await waitFor(() => expect(localStorage.getItem('client-auth-token')).toBeNull())
    expect(localStorage.getItem('client-auth-user')).toBeNull()
  })

  it('logout limpa só a sessão da loja atual', async () => {
    localStorage.setItem('client-auth-user:loja-a', JSON.stringify({ uuid: 'c1', name: 'Ana' }))
    localStorage.setItem('client-auth-token:loja-a', 'tok-a')
    localStorage.setItem('client-auth-user:loja-b', JSON.stringify({ uuid: 'c2', name: 'Bia' }))
    localStorage.setItem('client-auth-token:loja-b', 'tok-b')
    renderStore()
    await screen.findByText('logado:Ana')

    act(() => auth.logout())

    expect(screen.getByText('anonimo')).toBeInTheDocument()
    expect(localStorage.getItem('client-auth-token:loja-a')).toBeNull()
    expect(localStorage.getItem('client-auth-token:loja-b')).toBe('tok-b')
  })
})
