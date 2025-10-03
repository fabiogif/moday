/**
 * Cliente HTTP padronizado para comunicação com a API Laravel
 * Inclui autenticação JWT, tratamento de erros e cache
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost'

interface ApiResponse<T = any> {
  success: boolean
  message: string
  data: T
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

interface ApiError {
  success: false
  message: string
  data?: any
  errors?: Record<string, string[]>
}

class ApiClient {
  private baseURL: string
  private token: string | null = null

  constructor(baseURL: string = API_BASE_URL) {
    this.baseURL = baseURL
    this.loadToken()
  }

  private loadToken() {
    if (typeof window !== 'undefined') {
      // Primeiro tenta pegar do localStorage
      this.token = localStorage.getItem('auth_token')
      
      // Se não encontrar, tenta pegar do cookie
      if (!this.token) {
        const cookies = document.cookie.split(';')
        const authCookie = cookies.find(cookie => cookie.trim().startsWith('auth-token='))
        if (authCookie) {
          this.token = authCookie.split('=')[1]
        }
      }
      
      console.log('ApiClient: Token carregado:', this.token ? 'Sim' : 'Não')
    }
  }

  setToken(token: string) {
    this.token = token
    if (typeof window !== 'undefined') {
      localStorage.setItem('auth_token', token)
      // Também salvar no cookie para sincronizar com AuthContext
      document.cookie = `auth-token=${token}; path=/; max-age=${7 * 24 * 60 * 60}`
    }
    console.log('ApiClient: Token definido:', token ? 'Sim' : 'Não')
  }

  // Função para forçar recarga do token
  reloadToken() {
    this.loadToken()
  }

  // Função para obter o token atual
  getToken() {
    return this.token
  }

  clearToken() {
    this.token = null
    if (typeof window !== 'undefined') {
      localStorage.removeItem('auth_token')
      // Também remover do cookie
      document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'
    }
  }

  private getHeaders(isFormData = false): HeadersInit {
    const headers: HeadersInit = {
      'Accept': 'application/json',
    }

    // Não definir Content-Type para FormData, deixar o navegador definir
    if (!isFormData) {
      headers['Content-Type'] = 'application/json'
    }

    if (this.token) {
      headers.Authorization = `Bearer ${this.token}`
      console.log('ApiClient: Enviando token JWT:', this.token.substring(0, 20) + '...')
    } else {
      console.log('ApiClient: Nenhum token disponível')
    }

    return headers
  }

  private async handleResponse<T>(response: Response): Promise<ApiResponse<T>> {
    try {
      const data = await response.json()

      if (!response.ok) {
        console.error('ApiClient: Erro HTTP', response.status, ':', data.message || data)
        const error: ApiError = {
          success: false,
          message: data.message || 'Erro na requisição',
          data: data.data,
          errors: data.errors
        }
        throw error
      }

      return data as ApiResponse<T>
    } catch (parseError) {
      if (parseError instanceof Error && parseError.message.includes('JSON')) {
        console.error('ApiClient: Resposta não é JSON válido. Status:', response.status)
        const text = await response.text()
        console.error('ApiClient: Conteúdo da resposta:', text.substring(0, 200))
      }
      throw parseError
    }
  }

  async get<T>(endpoint: string, params?: Record<string, any>): Promise<ApiResponse<T>> {
    const url = new URL(`${this.baseURL}${endpoint}`)
    
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          url.searchParams.append(key, String(value))
        }
      })
    }

    console.log('ApiClient: GET:', url.toString())

    const response = await fetch(url.toString(), {
      method: 'GET',
      headers: this.getHeaders(false),
    })

    return this.handleResponse<T>(response)
  }

  async post<T>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    const isFormData = data instanceof FormData
    
    console.log('ApiClient: POST:', `${this.baseURL}${endpoint}`, 'isFormData:', isFormData)

    const response = await fetch(`${this.baseURL}${endpoint}`, {
      method: 'POST',
      headers: this.getHeaders(isFormData),
      body: isFormData ? data : (data ? JSON.stringify(data) : undefined),
    })

    return this.handleResponse<T>(response)
  }

  async put<T>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    const isFormData = data instanceof FormData

    const response = await fetch(`${this.baseURL}${endpoint}`, {
      method: 'PUT',
      headers: this.getHeaders(isFormData),
      body: isFormData ? data : (data ? JSON.stringify(data) : undefined),
    })

    return this.handleResponse<T>(response)
  }

  async delete<T>(endpoint: string): Promise<ApiResponse<T>> {
    const response = await fetch(`${this.baseURL}${endpoint}`, {
      method: 'DELETE',
      headers: this.getHeaders(false),
    })

    return this.handleResponse<T>(response)
  }
}

// Instância singleton do cliente
export const apiClient = new ApiClient()

// Hooks para React Query (se disponível)
export const useApiClient = () => apiClient

// Utilitários para endpoints específicos
export const endpoints = {
  // Autenticação
  auth: {
    login: '/api/auth/login',
    register: '/api/auth/register',
    logout: '/api/auth/logout',
    me: '/api/auth/me',
  },
  
  // Produtos
  products: {
    list: '/api/product',
    stats: '/api/product/stats', // Added
    create: '/api/product',
    show: (id: string) => `/api/product/${id}`,
    update: (id: number) => `/api/product/${id}`,
    delete: (id: string) => `/api/product/${id}`,
  },
  
  // Categorias
  categories: {
    list: '/api/category',
    stats: '/api/category/stats',
    create: '/api/category',
    show: (id: string) => `/api/category/${id}`,
    update: (id: number) => `/api/category/${id}`,
    delete: (id: string) => `/api/category/${id}`,
  },
  
  // Pedidos
  orders: {
    list: '/api/order',
    create: '/api/order',
    show: (id: string) => `/api/order/${id}`,
    update: (id: string) => `/api/order/${id}`,
    delete: (id: string) => `/api/order/${id}`,
    invoice: (id: string) => `/api/order/${id}/invoice`,
    receipt: (id: string) => `/api/order/${id}/receipt`,
  },
  
  // Mesas
  tables: {
    list: '/api/table',
    stats: '/api/table/stats',
    create: '/api/table',
    show: (id: string) => `/api/table/${id}`,
    update: (id: number) => `/api/table/${id}`,
    delete: (id: string) => `/api/table/${id}`,
  },
  
  // Usuários
  users: {
    list: '/api/user',
    create: '/api/user',
    show: (id: string) => `/api/user/${id}`,
    update: (id: number) => `/api/user/${id}`,
    delete: (id: string) => `/api/user/${id}`,
  },

  // Permissões
  permissions: {
    list: '/api/permissions',
    create: '/api/permissions',
    show: (id: string) => `/api/permissions/${id}`,
    update: (id: number) => `/api/permissions/${id}`,
    delete: (id: string) => `/api/permissions/${id}`,
  },

  // Roles
  roles: {
    list: '/api/role',
    create: '/api/role',
    show: (id: string) => `/api/role/${id}`,
    update: (id: number) => `/api/role/${id}`,
    delete: (id: string) => `/api/role/${id}`,
  },

  // Perfis
  profiles: {
    list: '/api/profile',
    create: '/api/profile',
    show: (id: string) => `/api/profile/${id}`,
    update: (id: number) => `/api/profile/${id}`,
    delete: (id: string) => `/api/profile/${id}`,
  },
  
  // Clientes
  clients: {
    list: '/api/client',
    stats: '/api/client/stats',
    create: '/api/client',
    show: (id: string) => `/api/client/${id}`,
    update: (id: number) => `/api/client/${id}`,
    delete: (id: string) => `/api/client/${id}`,
  },

  // Formas de Pagamento
  paymentMethods: {
    list: '/api/payment-methods',
    active: '/api/payment-methods/active',
    create: '/api/payment-methods',
    show: (uuid: string) => `/api/payment-methods/${uuid}`,
    update: (uuid: string) => `/api/payment-methods/${uuid}`,
    delete: (uuid: string) => `/api/payment-methods/${uuid}`,
  },
} as const

export default apiClient
