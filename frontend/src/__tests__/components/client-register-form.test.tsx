import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import { ClientRegisterForm } from '@/components/client-register-form'
import { ClientAuthProvider } from '@/contexts/client-auth-context'

jest.mock('sonner', () => ({ toast: { success: jest.fn(), error: jest.fn() } }))

global.fetch = jest.fn() as jest.Mock

function renderForm(onSuccess = jest.fn()) {
  render(
    <ClientAuthProvider>
      <ClientRegisterForm slug="test-store" onSuccess={onSuccess} />
    </ClientAuthProvider>
  )
  fireEvent.change(screen.getByLabelText(/Nome Completo/i), { target: { value: 'Maria Nova' } })
  fireEvent.change(screen.getByLabelText(/^Email/i), { target: { value: 'maria@teste.com' } })
  fireEvent.change(screen.getByLabelText(/Telefone/i), { target: { value: '71988887777' } })
  fireEvent.change(screen.getByLabelText(/^Senha/i), { target: { value: 'segredo123' } })
  fireEvent.change(screen.getByLabelText(/Confirmar Senha/i), { target: { value: 'segredo123' } })
  return onSuccess
}

function mockRegisterResponse(status: number, body: unknown) {
  ;(global.fetch as jest.Mock).mockResolvedValue({
    ok: status < 400,
    status,
    json: () => Promise.resolve(body),
  })
}

describe('ClientRegisterForm', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.clear()
  })

  it('cadastra, entrega o cliente ao onSuccess e deixa a sessão salva', async () => {
    const client = { uuid: 'c1', name: 'Maria Nova', email: 'maria@teste.com', phone: '71988887777' }
    mockRegisterResponse(201, { success: true, data: { client, token: 'jwt-token' } })
    const onSuccess = renderForm()

    fireEvent.click(screen.getByRole('button', { name: /Criar Conta/i }))

    await waitFor(() => expect(onSuccess).toHaveBeenCalledWith(client))
    expect(global.fetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/store/test-store/auth/register'),
      expect.objectContaining({ method: 'POST' })
    )
    expect(localStorage.getItem('client-auth-token')).toBe('jwt-token')
  })

  it('e-mail já cadastrado mostra o motivo do 422, não o genérico "Dados inválidos"', async () => {
    mockRegisterResponse(422, {
      success: false,
      message: 'Dados inválidos',
      errors: { email: ['Email já cadastrado nesta loja'] },
    })
    const onSuccess = renderForm()

    fireEvent.click(screen.getByRole('button', { name: /Criar Conta/i }))

    expect(await screen.findByText('Email já cadastrado nesta loja')).toBeInTheDocument()
    expect(onSuccess).not.toHaveBeenCalled()
    expect(screen.getByLabelText(/^Email/i)).toHaveValue('maria@teste.com')
  })

  it('sem conexão mostra mensagem amigável', async () => {
    ;(global.fetch as jest.Mock).mockRejectedValue(new TypeError('Failed to fetch'))
    renderForm()

    fireEvent.click(screen.getByRole('button', { name: /Criar Conta/i }))

    expect(await screen.findByText(/Não foi possível conectar/i)).toBeInTheDocument()
  })

  it('senhas diferentes não chamam a API', async () => {
    renderForm()
    fireEvent.change(screen.getByLabelText(/Confirmar Senha/i), { target: { value: 'outra-senha' } })

    fireEvent.click(screen.getByRole('button', { name: /Criar Conta/i }))

    expect(await screen.findByText('As senhas não coincidem')).toBeInTheDocument()
    expect(global.fetch).not.toHaveBeenCalled()
  })
})
