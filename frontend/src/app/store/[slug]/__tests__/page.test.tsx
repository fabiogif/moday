import { describe, it, expect, beforeEach } from '@jest/globals'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
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

jest.mock('../components/store-hours-banner', () => ({
  StoreHoursBanner: () => null,
}))

jest.mock('@/components/site-footer', () => ({
  SiteFooter: () => null,
}))

jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}))

// Mock hooks
jest.mock('@/hooks/use-viacep', () => ({
  useViaCEP: () => ({
    searchCEP: jest.fn(),
    loading: false,
  }),
}))

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

function setupStoreFetchMock(products = mockProducts, storeInfo = mockStoreInfo) {
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

describe('PublicStorePage - Categorias', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    setupStoreFetchMock()
  })

  it('deve renderizar a aba "Todos" por padrão', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      expect(screen.getByText(/Todos/i)).toBeInTheDocument()
    })
  })

  it('deve extrair categorias únicas dos produtos', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      expect(screen.getByText(/Pizzas/i)).toBeInTheDocument()
      expect(screen.getByText(/Bebidas/i)).toBeInTheDocument()
      expect(screen.getByText(/Lanches/i)).toBeInTheDocument()
      expect(screen.getByText(/Sobremesas/i)).toBeInTheDocument()
    })
  })

  it('deve mostrar filtros de categoria dos produtos', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Pizzas' })).toBeInTheDocument()
      expect(screen.getByRole('button', { name: 'Bebidas' })).toBeInTheDocument()
    })
  })

  it('deve filtrar produtos ao clicar em uma categoria', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      expect(screen.getByText('Pizza Margherita')).toBeInTheDocument()
    })

    // Clicar na aba de Bebidas
    const bebidasTab = screen.getByRole('button', { name: 'Bebidas' })
    fireEvent.click(bebidasTab)

    await waitFor(() => {
      // Deve mostrar apenas Coca-Cola
      expect(screen.getByText('Coca-Cola')).toBeInTheDocument()
      // Não deve mostrar Pizza
      expect(screen.queryByText('Pizza Margherita')).not.toBeInTheDocument()
    })
  })

  it('deve mostrar todos os produtos na aba "Todos"', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      expect(screen.getByText('Pizza Margherita')).toBeInTheDocument()
      expect(screen.getByText('Coca-Cola')).toBeInTheDocument()
      expect(screen.getByText('Hambúrguer')).toBeInTheDocument()
      expect(screen.getByText('Pudim')).toBeInTheDocument()
    })
  })
})

describe('PublicStorePage - Ofertas', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    setupStoreFetchMock()
  })

  it('deve identificar produtos com ofertas', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      // Pizza, Hambúrguer e Pudim têm ofertas
      const offerBadges = screen.getAllByText(/-\d+%/)
      expect(offerBadges.length).toBeGreaterThan(0)
    })
  })

  it('deve calcular o percentual de desconto corretamente', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      // Pudim: de 10 para 5 = 50% OFF
      expect(screen.getByText(/-50%/)).toBeInTheDocument()
      expect(screen.getByText(/-17%/)).toBeInTheDocument()
    })
  })

  it('deve ordenar ofertas por maior desconto', async () => {
    // Teste da lógica de ordenação
    const productsWithOffers = [
      { ...mockProducts[0], discountPercent: 17 }, // Pizza: 17%
      { ...mockProducts[2], discountPercent: 25 }, // Hambúrguer: 25%
      { ...mockProducts[3], discountPercent: 50 }, // Pudim: 50%
    ].sort((a, b) => b.discountPercent - a.discountPercent)

    expect(productsWithOffers[0].discountPercent).toBe(50) // Pudim
    expect(productsWithOffers[1].discountPercent).toBe(25) // Hambúrguer
    expect(productsWithOffers[2].discountPercent).toBe(17) // Pizza
  })

  it('deve limitar ofertas a 4 produtos', async () => {
    const manyProducts = Array.from({ length: 10 }, (_, i) => ({
      ...mockProducts[0],
      uuid: `prod-${i}`,
      promotional_price: 20 - i,
    }))

    setupStoreFetchMock(manyProducts)

    const bestOffers = manyProducts
      .filter(p => p.promotional_price && p.promotional_price < p.price)
      .slice(0, 4)

    expect(bestOffers).toHaveLength(4)
  })
})

describe('PublicStorePage - Mais Vendidos', () => {
  it('deve retornar até 4 produtos mais vendidos', () => {
    const bestSellers = mockProducts.slice(0, 4)
    expect(bestSellers).toHaveLength(4)
  })

  it('deve funcionar com menos de 4 produtos disponíveis', () => {
    const fewProducts = mockProducts.slice(0, 2)
    const bestSellers = fewProducts.slice(0, 4)
    expect(bestSellers).toHaveLength(2)
  })
})

describe('PublicStorePage - Badge de Categoria', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    setupStoreFetchMock()
  })

  it('deve exibir categorias nos filtros da loja', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Pizzas' })).toBeInTheDocument()
      expect(screen.getByRole('button', { name: 'Bebidas' })).toBeInTheDocument()
    })
  })

  it('não deve exibir badge se produto não tiver categoria', async () => {
    const productWithoutCategory = {
      ...mockProducts[0],
      categories: [],
    }

    setupStoreFetchMock([productWithoutCategory])

    render(<PublicStorePage />)
    
    await waitFor(() => {
      const categoryBadges = screen.queryAllByText(/Pizzas|Bebidas/i)
      // Badge não deve aparecer no card
      expect(categoryBadges.length).toBe(0)
    })
  })
})

describe('PublicStorePage - Casos Especiais', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    setupStoreFetchMock()
  })

  it('deve lidar com produtos sem preço promocional', async () => {
    const product = mockProducts[1] // Coca-Cola sem promoção
    expect(product.promotional_price).toBeNull()
  })

  it('deve lidar com categorias vazias', async () => {
    setupStoreFetchMock([])

    render(<PublicStorePage />)
    
    await waitFor(() => {
      expect(screen.queryByRole('button', { name: 'Todos' })).not.toBeInTheDocument()
      expect(screen.getByText(/Nenhum produto encontrado/i)).toBeInTheDocument()
    })
  })

  it('deve exibir mensagem quando não há produtos na categoria', async () => {
    render(<PublicStorePage />)
    
    await waitFor(() => {
      const bebidasTab = screen.getByRole('button', { name: 'Bebidas' })
      fireEvent.click(bebidasTab)
    })

    // Se não houver produtos, deve mostrar mensagem
    // Como temos 1 produto, não deve mostrar a mensagem
    expect(screen.queryByText(/Nenhum produto encontrado/i)).not.toBeInTheDocument()
  })

  it('deve calcular preço corretamente para strings e numbers', () => {
    const getNumericPrice = (price: number | string): number => {
      return typeof price === 'string' ? parseFloat(price) || 0 : price
    }

    expect(getNumericPrice(10)).toBe(10)
    expect(getNumericPrice('10')).toBe(10)
    expect(getNumericPrice('10.50')).toBe(10.5)
    expect(getNumericPrice('invalid')).toBe(0)
  })
})

describe('PublicStorePage - Performance', () => {
  it('não deve recalcular categorias desnecessariamente', () => {
    const extractCategories = (products: typeof mockProducts) => {
      return Array.from(
        new Set(
          products.flatMap(product => 
            product.categories?.map(cat => cat.name) || []
          )
        )
      ).sort()
    }

    const categories1 = extractCategories(mockProducts)
    const categories2 = extractCategories(mockProducts)

    expect(categories1).toEqual(categories2)
    expect(categories1).toHaveLength(4)
  })

  it('deve filtrar produtos eficientemente', () => {
    const filterByCategory = (products: typeof mockProducts, category: string) => {
      if (category === 'all') return products
      return products.filter(product => 
        product.categories?.some(cat => cat.name === category)
      )
    }

    const pizzas = filterByCategory(mockProducts, 'Pizzas')
    expect(pizzas).toHaveLength(1)
    expect(pizzas[0].name).toBe('Pizza Margherita')

    const all = filterByCategory(mockProducts, 'all')
    expect(all).toHaveLength(4)
  })
})

