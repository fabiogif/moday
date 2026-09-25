import { render, screen, waitFor, within } from '@testing-library/react'
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

jest.mock('sonner', () => ({ toast: { success: jest.fn(), error: jest.fn(), info: jest.fn() } }))

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({ user: { tenant: { uuid: 'tenant-uuid-1' } } }),
}))

jest.mock('../components/plans-section', () => ({
  PlansSection: () => <div data-testid="plans-section-stub" />,
}))

jest.mock('@/hooks/use-viacep', () => {
  const reset = jest.fn()
  const searchCEP = jest.fn()
  return {
    useViaCEP: () => ({
      loading: false,
      searchCEP,
      found: false,
      notifyCepChange: () => false,
      reset,
    }),
  }
})

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

describe('CompanySettings - capa do cardápio', () => {
  const { toast } = jest.requireMock('sonner') as { toast: { error: jest.Mock } }
  const COVER_PATH = '/storage/logos/tenants/tenant-uuid-1/covers/capa.jpg'
  const LOGO_PATH = '/storage/logos/tenants/tenant-uuid-1/logos/logo.png'

  function mockTenant(extra: Record<string, unknown> = {}) {
    ;(apiClient.get as jest.Mock).mockImplementation((url: string) => {
      if (url === '/api/auth/me') {
        return Promise.resolve({ success: true, data: { tenant: { uuid: TENANT.uuid } } })
      }
      if (url === `/api/tenant/${TENANT.uuid}`) {
        return Promise.resolve({ success: true, data: { ...TENANT, ...extra } })
      }
      return Promise.resolve({ success: false, data: null })
    })
  }

  async function renderLoaded() {
    const user = userEvent.setup()
    render(<CompanySettings />)
    expect(await screen.findByText('Capa do cardápio')).toBeInTheDocument()
    return user
  }

  function coverInput() {
    const dropzone = screen.getByRole('button', { name: 'Área para enviar a capa do cardápio' })
    return dropzone.querySelector('input[type="file"]') as HTMLInputElement
  }

  async function saveThroughSteps(user: ReturnType<typeof userEvent.setup>) {
    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    await user.type(await screen.findByLabelText(/Nome da Empresa/i), 'Empresa Nova')
    await user.type(screen.getByLabelText(/^Email$/i), 'nova@empresa.com')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    await screen.findByLabelText(/^Endereço$/i)
    await user.click(screen.getByRole('button', { name: /Salvar alterações/i }))
  }

  function sentFormData(): FormData | undefined {
    const call = (apiClient.post as jest.Mock).mock.calls.find(([, body]) => body instanceof FormData)
    return call?.[1]
  }

  beforeEach(() => {
    jest.clearAllMocks()
    ;(apiClient.post as jest.Mock).mockResolvedValue({ success: true, data: { valid: true } })
    ;(apiClient.put as jest.Mock).mockResolvedValue({ success: true, data: TENANT })
  })

  test('escolher uma capa mostra a prévia e envia "cover" junto com o formulário', async () => {
    mockTenant()
    const user = await renderLoaded()
    const file = new File(['capa'], 'capa.png', { type: 'image/png' })

    await user.upload(coverInput(), file)

    expect(await screen.findByAltText('Prévia da capa do cardápio')).toBeInTheDocument()
    await saveThroughSteps(user)
    await waitFor(() => expect(sentFormData()).toBeDefined())
    const formData = sentFormData()!
    expect(formData.get('cover')).toBe(file)
    expect(formData.get('_method')).toBe('PUT')
    expect(formData.get('remove_cover')).toBeNull()
  })

  test('remover a capa envia "remove_cover" e não mexe no logo', async () => {
    mockTenant({ cover: COVER_PATH, logo: LOGO_PATH })
    const user = await renderLoaded()

    await user.click(screen.getByRole('button', { name: /Remover capa/i }))
    expect(screen.getByText('A capa será removida ao salvar as alterações')).toBeInTheDocument()

    await saveThroughSteps(user)
    await waitFor(() => expect(sentFormData()).toBeDefined())
    const formData = sentFormData()!
    expect(formData.get('remove_cover')).toBe('1')
    expect(formData.get('cover')).toBeNull()
    expect(formData.get('remove_logo')).toBeNull()
    expect(formData.get('logo')).toBeNull()
  })

  test('capa maior que 5MB mostra erro e não é enviada', async () => {
    mockTenant()
    const user = await renderLoaded()
    const big = new File(['x'], 'grande.jpg', { type: 'image/jpeg' })
    Object.defineProperty(big, 'size', { value: 7 * 1024 * 1024 })

    await user.upload(coverInput(), big)

    expect(toast.error).toHaveBeenCalledWith(expect.stringMatching(/5MB/))
    expect(screen.queryByAltText('Prévia da capa do cardápio')).not.toBeInTheDocument()
    await saveThroughSteps(user)
    await waitFor(() => expect(apiClient.put).toHaveBeenCalled())
    expect(sentFormData()).toBeUndefined()
  })

  test('remover o logo continua funcionando e envia "remove_logo" aceito pelo backend', async () => {
    mockTenant({ logo: LOGO_PATH })
    const user = await renderLoaded()

    await user.click(screen.getByRole('button', { name: /Remover Logo/i }))
    await saveThroughSteps(user)

    await waitFor(() => expect(sentFormData()).toBeDefined())
    expect(sentFormData()!.get('remove_logo')).toBe('1')
    expect(sentFormData()!.get('remove_cover')).toBeNull()
  })

  test('card da capa fica no passo "Logo e capa"', async () => {
    mockTenant()
    await renderLoaded()
    const card = screen.getByText('Capa do cardápio').closest('[data-slot="card"]') as HTMLElement
    expect(within(card).getByText(/1600×640 px/)).toBeInTheDocument()
    expect(within(card).getByText('Sem capa')).toBeInTheDocument()
  })
})
