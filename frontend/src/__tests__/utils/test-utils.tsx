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

jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedProducts: jest.fn(),
  useAuthenticatedCategories: jest.fn(),
  useAuthenticatedRoles: jest.fn(),
  useAuthenticatedPermissions: jest.fn(),
  useMutation: jest.fn(),
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
  category: 'Electronics',
  stock: 10,
  isActive: true,
  createdAt: '2024-01-15',
  ...overrides,
})

export const generateCategory = (overrides = {}) => ({
  id: 1,
  name: 'Electronics',
  description: 'Electronic products',
  color: '#3b82f6',
  productCount: 5,
  isActive: true,
  createdAt: '2024-01-15',
  ...overrides,
})

export const generateOrder = (overrides = {}) => ({
  id: 1,
  orderNumber: 'ORD-001',
  customerName: 'John Doe',
  customerEmail: 'john@example.com',
  status: 'pending',
  total: 299.99,
  items: 3,
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
  email: 'client@example.com',
  phone: '+1234567890',
  address: '123 Main St',
  totalOrders: 5,
  lastOrder: '2024-01-15',
  isActive: true,
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