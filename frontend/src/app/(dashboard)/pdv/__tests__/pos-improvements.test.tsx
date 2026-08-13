import { render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import POSPage from '@/app/(dashboard)/pdv/page'
import { setupPdvMocks, defaultProducts, defaultCategories } from '../testing/pdv-mocks'

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

describe('PDV - Melhorias Implementadas', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    setupPdvMocks()
  })

  it('renderiza produtos do catálogo', async () => {
    render(<POSPage />)

    await waitFor(() => {
      expect(screen.getByText(defaultProducts[0].name)).toBeInTheDocument()
    })
  })

  it('renderiza categorias touch-first', async () => {
    render(<POSPage />)

    await waitFor(() => {
      expect(screen.getByTestId('touch-category-cat-1')).toBeInTheDocument()
      expect(screen.getByText(defaultCategories[0].name)).toBeInTheDocument()
    })
  })

  it('renderiza busca de produtos', async () => {
    render(<POSPage />)

    expect(
      await screen.findByPlaceholderText(/buscar produtos por nome ou código/i)
    ).toBeInTheDocument()
  })

  const getMobileNav = (container: HTMLElement) => {
    const nav = container.querySelector('nav.lg\\:hidden')
    expect(nav).toBeTruthy()
    return within(nav as HTMLElement)
  }

  it('permite adicionar produto ao carrinho', async () => {
    const user = userEvent.setup()
    const { container } = render(<POSPage />)

    const productButton = await screen.findByTestId('touch-product-prod-1')
    await user.click(productButton)

    await user.click(getMobileNav(container).getByRole('button', { name: /Pedido/i }))

    await waitFor(() => {
      expect(screen.getAllByText('Carrinho').length).toBeGreaterThan(0)
      expect(screen.getAllByText(/Hambúrguer/i).length).toBeGreaterThan(0)
    })
  })

  it('exibe navegação mobile entre produtos e pedido', () => {
    const { container } = render(<POSPage />)
    const mobileNav = getMobileNav(container)

    expect(mobileNav.getByRole('button', { name: /^Produtos$/ })).toBeInTheDocument()
    expect(mobileNav.getByRole('button', { name: /Pedido/i })).toBeInTheDocument()
  })

  it('exibe seção de pagamento na aba do carrinho', async () => {
    const user = userEvent.setup()
    const { container } = render(<POSPage />)

    await user.click(await screen.findByTestId('touch-product-prod-1'))
    await user.click(getMobileNav(container).getByRole('button', { name: /Pedido/i }))
    await user.click(screen.getByRole('tab', { name: /pagamento/i }))

    await waitFor(() => {
      expect(container.querySelector('#payment-section')).toBeInTheDocument()
    })
  })
})
