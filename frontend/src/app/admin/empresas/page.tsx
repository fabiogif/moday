'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useAdminAuth } from '@/contexts/admin-auth-context'
import adminApi from '@/lib/admin-api-client'
import { StatCards } from './components/stat-cards'
import { DataTable } from './components/data-table'
import { PageLoading } from '@/components/ui/loading-progress'

interface Tenant {
  id: number
  name: string
  subdomain: string
  account_status: string
  plan_id: number | null
  subscription_plan: string
  is_blocked: boolean
  mrr: number
  users_limit: number
  messages_limit: number
  last_login_at: string | null
  created_at: string
  trial_expires_at: string | null
}

export default function AdminEmpresasPage() {
  const router = useRouter()
  const { isAuthenticated, isLoading: authLoading, hasPermission } = useAdminAuth()
  const [tenants, setTenants] = useState<Tenant[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [stats, setStats] = useState({
    total: 0,
    active: 0,
    trial: 0,
    expired: 0,
  })

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/admin/login')
    }
  }, [authLoading, isAuthenticated, router])

  useEffect(() => {
    if (isAuthenticated) {
      loadData()
    }
  }, [isAuthenticated])

  const loadData = async () => {
    try {
      setIsLoading(true)

      // Carregar empresas e stats em paralelo
      const [tenantsResponse, dashboardResponse] = await Promise.all([
        adminApi.getTenants({ per_page: 100 }),
        adminApi.getDashboardStats(),
      ])

      setTenants(tenantsResponse.data)
      setStats(dashboardResponse.data.tenants)
    } catch (error) {

    } finally {
      setIsLoading(false)
    }
  }

  const handleRefresh = () => {
    loadData()
  }

  if (authLoading || !isAuthenticated) {
    return null
  }

  if (isLoading) {
    return <PageLoading />
  }

  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
      {/* Header */}
      <div className="flex md:flex-row flex-col md:items-center justify-between gap-4 md:gap-6">
        <div className="flex flex-col gap-2">
          <h1 className="text-2xl font-bold tracking-tight">Empresas</h1>
          <p className="text-muted-foreground">
            Gerencie todas as empresas cadastradas no sistema
          </p>
        </div>
      </div>

      {/* Stat Cards */}
      <div className="@container/main">
        <StatCards stats={stats} />
      </div>

      {/* Data Table */}
      <div className="@container/main">
        <DataTable 
          data={tenants} 
          onRefresh={handleRefresh}
          isLoading={isLoading}
          canManage={hasPermission('tenants.manage')}
        />
      </div>
    </div>
  )
}
