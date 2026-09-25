import { render, screen, fireEvent, waitFor, within } from '@testing-library/react'
import '@testing-library/jest-dom'
import PublicStorePage from '../page'

// Mock next/navigation
jest.mock('next/navigation', () => ({
  useParams: () => ({ slug: 'test-store' }),
  useRouter: () => ({
    push: jest.fn(),
    replace: jest.fn(),
  }),
}))

// Mock next/image
jest.mock('next/image', () => ({
  __esModule: true,
  default: (props: any) => {
    const { fill, priority, sizes, unoptimized, ...rest } = props
    return <img {...rest} />
  },
}))

jest.mock('next/link', () => ({
  __esModule: true,
  default: ({ children, href }: any) => <a href={href}>{children}</a>,
}))

jest.mock('../components/reviews-section', () => ({
  ReviewsSection: () => null,
}))

// Status de horário controlado pelo teste (o banner real consulta /is-open)
let mockStoreOpen = true
jest.mock('../components/store-hours-banner', () => {
  const { useEffect } = jest.requireActual('react') as typeof import('react')
  return {
    StoreHoursBanner: ({ onStatusChange }: { onStatusChange?: (open: boolean) => void }) => {
      useEffect(() => {
        onStatusChange?.(mockStoreOpen)
      }, [onStatusChange])
      return null
    },
  }
})

jest.mock('@/components/order-stepper', () => ({
  OrderStepper: () => <div data-testid="order-stepper" />,
}))

jest.mock('@/components/site-footer', () => ({
  SiteFooter: () => null,
}))

// Sessão do cliente final controlada pelo teste (o provider real fica no layout da loja)
let mockClientAuthenticated = false
const mockRegister = jest.fn()
jest.mock('@/contexts/client-auth-context', () => ({
  useClientAuth: () => ({ isAuthenticated: mockClientAuthenticated, register: mockRegister }),
}))

jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
    info: jest.fn(),
  },
}))

// Mock hooks
jest.mock('@/hooks/use-viacep', () => {
  const reset = jest.fn()
  const searchCEP = jest.fn()
  return {
    useViaCEP: () => ({
      searchCEP,
      loading: false,
      found: false,
      notifyCepChange: () => false,
      reset,
    }),
  }
})

// Mock de produtos para teste
const mockProducts = [
  {
    uuid: '1',
    name: 'Pizza Margherita',
    description: 'Pizza tradicional',
    price: 30.00,
    promotional_price: 25.00,
    image: '/pizza.jpg',
    qtd_stock: 10,
    brand: 'Casa',
    categories: [{ uuid: 'cat1', name: 'Pizzas' }],
  },
  {
    uuid: '2',
    name: 'Coca-Cola',
    description: 'Refrigerante',
    price: 5.00,
    promotional_price: null,
    image: '/coca.jpg',
    qtd_stock: 50,
    brand: 'Coca',
    categories: [{ uuid: 'cat2', name: 'Bebidas' }],
  },
  {
    uuid: '3',
    name: 'Hambúrguer',
    description: 'Hambúrguer artesanal',
    price: 20.00,
    promotional_price: 15.00,
    image: '/burger.jpg',
    qtd_stock: 8,
    brand: 'Casa',
    categories: [{ uuid: 'cat3', name: 'Lanches' }],
  },
  {
    uuid: '4',
    name: 'Pudim',
    description: 'Pudim caseiro',
    price: 10.00,
    promotional_price: 5.00,
    image: '/pudim.jpg',
    qtd_stock: 5,
    brand: 'Casa',
    categories: [{ uuid: 'cat4', name: 'Sobremesas' }],
  },
]

const mockStoreInfo = {
  name: 'Loja Teste',
  slug: 'test-store',
  email: 'test@test.com',
  phone: '1234567890',
  address: 'Rua Teste',
  city: 'São Paulo',
  state: 'SP',
  zipcode: '12345678',
  logo: '/logo.jpg',
  whatsapp: '1234567890',
}

function createJsonFetchResponse(data: unknown) {
  return Promise.resolve({
    ok: true,
    headers: {
      get: (key: string) => (key === 'content-type' ? 'application/json' : null),
    },
    json: () => Promise.resolve(data),
  })
}

function setupStoreFetchMock(products: Array<Record<string, unknown>> = mockProducts, storeInfo = mockStoreInfo) {
  ;(global.fetch as jest.Mock).mockImplementation((url: string) => {
    if (url.includes('/info')) {
      return createJsonFetchResponse({ success: true, data: storeInfo })
    }
    if (url.includes('/products')) {
      return createJsonFetchResponse({ success: true, data: products })
    }
    if (url.includes('/payment-methods')) {
      return createJsonFetchResponse({ success: true, data: [{ uuid: 'pm-1', name: 'Dinheiro' }] })
    }
    if (url.includes('/service-type/menu')) {
      return createJsonFetchResponse({ success: true, data: [] })
    }
    return createJsonFetchResponse({ success: true, data: [] })
  })
}

// Mock fetch
global.fetch = jest.fn() as any

describe('PublicStorePage - Seções do cardápio', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockStoreOpen = true
    setupStoreFetchMock()
  })

  it('lista todas as categorias como seções com seus produtos, sem filtrar', async () => {
    render(<PublicStorePage />)

    for (const [category, product] of [
      ['Bebidas', 'Coca-Cola'],
      ['Lanches', 'Hambúrguer'],
      ['Pizzas', 'Pizza Margherita'],
      ['Sobremesas', 'Pudim'],
    ]) {
      const heading = await screen.findByRole('heading', { level: 2, name: category })
      const section = heading.closest('section')!
      expect(within(section).getByRole('button', { name: `Ver detalhes de ${product}` })).toBeInTheDocument()
    }
    expect(screen.queryByRole('button', { name: 'Todos' })).not.toBeInTheDocument()
  })

  it('mostra as abas de categoria no header fixo', async () => {
    render(<PublicStorePage />)
    const nav = await screen.findByRole('navigation', { name: 'Categorias do cardápio' })
    expect(within(nav).getByRole('button', { name: 'Bebidas' })).toHaveAttribute('aria-current', 'true')
    expect(within(nav).getByRole('button', { name: 'Sobremesas' })).toBeInTheDocument()
  })

  it('produto sem categoria vai para a seção "Outros"', async () => {
    setupStoreFetchMock([{ ...mockProducts[1], categories: [] }])
    render(<PublicStorePage />)
    const heading = await screen.findByRole('heading', { level: 2, name: 'Outros' })
    expect(within(heading.closest('section')!).getByText('Coca-Cola')).toBeInTheDocument()
  })

  it('cardápio vazio mostra mensagem e nenhuma aba', async () => {
    setupStoreFetchMock([])
    render(<PublicStorePage />)
    expect(await screen.findByText('Nenhum produto encontrado no cardápio.')).toBeInTheDocument()
    expect(screen.queryByRole('navigation', { name: 'Categorias do cardápio' })).not.toBeInTheDocument()
  })

  it('não mostra o stepper do checkout no cardápio', async () => {
    render(<PublicStorePage />)
    await screen.findByRole('heading', { level: 2, name: 'Bebidas' })
    expect(screen.queryByTestId('order-stepper')).not.toBeInTheDocument()

    fireEvent.click(screen.getByRole('button', { name: 'Adicionar Coca-Cola ao carrinho' }))
    fireEvent.click(screen.getAllByRole('button', { name: /Continuar pedido/i })[0])
    fireEvent.click(await screen.findByRole('button', { name: 'Continuar sem cadastro' }))
    expect(await screen.findByTestId('order-stepper')).toBeInTheDocument()
  })
})

describe('PublicStorePage - Busca', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockStoreOpen = true
    setupStoreFetchMock()
  })

  it('busca troca as seções por uma lista única e limpar restaura as seções', async () => {
    render(<PublicStorePage />)
    const search = await screen.findByRole('searchbox', { name: 'Buscar em Loja Teste' })

    fireEvent.change(search, { target: { value: 'COCA' } })
    expect(screen.getByRole('button', { name: 'Ver detalhes de Coca-Cola' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Ver detalhes de Pudim' })).not.toBeInTheDocument()
    expect(screen.queryByRole('heading', { level: 2, name: 'Bebidas' })).not.toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Ofertas' })).not.toBeInTheDocument()

    fireEvent.click(screen.getByRole('button', { name: 'Limpar busca' }))
    expect(screen.getByRole('heading', { level: 2, name: 'Bebidas' })).toBeInTheDocument()
  })

  it('busca pela descrição e mostra mensagem quando nada combina', async () => {
    render(<PublicStorePage />)
    const search = await screen.findByRole('searchbox', { name: 'Buscar em Loja Teste' })

    fireEvent.change(search, { target: { value: 'artesanal' } })
    expect(screen.getByRole('button', { name: 'Ver detalhes de Hambúrguer' })).toBeInTheDocument()

    fireEvent.change(search, { target: { value: 'xyz' } })
    expect(screen.getByText('Nenhum produto encontrado para sua busca.')).toBeInTheDocument()
  })
})

describe('PublicStorePage - Vitrines', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockStoreOpen = true
    setupStoreFetchMock()
  })

  it('"Ofertas" ordena por maior desconto', async () => {
    render(<PublicStorePage />)
    const offers = (await screen.findByRole('heading', { name: 'Ofertas' })).closest('section')!
    const names = within(offers)
      .getAllByRole('button', { name: /^Ver detalhes de/ })
      .map((button) => button.getAttribute('aria-label'))
    expect(names).toEqual(['Ver detalhes de Pudim', 'Ver detalhes de Hambúrguer', 'Ver detalhes de Pizza Margherita'])
    expect(within(offers).getByText('-50%')).toBeInTheDocument()
  })

  it('"Preferidos" só aparece com vendas e mostra o ranking', async () => {
    setupStoreFetchMock(mockProducts.map((p, i) => ({ ...p, sold_qty: i === 1 ? 30 : i === 3 ? 12 : 0 })))
    render(<PublicStorePage />)
    const top = (await screen.findByRole('heading', { name: 'Preferidos' })).closest('section')!
    expect(within(top).getByRole('button', { name: /^1º mais pedido: Coca-Cola/ })).toBeInTheDocument()
    expect(within(top).getByRole('button', { name: /^2º mais pedido: Pudim/ })).toBeInTheDocument()
  })

  it('sem vendas não mostra "Preferidos"', async () => {
    render(<PublicStorePage />)
    await screen.findByRole('heading', { name: 'Ofertas' })
    expect(screen.queryByRole('heading', { name: 'Preferidos' })).not.toBeInTheDocument()
  })
})

describe('PublicStorePage - Adicionar pelo +', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockStoreOpen = true
  })

  it('produto com variações abre os detalhes em vez de adicionar', async () => {
    setupStoreFetchMock([{ ...mockProducts[1], variations: [{ id: 'v1', name: '2 litros', price: 4 }] }])
    render(<PublicStorePage />)
    fireEvent.click(await screen.findByRole('button', { name: 'Adicionar Coca-Cola ao carrinho' }))
    expect(await screen.findByRole('dialog')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Ver carrinho' })).not.toBeInTheDocument()
  })
})

describe('PublicStorePage - Topo da loja', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockStoreOpen = true
  })

  it('mostra nota, cupons e "Grátis acima de" quando os dados existem', async () => {
    setupStoreFetchMock(mockProducts, {
      ...mockStoreInfo,
      settings: { delivery_pickup: { delivery_enabled: true, delivery_free_above_value: 80 } },
    } as typeof mockStoreInfo)
    const base = (global.fetch as jest.Mock).getMockImplementation()!
    ;(global.fetch as jest.Mock).mockImplementation((url: string) => {
      if (url.includes('/reviews/stats')) {
        return createJsonFetchResponse({ success: true, data: { total: 22, average_rating: 4.8 } })
      }
      if (url.includes('/promotions')) {
        return createJsonFetchResponse({
          success: true,
          data: { slides: [{ type: 'coupon', title: 'Primeira compra', highlight: '10% OFF', code: 'BEMVINDO' }, { type: 'loyalty', title: 'Fidelidade' }] },
        })
      }
      return base(url)
    })

    render(<PublicStorePage />)

    expect(await screen.findByRole('button', { name: /^Nota 4,8 de 5, 22 avaliações/ })).toBeInTheDocument()
    expect(await screen.findByRole('button', { name: /^Copiar cupom BEMVINDO/ })).toBeInTheDocument()
    expect(screen.queryByText('Fidelidade')).not.toBeInTheDocument()
    expect(screen.getByText('Grátis acima de R$ 80,00')).toBeInTheDocument()
  })

  it('se avaliação e cupons falharem, o cardápio abre normalmente e sem aviso de erro', async () => {
    const { toast } = jest.requireMock('sonner') as { toast: { error: jest.Mock } }
    setupStoreFetchMock()
    const base = (global.fetch as jest.Mock).getMockImplementation()!
    ;(global.fetch as jest.Mock).mockImplementation((url: string) => {
      if (url.includes('/reviews/stats')) return Promise.reject(new Error('offline'))
      if (url.includes('/promotions')) return Promise.resolve({ ok: false, status: 500, json: () => Promise.resolve({}) })
      return base(url)
    })

    render(<PublicStorePage />)

    expect(await screen.findByRole('heading', { level: 2, name: 'Bebidas' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /^Nota / })).not.toBeInTheDocument()
    expect(screen.queryByRole('region', { name: 'Cupons da loja' })).not.toBeInTheDocument()
    expect(toast.error).not.toHaveBeenCalled()
  })
})

describe('PublicStorePage - Barra do carrinho', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockStoreOpen = true
    setupStoreFetchMock()
  })

  it('aparece com itens, mostra o total e "Ver carrinho" abre o resumo', async () => {
    render(<PublicStorePage />)
    expect(screen.queryByRole('button', { name: 'Ver carrinho' })).not.toBeInTheDocument()

    fireEvent.click(await screen.findByRole('button', { name: 'Adicionar Coca-Cola ao carrinho' }))
    expect(screen.getByText('Total sem a entrega')).toBeInTheDocument()

    fireEvent.click(screen.getByRole('button', { name: 'Ver carrinho' }))
    const sheet = await screen.findByRole('dialog')
    expect(within(sheet).getByText('Seu pedido')).toBeInTheDocument()
    expect(within(sheet).getByRole('button', { name: 'Continuar pedido' })).toBeEnabled()
  })

  it('com a loja fechada o resumo aberto pela barra não deixa continuar', async () => {
    mockStoreOpen = false
    render(<PublicStorePage />)
    fireEvent.click(await screen.findByRole('button', { name: 'Adicionar Coca-Cola ao carrinho' }))
    fireEvent.click(screen.getByRole('button', { name: 'Ver carrinho' }))

    const sheet = await screen.findByRole('dialog')
    expect(within(sheet).getByRole('button', { name: 'Continuar pedido' })).toBeDisabled()
    expect(within(sheet).getByText(/A loja está fechada no momento/)).toBeInTheDocument()
  })

  it('mostra quanto o cliente economiza e nunca fala de pedido mínimo', async () => {
    setupStoreFetchMock(mockProducts, {
      ...mockStoreInfo,
      settings: { delivery_pickup: { delivery_minimum_order_enabled: true, delivery_minimum_order_value: 100 } },
    } as typeof mockStoreInfo)
    render(<PublicStorePage />)
    fireEvent.click((await screen.findAllByRole('button', { name: 'Adicionar Pudim ao carrinho' }))[0])

    expect(screen.getByRole('status')).toHaveTextContent('Você economiza R$ 5,00')
    expect(screen.queryByText(/mínimo/i)).not.toBeInTheDocument()
  })
})

describe('PublicStorePage - Horário por método de entrega', () => {
  beforeAll(() => {
    mockStoreOpen = true
  })

  const { toast } = jest.requireMock('sonner') as { toast: { error: jest.Mock } }

  function mockIsOpen(isOpen: boolean) {
    const base = (global.fetch as jest.Mock).getMockImplementation()!
    ;(global.fetch as jest.Mock).mockImplementation((url: string) => {
      if (url.includes('/is-open')) {
        return createJsonFetchResponse({
          success: true,
          data: { is_open: isOpen, is_always_open: false, store_hours: { quarta: [{ start: '18:00', end: '23:00', delivery_type: 'delivery' }] } },
        })
      }
      return base(url)
    })
  }

  async function goToShippingStepWithPickup() {
    render(<PublicStorePage />)
    fireEvent.click(await screen.findByRole('button', { name: 'Adicionar Coca-Cola ao carrinho' }))
    fireEvent.click(screen.getAllByRole('button', { name: /Continuar pedido/i })[0])
    fireEvent.click(await screen.findByRole('button', { name: 'Continuar sem cadastro' }))
    fireEvent.change(await screen.findByLabelText(/Como podemos te chamar/i), { target: { value: 'Ana' } })
    fireEvent.change(screen.getByLabelText(/Celular com WhatsApp/i), { target: { value: '71988887777' } })
    fireEvent.click(screen.getAllByRole('button', { name: /Escolher entrega/i })[0])
    fireEvent.click(await screen.findByRole('radio', { name: 'Buscar no restaurante' }))
    fireEvent.click(screen.getAllByRole('button', { name: /Forma de pagamento/i })[0])
  }

  beforeEach(() => {
    jest.clearAllMocks()
    setupStoreFetchMock()
  })

  it('não avança da etapa de entrega quando a retirada está fechada', async () => {
    mockIsOpen(false)
    await goToShippingStepWithPickup()

    await waitFor(() => {
      expect(toast.error).toHaveBeenCalledWith('Retirada indisponível no momento. A loja não está atendendo retiradas neste horário.')
    })
    expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/is-open?delivery_type=pickup'))
    expect(screen.queryAllByRole('button', { name: /Revisar pedido/i })).toHaveLength(0)
  })

  it('avança para o pagamento quando a retirada está aberta', async () => {
    mockIsOpen(true)
    await goToShippingStepWithPickup()

    expect((await screen.findAllByRole('button', { name: /Revisar pedido/i })).length).toBeGreaterThan(0)
    expect(toast.error).not.toHaveBeenCalled()
  })
})

describe('PublicStorePage - Dados do cliente sem busca pública', () => {
  const { toast } = jest.requireMock('sonner') as { toast: { error: jest.Mock } }

  function mockAuthMe(response: { ok: boolean; status: number; body?: unknown }) {
    const base = (global.fetch as jest.Mock).getMockImplementation()!
    ;(global.fetch as jest.Mock).mockImplementation((url: string, init?: RequestInit) => {
      if (url.includes('/auth/me')) {
        return Promise.resolve({
          ok: response.ok,
          status: response.status,
          headers: { get: () => 'application/json' },
          json: () => Promise.resolve(response.body ?? { success: false }),
        })
      }
      return base(url, init)
    })
  }

  async function goToClientStep() {
    render(<PublicStorePage />)
    fireEvent.click(await screen.findByRole('button', { name: 'Adicionar Coca-Cola ao carrinho' }))
    fireEvent.click(screen.getAllByRole('button', { name: /Continuar pedido/i })[0])
    fireEvent.click(await screen.findByRole('button', { name: 'Continuar sem cadastro' }))
    return screen.findByLabelText(/Como podemos te chamar/i)
  }

  beforeEach(() => {
    jest.clearAllMocks()
    setupStoreFetchMock()
  })

  it('preenche os dados com a sessão do cliente logado nesta loja', async () => {
    mockAuthMe({
      ok: true,
      status: 200,
      body: { success: true, data: { name: 'Maria Logada', email: 'maria@teste.com', phone: '71988887777' } },
    })

    const nameInput = await goToClientStep()

    await waitFor(() => expect((nameInput as HTMLInputElement).value).toBe('Maria Logada'))
    expect((screen.getByLabelText(/Celular com WhatsApp/i) as HTMLInputElement).value).toBe('(71) 98888-7777')
    expect(global.fetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/store/test-store/auth/me'),
      expect.objectContaining({ credentials: 'include' })
    )
  })

  it('sem sessão segue com os campos vazios e sem mensagem de erro', async () => {
    mockAuthMe({ ok: false, status: 401 })

    const nameInput = await goToClientStep()

    expect((nameInput as HTMLInputElement).value).toBe('')
    expect(toast.error).not.toHaveBeenCalled()
  })

  it('digitar um telefone não consulta dados de cliente', async () => {
    mockAuthMe({ ok: false, status: 401 })
    await goToClientStep()

    fireEvent.change(screen.getByLabelText(/Celular com WhatsApp/i), { target: { value: '71988887777' } })
    fireEvent.blur(screen.getByLabelText(/Celular com WhatsApp/i))
    await new Promise((r) => setTimeout(r, 700))

    const urls = (global.fetch as jest.Mock).mock.calls.map(([url]) => String(url))
    expect(urls.some((u) => u.includes('clients/lookup') || u.includes('71988887777'))).toBe(false)
  })
})

describe('PublicStorePage - Cadastro opcional ao continuar o pedido', () => {
  async function addItemAndContinue() {
    render(<PublicStorePage />)
    fireEvent.click(await screen.findByRole('button', { name: 'Adicionar Coca-Cola ao carrinho' }))
    fireEvent.click(screen.getAllByRole('button', { name: /Continuar pedido/i })[0])
    return screen.findByRole('dialog', { name: 'Deseja se cadastrar?' })
  }

  async function openSignupForm() {
    const prompt = await addItemAndContinue()
    fireEvent.click(within(prompt).getByRole('button', { name: 'Sim, quero me cadastrar' }))
    const form = await screen.findByRole('dialog', { name: 'Criar conta' })
    fireEvent.change(within(form).getByLabelText(/Nome Completo/i), { target: { value: 'Maria Nova' } })
    fireEvent.change(within(form).getByLabelText(/^Email/i), { target: { value: 'maria@teste.com' } })
    fireEvent.change(within(form).getByLabelText(/Telefone/i), { target: { value: '71988887777' } })
    fireEvent.change(within(form).getByLabelText(/^Senha/i), { target: { value: 'segredo123' } })
    fireEvent.change(within(form).getByLabelText(/Confirmar Senha/i), { target: { value: 'segredo123' } })
    return form
  }

  const cartTotalIsKept = () => expect(screen.getAllByText(/5,00/).length).toBeGreaterThan(0)

  beforeEach(() => {
    jest.clearAllMocks()
    mockStoreOpen = true
    mockClientAuthenticated = false
    setupStoreFetchMock()
  })

  it('continuar sem cadastro segue para "Seus dados" com o carrinho intacto', async () => {
    const prompt = await addItemAndContinue()
    fireEvent.click(within(prompt).getByRole('button', { name: 'Continuar sem cadastro' }))

    expect(await screen.findByLabelText(/Como podemos te chamar/i)).toHaveValue('')
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    expect(mockRegister).not.toHaveBeenCalled()
    cartTotalIsKept()
  })

  it('cadastro com sucesso preenche "Seus dados" e continua o pedido', async () => {
    mockRegister.mockResolvedValue({ uuid: 'c1', name: 'Maria Nova', email: 'maria@teste.com', phone: '71988887777' })
    const form = await openSignupForm()
    fireEvent.click(within(form).getByRole('button', { name: /Cadastrar e continuar/i }))

    const nameInput = await screen.findByLabelText(/Como podemos te chamar/i)
    expect(nameInput).toHaveValue('Maria Nova')
    expect(screen.getByLabelText(/Celular com WhatsApp/i)).toHaveValue('(71) 98888-7777')
    expect(mockRegister).toHaveBeenCalledWith(expect.objectContaining({ email: 'maria@teste.com' }), 'test-store')
    cartTotalIsKept()
  })

  it('erro no cadastro mostra a mensagem, mantém os dados e não avança', async () => {
    mockRegister.mockRejectedValue(new Error('Erro ao registrar cliente'))
    const form = await openSignupForm()
    fireEvent.click(within(form).getByRole('button', { name: /Cadastrar e continuar/i }))

    expect(await within(form).findByText('Erro ao registrar cliente')).toBeInTheDocument()
    expect(within(form).getByLabelText(/^Email/i)).toHaveValue('maria@teste.com')
    expect(screen.queryByLabelText(/Como podemos te chamar/i)).not.toBeInTheDocument()
  })

  it('e-mail já cadastrado permite continuar sem cadastro sem perder o carrinho', async () => {
    mockRegister.mockRejectedValue(new Error('Email já cadastrado nesta loja'))
    const form = await openSignupForm()
    fireEvent.click(within(form).getByRole('button', { name: /Cadastrar e continuar/i }))

    expect(await within(form).findByText('Email já cadastrado nesta loja')).toBeInTheDocument()
    fireEvent.click(within(form).getByRole('button', { name: 'Continuar sem cadastro' }))
    expect(await screen.findByLabelText(/Como podemos te chamar/i)).toBeInTheDocument()
    cartTotalIsKept()
  })

  it('fechar a modal volta ao cardápio com o carrinho intacto', async () => {
    await openSignupForm()
    fireEvent.keyDown(document.activeElement ?? document.body, { key: 'Escape' })

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
    expect(screen.queryByLabelText(/Como podemos te chamar/i)).not.toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Ver carrinho/i })).toBeInTheDocument()
  })

  it('cliente logado não vê a pergunta', async () => {
    mockClientAuthenticated = true
    render(<PublicStorePage />)
    fireEvent.click(await screen.findByRole('button', { name: 'Adicionar Coca-Cola ao carrinho' }))
    fireEvent.click(screen.getAllByRole('button', { name: /Continuar pedido/i })[0])

    expect(await screen.findByLabelText(/Como podemos te chamar/i)).toBeInTheDocument()
    expect(screen.queryByRole('dialog', { name: 'Deseja se cadastrar?' })).not.toBeInTheDocument()
  })

  it('pergunta só uma vez por visita', async () => {
    const prompt = await addItemAndContinue()
    fireEvent.click(within(prompt).getByRole('button', { name: 'Continuar sem cadastro' }))
    await screen.findByLabelText(/Como podemos te chamar/i)

    fireEvent.click(screen.getAllByRole('button', { name: /Voltar/i })[0])
    fireEvent.click((await screen.findAllByRole('button', { name: /Continuar pedido/i }))[0])

    expect(await screen.findByLabelText(/Como podemos te chamar/i)).toBeInTheDocument()
    expect(screen.queryByRole('dialog', { name: 'Deseja se cadastrar?' })).not.toBeInTheDocument()
  })
})
