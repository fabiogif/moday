import { render, screen, waitFor } from '@testing-library/react'
import { PricingSection } from '../components/pricing-section'
import ApiClient from '@/lib/api-client'

jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
  }),
}))

jest.mock('@/lib/api-client', () => ({
  __esModule: true,
  default: {
    get: jest.fn(),
  },
}))

describe('PricingSection', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('deve renderizar o título da seção', async () => {
    (ApiClient.get as jest.Mock).mockRejectedValue(new Error('API Error'))
    render(<PricingSection />)

    await waitFor(() => {
      expect(screen.getByText(/Simples, transparente e sem surpresas/i)).toBeInTheDocument()
    })
  })

  it('deve renderizar loading state inicialmente', () => {
    (ApiClient.get as jest.Mock).mockImplementation(() => new Promise(() => {}))
    render(<PricingSection />)
    expect(screen.getByText(/Carregando planos/i)).toBeInTheDocument()
  })

  it('deve renderizar planos estáticos quando API falhar', async () => {
    (ApiClient.get as jest.Mock).mockRejectedValue(new Error('API Error'))
    
    render(<PricingSection />)
    
    await waitFor(() => {
      expect(screen.getAllByText(/^Grátis$/i).length).toBeGreaterThan(0)
      expect(screen.getByText(/^Básico$/i)).toBeInTheDocument()
      expect(screen.getByText(/^Premium$/i)).toBeInTheDocument()
    })
  })

  it('deve renderizar planos da API quando disponível', async () => {
    const mockPlans = [
      {
        id: 1,
        name: 'Grátis',
        url: 'gratis',
        price: 0,
        description: 'Teste',
        max_users: 1,
        max_products: 50,
        max_orders_per_month: 30,
        has_marketing: false,
        has_order_completion_email: false,
        has_reports: false,
        details: [],
      },
    ];

    (ApiClient.get as jest.Mock).mockResolvedValue({ data: mockPlans })
    
    render(<PricingSection />)
    
    await waitFor(() => {
      expect(screen.getAllByText(/^Grátis$/i).length).toBeGreaterThan(0)
      expect(screen.getByText(/Limites do plano/i)).toBeInTheDocument()
      expect(screen.getByText(/1 usuário/i)).toBeInTheDocument()
      expect(screen.getByText(/Módulo Marketing/i)).toBeInTheDocument()
      expect(screen.getByText(/Cupons e campanhas/i)).toBeInTheDocument()
    })
  })

  it('deve renderizar toggle mensal/anual', async () => {
    (ApiClient.get as jest.Mock).mockRejectedValue(new Error('API Error'))
    
    render(<PricingSection />)
    
    await waitFor(() => {
      expect(screen.getAllByText(/^Grátis$/i).length).toBeGreaterThan(0)
    }, { timeout: 5000 })
    
    // Verifica se o toggle está presente (pode estar em um componente ToggleGroup)
    const toggleElements = screen.queryAllByText(/Mensal|Anual/i)
    expect(toggleElements.length).toBeGreaterThan(0)
  })

  it('deve destacar o plano popular', async () => {
    (ApiClient.get as jest.Mock).mockRejectedValue(new Error('API Error'))
    
    render(<PricingSection />)
    
    await waitFor(() => {
      expect(screen.getByText(/Mais popular/i)).toBeInTheDocument()
    })
  })

  it('deve exibir trial de 7 dias apenas em planos pagos', async () => {
    (ApiClient.get as jest.Mock).mockRejectedValue(new Error('API Error'))

    render(<PricingSection />)

    await waitFor(() => {
      expect(screen.getByText(/Planos Básico e Premium incluem 7 dias de teste grátis/i)).toBeInTheDocument()
      expect(screen.getByText(/Para sempre, sem cartão/i)).toBeInTheDocument()
      expect(screen.getAllByText(/7 dias de teste grátis/i).length).toBeGreaterThanOrEqual(2)
      expect(screen.getAllByText(/Iniciar teste grátis/i).length).toBeGreaterThanOrEqual(2)
    })
  })
})

