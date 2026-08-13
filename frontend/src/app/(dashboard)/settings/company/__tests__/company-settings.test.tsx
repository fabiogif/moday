import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import CompanySettings from '../page'
import { apiClient } from '@/lib/api-client'

jest.mock('@/lib/api-client', () => {
  const actual = jest.requireActual('@/lib/api-client')
  return {
    ...actual,
    apiClient: {
      get: jest.fn(),
      post: jest.fn(),
      put: jest.fn(),
    },
  }
})

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({ user: { tenant: { uuid: 'tenant-uuid-1' } } }),
}))

jest.mock('../components/plans-section', () => ({
  PlansSection: () => <div data-testid="plans-section-stub" />,
}))

jest.mock('@/hooks/use-viacep', () => ({
  useViaCEP: () => ({ loading: false, searchCEP: jest.fn() }),
}))

jest.mock('@/hooks/use-receitaws', () => ({
  useReceitaWS: () => ({ loading: false, companyData: null, searchCNPJ: jest.fn() }),
}))

const TENANT = {
  id: 1,
  uuid: 'tenant-uuid-1',
  name: '',
  slug: 'empresa-teste',
  email: '',
  cnpj: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  zipcode: '',
  country: '',
  is_active: true,
  created_at: '2026-01-01',
  updated_at: '2026-01-01',
}

describe('CompanySettings - wizard de passos', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(apiClient.get as jest.Mock).mockImplementation((url: string) => {
      if (url === '/api/auth/me') {
        return Promise.resolve({ success: true, data: { tenant: { uuid: 'tenant-uuid-1' } } })
      }
      if (url === `/api/tenant/${TENANT.uuid}`) {
        return Promise.resolve({ success: true, data: TENANT })
      }
      return Promise.resolve({ success: false, data: null })
    })
    ;(apiClient.post as jest.Mock).mockResolvedValue({ success: true, data: { valid: true } })
    ;(apiClient.put as jest.Mock).mockResolvedValue({ success: true, data: TENANT })
  })

  const waitForLoaded = async () => {
    expect(await screen.findByText(/Logo da Empresa/i)).toBeInTheDocument()
  }

  test('renders only step 1 (Logo) fields after loading', async () => {
    render(<CompanySettings />)
    await waitForLoaded()

    expect(screen.queryByLabelText(/Nome da Empresa/i)).not.toBeInTheDocument()
    expect(screen.queryByLabelText(/^Endereço$/i)).not.toBeInTheDocument()
  })

  test('advances to step 2 after backend validates step 1', async () => {
    const user = userEvent.setup()
    render(<CompanySettings />)
    await waitForLoaded()

    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByLabelText(/Nome da Empresa/i)).toBeInTheDocument()
    expect(apiClient.post).toHaveBeenCalledWith(
      '/api/tenant/validate',
      expect.objectContaining({ step: 0, uuid: TENANT.uuid })
    )
  })

  test('keeps Continuar disabled on step 2 when name/email are empty', async () => {
    const user = userEvent.setup()
    render(<CompanySettings />)
    await waitForLoaded()

    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    await screen.findByLabelText(/Nome da Empresa/i)

    expect(screen.getByRole('button', { name: /Continuar/i })).toBeDisabled()
  })

  test('does not advance when backend rejects step 2 (duplicate email)', async () => {
    const user = userEvent.setup()
    ;(apiClient.post as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: { valid: true } }) // step 0
      .mockRejectedValueOnce({
        message: 'Dados inválidos',
        errors: { email: ['Já existe uma empresa cadastrada com este e-mail.'] },
        status: 422,
      })
    render(<CompanySettings />)
    await waitForLoaded()

    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    await user.type(await screen.findByLabelText(/Nome da Empresa/i), 'Empresa Nova')
    await user.type(screen.getByLabelText(/^Email$/i), 'ocupado@empresa.com')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(
      await screen.findByText(/Já existe uma empresa cadastrada com este e-mail/i)
    ).toBeInTheDocument()
    expect(screen.queryByLabelText(/^Endereço$/i)).not.toBeInTheDocument()
  })

  test('advances through all steps and saves on final submit', async () => {
    const user = userEvent.setup()
    render(<CompanySettings />)
    await waitForLoaded()

    // Passo 1: Logo (sem campos obrigatórios)
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 2: Dados da Empresa
    await user.type(await screen.findByLabelText(/Nome da Empresa/i), 'Empresa Nova')
    await user.type(screen.getByLabelText(/^Email$/i), 'nova@empresa.com')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 3: Endereço (opcional)
    expect(await screen.findByLabelText(/^Endereço$/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Salvar alterações/i }))

    await waitFor(() => {
      expect(apiClient.put).toHaveBeenCalledWith(
        `/api/tenant/${TENANT.uuid}`,
        expect.objectContaining({ name: 'Empresa Nova', email: 'nova@empresa.com' })
      )
    })
  })
})
