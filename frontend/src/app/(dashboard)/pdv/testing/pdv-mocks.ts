import {
  useAuthenticatedCatalogProducts,
  useAuthenticatedActiveCategories,
  useAuthenticatedTables,
  useAuthenticatedActivePaymentMethods,
  useAuthenticatedClients,
  useAuthenticatedOrdersByTable,
  useAuthenticatedTodayOrders,
  useAuthenticatedActiveServiceTypes,
  useAuthenticatedActiveOrderStatuses,
  useMutation,
} from '@/hooks/use-authenticated-api'
import { useAuth } from '@/contexts/auth-context'

export const defaultPdvHookReturn = {
  loading: false,
  error: null,
  refetch: jest.fn(),
  isAuthenticated: true,
}

export const defaultCategories = [{ uuid: 'cat-1', name: 'Lanches' }]
export const defaultProducts = [
  {
    uuid: 'prod-1',
    name: 'Hambúrguer',
    price: 25,
    promotional_price: null,
    categories: [{ uuid: 'cat-1', name: 'Lanches' }],
  },
]
export const defaultTables = [{ uuid: 'table-1', name: 'Mesa 1' }]
export const defaultPayments = [
  { uuid: 'pix-1', name: 'PIX' },
  { uuid: 'card-1', name: 'Cartão de Crédito' },
  { uuid: 'cash-1', name: 'Dinheiro' },
]
export const defaultClients = [{ uuid: 'client-1', name: 'João Silva', phone: '11999999999' }]
export const defaultServiceTypes = [{ uuid: 'st-1', name: 'Balcão', is_active: true }]
export const defaultOrderStatuses = [
  { uuid: 'os-1', name: 'Pendente', order_position: 1, is_active: true },
  { uuid: 'os-2', name: 'Aceito', order_position: 2, is_active: true },
  { uuid: 'os-3', name: 'Preparo', order_position: 3, is_active: true },
  { uuid: 'os-4', name: 'Concluído', order_position: 4, is_active: true },
  { uuid: 'os-5', name: 'Cancelado', order_position: 5, is_active: true },
]

export const pdvTestMocks = {
  jestAuthenticatedApi: () => ({
    useAuthenticatedCatalogProducts: jest.fn(),
    useAuthenticatedActiveCategories: jest.fn(),
    useAuthenticatedTables: jest.fn(),
    useAuthenticatedActivePaymentMethods: jest.fn(),
    useAuthenticatedClients: jest.fn(),
    useAuthenticatedOrdersByTable: jest.fn(),
    useAuthenticatedTodayOrders: jest.fn(),
    useAuthenticatedActiveServiceTypes: jest.fn(),
    useAuthenticatedActiveOrderStatuses: jest.fn(),
    useMutation: jest.fn(),
  }),
  jestAuth: () => ({
    useAuth: jest.fn(),
  }),
  jestPosHeader: () => ({
    POSHeaderProvider: ({ children }: { children: React.ReactNode }) => children,
    usePOSHeader: () => ({
      setTodayOrdersClick: jest.fn(),
      setTodayOrdersCount: jest.fn(),
      onTodayOrdersClick: null,
      todayOrdersCount: 0,
    }),
  }),
  jestSonner: () => ({
    toast: { success: jest.fn(), error: jest.fn(), info: jest.fn() },
  }),
}

export function setupPdvMocks(overrides: {
  products?: unknown[]
  categories?: unknown[]
  tables?: unknown[]
  payments?: unknown[]
  clients?: unknown[]
  serviceTypes?: unknown[]
  orderStatuses?: unknown[]
  ordersByTable?: unknown[]
  todayOrders?: unknown[]
  mutate?: jest.Mock
} = {}) {
  const mocks = {
    products: overrides.products ?? defaultProducts,
    categories: overrides.categories ?? defaultCategories,
    tables: overrides.tables ?? defaultTables,
    payments: overrides.payments ?? defaultPayments,
    clients: overrides.clients ?? defaultClients,
    serviceTypes: overrides.serviceTypes ?? defaultServiceTypes,
    orderStatuses: overrides.orderStatuses ?? defaultOrderStatuses,
    ordersByTable: overrides.ordersByTable ?? [],
    todayOrders: overrides.todayOrders ?? [],
    mutate: overrides.mutate ?? jest.fn().mockResolvedValue({ identify: 'order-123' }),
  }

  ;(useAuthenticatedActiveCategories as jest.Mock).mockReturnValue({
    data: mocks.categories,
    ...defaultPdvHookReturn,
  })
  ;(useAuthenticatedCatalogProducts as jest.Mock).mockReturnValue({
    data: mocks.products,
    ...defaultPdvHookReturn,
  })
  ;(useAuthenticatedTables as jest.Mock).mockReturnValue({
    data: mocks.tables,
    ...defaultPdvHookReturn,
  })
  ;(useAuthenticatedActivePaymentMethods as jest.Mock).mockReturnValue({
    data: mocks.payments,
    ...defaultPdvHookReturn,
  })
  ;(useAuthenticatedClients as jest.Mock).mockReturnValue({
    data: mocks.clients,
    ...defaultPdvHookReturn,
  })
  ;(useAuthenticatedOrdersByTable as jest.Mock).mockReturnValue({
    data: mocks.ordersByTable,
    ...defaultPdvHookReturn,
  })
  ;(useAuthenticatedTodayOrders as jest.Mock).mockReturnValue({
    data: mocks.todayOrders,
    ...defaultPdvHookReturn,
  })
  ;(useAuthenticatedActiveServiceTypes as jest.Mock).mockReturnValue({
    data: mocks.serviceTypes,
    ...defaultPdvHookReturn,
  })
  ;(useAuthenticatedActiveOrderStatuses as jest.Mock).mockReturnValue({
    data: mocks.orderStatuses,
    ...defaultPdvHookReturn,
  })
  ;(useMutation as jest.Mock).mockReturnValue({
    mutate: mocks.mutate,
    loading: false,
    error: null,
  })
  ;(useAuth as jest.Mock).mockReturnValue({
    user: { id: 1, tenant_id: 1, name: 'Operador Teste' },
    isAuthenticated: true,
    isLoading: false,
  })
}
