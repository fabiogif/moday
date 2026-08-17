/**
 * Cliente API para endpoints administrativos
 */

import { buildApiUrl, getApiBaseUrl } from '@/lib/api-config'
import { getAdminToken, updateAdminToken, clearAdminSession } from '@/lib/admin-auth-storage'

const API_BASE_URL = getApiBaseUrl()

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

export interface AdminPlanPayload {
  name: string
  url: string
  price: number
  description?: string
  is_active: boolean
  max_users: number | null
  max_products: number | null
  max_orders_per_month: number | null
  has_marketing: boolean
  has_order_completion_email: boolean
  has_reports: boolean
  details?: Array<{ name: string }>
}

class AdminApiClient {
  private baseURL: string
  private token: string | null = null

  constructor(baseURL: string = API_BASE_URL) {
    this.baseURL = baseURL
    this.loadToken()
  }

  private loadToken() {
    if (typeof window !== 'undefined') {
      this.token = getAdminToken()
    }
  }

  setToken(token: string) {
    this.token = token
    updateAdminToken(token)
  }

  // Método público para recarregar token do localStorage/sessionStorage
  reloadToken() {
    this.loadToken()
  }

  clearToken() {
    this.token = null
    clearAdminSession()
  }

  private async requestAbsolute<T = any>(
    path: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    this.loadToken()

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers as Record<string, string>),
    }

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`
    }

    const response = await fetch(buildApiUrl(path), {
      ...options,
      headers,
      credentials: 'include',
    })

    const contentType = response.headers.get('content-type')
    if (!contentType || !contentType.includes('application/json')) {
      throw new Error(`Resposta inválida do servidor: ${response.status} ${response.statusText}`)
    }

    const data = await response.json()

    if (!response.ok) {
      const errorMessage = data.message || 'Erro na requisição'
      const error: Error & { status?: number; data?: unknown } = new Error(errorMessage)
      error.status = response.status
      error.data = data
      throw error
    }

    return data
  }

  private async request<T = any>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    // Sempre recarregar o token do localStorage antes de cada requisição
    // Isso garante que o token mais recente seja usado
    this.loadToken()

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers as Record<string, string>),
    }

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`
    } else {
      // Se não tiver token após recarregar, pode ser um problema
      console.warn('[AdminApiClient] Token não encontrado para requisição:', endpoint)
    }

    const response = await fetch(`${this.baseURL}/api/admin${endpoint}`, {
      ...options,
      headers,
      credentials: 'include',
    })

    // Verificar se a resposta é JSON antes de fazer parse
    const contentType = response.headers.get('content-type')
    if (!contentType || !contentType.includes('application/json')) {
      throw new Error(`Resposta inválida do servidor: ${response.status} ${response.statusText}`)
    }

    const data = await response.json()

    if (!response.ok) {
      // Tratar estrutura padrão da aplicação
      const errorMessage = data.message || 'Erro na requisição'
      const error: any = new Error(errorMessage)
      error.status = response.status
      error.data = data
      throw error
    }

    return data
  }

  // ============================================================================
  // AUTH
  // ============================================================================

  async login(email: string, password: string) {
    return this.request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    })
  }

  async logout() {
    return this.request('/auth/logout', {
      method: 'POST',
    })
  }

  async me() {
    return this.request('/auth/me')
  }

  async refreshToken() {
    return this.request('/auth/refresh', {
      method: 'POST',
    })
  }

  async updateProfile(data: { name: string; email: string }) {
    return this.request('/auth/profile', {
      method: 'PUT',
      body: JSON.stringify(data),
    })
  }

  async updatePassword(data: {
    current_password: string
    password: string
    password_confirmation: string
  }) {
    return this.request('/auth/password', {
      method: 'PUT',
      body: JSON.stringify(data),
    })
  }

  // ============================================================================
  // DASHBOARD
  // ============================================================================

  async getDashboardStats() {
    return this.request('/dashboard')
  }

  async getRecentActivity(limit = 20) {
    return this.request(`/dashboard/activity?limit=${limit}`)
  }

  async getAlerts() {
    return this.request('/dashboard/alerts')
  }

  // ============================================================================
  // TENANTS
  // ============================================================================

  async getTenants(params?: {
    search?: string
    status?: string
    plan?: string
    blocked?: boolean
    sort_by?: string
    sort_direction?: 'asc' | 'desc'
    per_page?: number
    page?: number
  }) {
    const queryString = new URLSearchParams(
      Object.entries(params || {}).reduce((acc, [key, value]) => {
        if (value !== undefined && value !== null) {
          acc[key] = String(value)
        }
        return acc
      }, {} as Record<string, string>)
    ).toString()

    return this.request(`/tenants${queryString ? `?${queryString}` : ''}`)
  }

  async getTenant(id: string | number) {
    return this.request(`/tenants/${id}`)
  }

  async updateTenant(id: string | number, data: {
    admin_notes?: string
    users_limit?: number
    messages_limit?: number
  }) {
    return this.request(`/tenants/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
  }

  async activateTenant(id: string | number) {
    return this.request(`/tenants/${id}/activate`, {
      method: 'POST',
    })
  }

  async suspendTenant(id: string | number, reason: string) {
    return this.request(`/tenants/${id}/suspend`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    })
  }

  async blockTenant(id: string | number, reason: string) {
    return this.request(`/tenants/${id}/block`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    })
  }

  async unblockTenant(id: string | number) {
    return this.request(`/tenants/${id}/unblock`, {
      method: 'POST',
    })
  }

  async updateTenantPlan(id: string | number, plan: string, mrr: number) {
    return this.request(`/tenants/${id}/plan`, {
      method: 'PUT',
      body: JSON.stringify({ plan, mrr }),
    })
  }

  async updateTenantOwnerEmail(id: string | number, email: string) {
    return this.request(`/tenants/${id}/owner-email`, {
      method: 'PUT',
      body: JSON.stringify({ email }),
    })
  }

  async resetTenantOwnerPassword(
    id: string | number,
    data?: { password?: string; password_confirmation?: string }
  ) {
    return this.request(`/tenants/${id}/owner-password-reset`, {
      method: 'POST',
      body: JSON.stringify(data ?? {}),
    })
  }

  async migrateTenantPlan(data: {
    tenant_id: number
    plan_id: number
    reason?: string
    notify_tenant?: boolean
  }) {
    return this.request('/plan-migrations/migrate', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  async getPlanMigrationHistory(params?: {
    tenant_id?: number
    plan_id?: number
    limit?: number
  }) {
    const query = new URLSearchParams()
    if (params?.tenant_id) query.append('tenant_id', String(params.tenant_id))
    if (params?.plan_id) query.append('plan_id', String(params.plan_id))
    if (params?.limit) query.append('limit', String(params.limit))

    const suffix = query.toString() ? `?${query.toString()}` : ''
    return this.request(`/plan-migrations/history${suffix}`)
  }

  async pauseTenantAccess(id: string | number, reason: string) {
    return this.request(`/tenants/${id}/pause-access`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    })
  }

  async restoreTenantAccess(id: string | number) {
    return this.request(`/tenants/${id}/restore-access`, {
      method: 'POST',
    })
  }

  async deleteTenant(id: string | number) {
    return this.request(`/tenants/${id}`, {
      method: 'DELETE',
    })
  }

  async forceDeleteTenant(id: string | number) {
    return this.request(`/tenants/${id}/force`, {
      method: 'DELETE',
    })
  }

  // ============================================================================
  // METRICS
  // ============================================================================

  async getRevenueMetrics(startDate?: string, endDate?: string) {
    const params = new URLSearchParams()
    if (startDate) params.append('start_date', startDate)
    if (endDate) params.append('end_date', endDate)

    return this.request(`/metrics/revenue${params.toString() ? `?${params}` : ''}`)
  }

  async getUsageMetrics(startDate?: string, endDate?: string) {
    const params = new URLSearchParams()
    if (startDate) params.append('start_date', startDate)
    if (endDate) params.append('end_date', endDate)

    return this.request(`/metrics/usage${params.toString() ? `?${params}` : ''}`)
  }

  async getMessagesMetrics(startDate?: string, endDate?: string) {
    const params = new URLSearchParams()
    if (startDate) params.append('start_date', startDate)
    if (endDate) params.append('end_date', endDate)

    return this.request(`/metrics/messages${params.toString() ? `?${params}` : ''}`)
  }

  async getGrowthMetrics() {
    return this.request('/metrics/growth')
  }

  async getTenantMetrics(tenantId: string | number, startDate?: string, endDate?: string) {
    const params = new URLSearchParams()
    if (startDate) params.append('start_date', startDate)
    if (endDate) params.append('end_date', endDate)

    return this.request(`/metrics/tenant/${tenantId}${params.toString() ? `?${params}` : ''}`)
  }

  // ============================================================================
  // PLANS (rotas /api/admin/plans)
  // ============================================================================

  async getPlans(params?: { per_page?: number; page?: number; filter?: string }) {
    const query = new URLSearchParams()
    if (params?.per_page) query.append('per_page', String(params.per_page))
    if (params?.page) query.append('page', String(params.page))
    if (params?.filter) query.append('filter', params.filter)

    const suffix = query.toString() ? `?${query.toString()}` : ''
    return this.request(`/plans${suffix}`)
  }

  async getPlan(id: string | number) {
    return this.request(`/plans/${id}`)
  }

  async createPlan(data: AdminPlanPayload) {
    return this.request('/plans', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  async updatePlan(id: string | number, data: AdminPlanPayload) {
    return this.request(`/plans/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
  }

  async deletePlan(id: string | number) {
    return this.request(`/plans/${id}`, {
      method: 'DELETE',
    })
  }

  // ============================================================================
  // EMAIL
  // ============================================================================

  async sendBulkEmail(data: {
    tenant_ids?: number[]
    send_to_tenants?: boolean
    send_to_newsletter?: boolean
    subject: string
    message: string
  }) {
    return this.request('/email/send-bulk', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  async getNewsletterSubscribers() {
    return this.request('/email/newsletter-subscribers')
  }

  async getEmailHistory(limit = 50) {
    return this.request(`/email/history?limit=${limit}`)
  }
}

// Instância singleton
const adminApi = new AdminApiClient()

export default adminApi
export { AdminApiClient }

