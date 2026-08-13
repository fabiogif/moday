import '@testing-library/jest-dom'
import React from 'react'

// Polyfill for React 19 compatibility
if (!React.act) {
  React.act = (callback) => {
    callback()
  }
}

// Mock next/navigation
jest.mock('next/navigation', () => ({
  useRouter() {
    return {
      push: jest.fn(),
      replace: jest.fn(),
      prefetch: jest.fn(),
      back: jest.fn(),
      forward: jest.fn(),
      refresh: jest.fn(),
    }
  },
  useSearchParams() {
    return new URLSearchParams()
  },
  usePathname() {
    return '/'
  },
}))

// Mock next/image
jest.mock('next/image', () => ({
  __esModule: true,
  default: (props) => {
    // eslint-disable-next-line @next/next/no-img-element
    return <img {...props} />
  },
}))

// Mock intersectionObserver
global.IntersectionObserver = class IntersectionObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
}

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
}

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: jest.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: jest.fn(),
    removeListener: jest.fn(),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn(),
  })),
})

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
}
global.localStorage = localStorageMock

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
}
global.sessionStorage = sessionStorageMock

// Mock recharts ResponsiveContainer to avoid zero width/height warnings in JSDOM
jest.mock('recharts', () => {
  const Original = jest.requireActual('recharts')
  const ResponsiveContainer = ({ children }) => (
    <div style={{ width: 800, height: 400 }}>{children}</div>
  )
  return { ...Original, ResponsiveContainer }
})

// Mock use-authenticated-api — defaults reutilizáveis; testes podem sobrescrever via mockReturnValue
const createDefaultApiState = (data = []) => ({
  data,
  loading: false,
  error: null,
  refetch: jest.fn(),
  isAuthenticated: true,
})

const createDefaultMutation = () => ({
  mutate: jest.fn().mockResolvedValue({}),
  loading: false,
  error: null,
})

const defaultOrderStats = {
  total_orders: { current: 100, previous: 90, growth: 11.1 },
  pending_orders: { current: 20, previous: 15, growth: 33.3 },
  paid_orders: { current: 60, previous: 55, growth: 9.1 },
  delivered_orders: { current: 80, previous: 75, growth: 6.7 },
  total_revenue: { current: 15000, previous: 14000, growth: 7.1 },
}

jest.mock('@/hooks/use-authenticated-api', () => ({
  invalidateCache: jest.fn(),
  useAuthenticatedApi: jest.fn(() => createDefaultApiState()),
  useAuthenticatedProducts: jest.fn(() => createDefaultApiState()),
  useAuthenticatedCatalogProducts: jest.fn(() => createDefaultApiState()),
  useAuthenticatedPermissions: jest.fn(() => createDefaultApiState()),
  useAuthenticatedProductStats: jest.fn(() => createDefaultApiState({})),
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
  useAuthenticatedOrderStats: jest.fn(() => createDefaultApiState(defaultOrderStats)),
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