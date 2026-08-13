import React, { ReactElement } from 'react'
import { render, RenderOptions } from '@testing-library/react'
import { ThemeProvider } from 'next-themes'

// Mock API client
jest.mock('@/lib/api-client', () => ({
  endpoints: {
    users: {
      list: '/api/users',
      create: '/api/users',
      update: (id: string) => `/api/users/${id}`,
      delete: (id: string) => `/api/users/${id}`,
    },
    products: {
      list: '/api/products',
      create: '/api/products',
      update: (id: string) => `/api/products/${id}`,
      delete: (id: string) => `/api/products/${id}`,
    },
    categories: {
      list: '/api/categories',
      create: '/api/categories',
      update: (id: string) => `/api/categories/${id}`,
      delete: (id: string) => `/api/categories/${id}`,
    },
    orders: {
      list: '/api/orders',
      create: '/api/orders',
      update: (id: string) => `/api/orders/${id}`,
      delete: (id: string) => `/api/orders/${id}`,
    },
    roles: {
      list: '/api/roles',
      create: '/api/roles',
      update: (id: string) => `/api/roles/${id}`,
      delete: (id: string) => `/api/roles/${id}`,
    },
    permissions: {
      list: '/api/permissions',
      create: '/api/permissions',
      update: (id: string) => `/api/permissions/${id}`,
      delete: (id: string) => `/api/permissions/${id}`,
    },
  },
  apiClient: {
    get: jest.fn(),
    post: jest.fn(),
    put: jest.fn(),
    delete: jest.fn(),
  },
}))

// Mock hooks
jest.mock('@/hooks/use-api', () => ({
  useUsers: jest.fn(),
  useOrders: jest.fn(),
  useMutation: jest.fn(),
}))

const createDefaultApiState = (data: unknown = []) => ({
  data,
  loading: false,
  error: null,
  refetch: jest.fn(),
  isAuthenticated: true,
  pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
})

const createDefaultMutation = () => ({
  mutate: jest.fn().mockResolvedValue({}),
  loading: false,
  error: null,
})

jest.mock('@/hooks/use-authenticated-api', () => ({
  invalidateCache: jest.fn(),
  useAuthenticatedApi: jest.fn(() => createDefaultApiState()),
  useAuthenticatedProducts: jest.fn(() => createDefaultApiState()),
  useAuthenticatedCatalogProducts: jest.fn(() => createDefaultApiState()),
  useAuthenticatedPermissions: jest.fn(() => createDefaultApiState()),
  useAuthenticatedProductStats: jest.fn(() => createDefaultApiState({ total: 0, active: 0, inactive: 0, out_of_stock: 0 })),
  useAuthenticatedCategories: jest.fn(() => createDefaultApiState()),
  useAuthenticatedCategoryStats: jest.fn(() =>
    createDefaultApiState({
      total_categories: 0,
      active_categories: 0,
      inactive_categories: 0,
      avg_products_per_category: 0,
      total_products: 0,
    })
  ),
  useAuthenticatedOrders: jest.fn(() => createDefaultApiState()),
  useAuthenticatedOrderStats: jest.fn(() => createDefaultApiState({})),
  useAuthenticatedTables: jest.fn(() => createDefaultApiState()),
  useAuthenticatedPlans: jest.fn(() => createDefaultApiState()),
  useAuthenticatedServiceTypes: jest.fn(() => createDefaultApiState()),
  useAuthenticatedActiveServiceTypes: jest.fn(() => createDefaultApiState()),
  useAuthenticatedMenuServiceTypes: jest.fn(() => createDefaultApiState()),
  useAuthenticatedTableStats: jest.fn(() => createDefaultApiState({})),
  useAuthenticatedProfiles: jest.fn(() => createDefaultApiState()),
  useAuthenticatedUsers: jest.fn(() => createDefaultApiState()),
  useAuthenticatedRoles: jest.fn(() => createDefaultApiState()),
  useAuthenticatedClients: jest.fn(() => createDefaultApiState()),
  useAuthenticatedOrdersByTable: jest.fn(() => createDefaultApiState()),
  useAuthenticatedTodayOrders: jest.fn(() => createDefaultApiState()),
  useAuthenticatedClientStats: jest.fn(() =>
    createDefaultApiState({
      total_clients: { current: 0, previous: 0, growth: 0 },
      active_clients: { current: 0, previous: 0, growth: 0 },
      orders_per_client: { current: 0, previous: 0, growth: 0 },
      new_clients: { current: 0, previous: 0, growth: 0 },
    })
  ),
  useAuthenticatedReviews: jest.fn(() => createDefaultApiState()),
  useAuthenticatedReviewStats: jest.fn(() => createDefaultApiState({})),
  useAuthenticatedRecentReviews: jest.fn(() => createDefaultApiState()),
  useAuthenticatedPaymentMethods: jest.fn(() => createDefaultApiState()),
  useAuthenticatedActivePaymentMethods: jest.fn(() => createDefaultApiState()),
  useMutation: jest.fn(() => createDefaultMutation()),
  useMutationWithValidation: jest.fn(() => createDefaultMutation()),
}))

const AllTheProviders = ({ children }: { children: React.ReactNode }) => {
  return (
    <ThemeProvider attribute="class" defaultTheme="light" enableSystem={false}>
      {children}
    </ThemeProvider>
  )
}

const customRender = (
  ui: ReactElement,
  options?: Omit<RenderOptions, 'wrapper'>,
) => render(ui, { wrapper: AllTheProviders, ...options })

export * from '@testing-library/react'
export { customRender as render }

// Common test data generators
export const generateUser = (overrides = {}) => ({
  id: 1,
  name: 'John Doe',
  email: 'john@example.com',
  avatar: 'JD',
  role: 'Admin',
  plan: 'Pro',
  billing: 'Monthly',
  status: 'Active',
  joinedDate: '2024-01-15',
  lastLogin: '2024-01-30',
  ...overrides,
})

export const generateProduct = (overrides = {}) => ({
  id: 1,
  name: 'Test Product',
  description: 'Test Description',
  price: 99.99,
  price_cost: 50,
  categories: [{ identify: 'cat-1', name: 'Electronics' }],
  qtd_stock: 10,
  is_active: true,
  created_at: '2024-01-15',
  createdAt: '2024-01-15',
  ...overrides,
})

export const generateCategory = (overrides = {}) => ({
  id: 1,
  identify: 'cat-1',
  name: 'Electronics',
  description: 'Electronic products',
  url: '',
  color: '#3b82f6',
  productCount: 5,
  isActive: true,
  status: 'active',
  created_at: '2024-01-15',
  createdAt: '2024-01-15',
  ...overrides,
})

export const generateOrder = (overrides = {}) => ({
  id: 1,
  identify: 'ORD-001',
  orderNumber: 'ORD-001',
  client: {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    phone: '11999999999',
  },
  customerName: 'John Doe',
  customerEmail: 'john@example.com',
  status: 'Em Preparo',
  total: 299.99,
  products: [{ id: 1, name: 'Produto A', quantity: 1, price: 299.99 }],
  items: 1,
  date: '15/01/2024',
  orderDate: '2024-01-15',
  deliveryDate: '2024-01-20',
  ...overrides,
})

export const generateRole = (overrides = {}) => ({
  id: 1,
  name: 'Admin',
  slug: 'admin',
  created_at: '2024-01-15',
  updated_at: '2024-01-15',
  ...overrides,
})

export const generatePermission = (overrides = {}) => ({
  id: 1,
  name: 'Create Users',
  slug: 'create-users',
  description: 'Can create new users',
  created_at: '2024-01-15',
  updated_at: '2024-01-15',
  ...overrides,
})

export const generateClient = (overrides = {}) => ({
  id: 1,
  name: 'John Client',
  cpf: '123.456.789-00',
  email: 'client@example.com',
  phone: '+1234567890',
  address: '123 Main St',
  full_address: '123 Main St',
  total_orders: 5,
  totalOrders: 5,
  last_order: '15/01/2024',
  lastOrder: '2024-01-15',
  is_active: true,
  isActive: true,
  created_at: '2024-01-15',
  created_at_formatted: '15/01/2024',
  updated_at: '2024-01-15',
  createdAt: '2024-01-15',
  ...overrides,
})

export const generateTask = (overrides = {}) => ({
  id: 'task-1',
  title: 'Test Task',
  status: 'todo',
  label: 'bug',
  priority: 'medium',
  ...overrides,
})

// Ensure this file is treated as a valid test suite by providing a noop test
describe('test-utils bootstrap', () => {
  it('bootstrap loaded', () => {
    expect(true).toBe(true)
  })
})