import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import POSPage from '@/app/(dashboard)/pdv/page'
import { setupPdvMocks, defaultProducts } from '@/app/(dashboard)/pdv/testing/pdv-mocks'

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

describe('PDV Page', () => {
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

  it('permite adicionar produto ao carrinho', async () => {
    const user = userEvent.setup()
    render(<POSPage />)

    const productButton = await screen.findByTestId('touch-product-prod-1')
    await user.click(productButton)

    await waitFor(() => {
      expect(screen.getByText(/Hambúrguer/i)).toBeInTheDocument()
    })
  })
})
