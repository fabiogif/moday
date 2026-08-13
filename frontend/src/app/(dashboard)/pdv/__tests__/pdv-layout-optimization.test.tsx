import { render, screen, waitFor } from '@testing-library/react'
import POSPage from '@/app/(dashboard)/pdv/page'
import { setupPdvMocks, defaultProducts } from '../testing/pdv-mocks'

jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedProducts: jest.fn(),
  useAuthenticatedCatalogProducts: jest.fn(),
  useAuthenticatedCategories: jest.fn(),
  useAuthenticatedTables: jest.fn(),
  useAuthenticatedActivePaymentMethods: jest.fn(),
  useAuthenticatedClients: jest.fn(),
  useAuthenticatedOrdersByTable: jest.fn(),
  useAuthenticatedTodayOrders: jest.fn(),
  useAuthenticatedActiveServiceTypes: jest.fn(),
  useMutation: jest.fn(),
}))

jest.mock('@/contexts/auth-context', () => ({
  useAuth: jest.fn(),
}))

jest.mock('@/contexts/pos-header-context', () => ({
  POSHeaderProvider: ({ children }: { children: React.ReactNode }) => children,
  usePOSHeader: () => ({
    setTodayOrdersClick: jest.fn(),
    setTodayOrdersCount: jest.fn(),
    onTodayOrdersClick: null,
    todayOrdersCount: 0,
  }),
}))

jest.mock('sonner', () => ({
  toast: { success: jest.fn(), error: jest.fn(), info: jest.fn() },
}))

describe('PDV - Layout e Viewport', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    setupPdvMocks()
  })

  it('usa layout principal com altura de viewport e overflow controlado', () => {
    const { container } = render(<POSPage />)

    const mainLayout = container.querySelector(
      '.flex.min-h-0.flex-col.overflow-hidden'
    )
    expect(mainLayout).toBeInTheDocument()
  })

  it('renderiza seções principais do catálogo e carrinho', () => {
    const { container } = render(<POSPage />)

    expect(container.querySelector('#categories-section')).toBeInTheDocument()
    expect(container.querySelector('#products-section')).toBeInTheDocument()
    expect(container.querySelector('#order-summary')).toBeInTheDocument()
    expect(container.querySelector('#search-section')).toBeInTheDocument()
  })

  it('renderiza grids touch de categorias e produtos', async () => {
    render(<POSPage />)

    await waitFor(() => {
      expect(screen.getByTestId('touch-grid-categories')).toBeInTheDocument()
      expect(screen.getByTestId('touch-grid-products')).toBeInTheDocument()
      expect(screen.getByText(defaultProducts[0].name)).toBeInTheDocument()
    })
  })

  it('usa layout de duas colunas no desktop', () => {
    Object.defineProperty(window, 'matchMedia', {
      writable: true,
      value: jest.fn().mockImplementation((query: string) => ({
        matches: query.includes('min-width'),
        media: query,
        onchange: null,
        addListener: jest.fn(),
        removeListener: jest.fn(),
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
      })),
    })

    const { container } = render(<POSPage />)

    const catalogColumn = container.querySelector('section.lg\\:flex-\\[3\\]')
    const cartColumn = container.querySelector('aside.lg\\:flex-\\[2\\]')
    expect(catalogColumn).toBeInTheDocument()
    expect(cartColumn).toBeInTheDocument()
  })

  it('exibe navegação inferior apenas em mobile', () => {
    const { container } = render(<POSPage />)

    const mobileNav = container.querySelector('nav.lg\\:hidden')
    expect(mobileNav).toBeInTheDocument()
  })

  it('mantém cards com bordas arredondadas', () => {
    const { container } = render(<POSPage />)

    const roundedElements = container.querySelectorAll('.rounded-xl, .rounded-2xl, .rounded-\\[14px\\]')
    expect(roundedElements.length).toBeGreaterThan(0)
  })
})
