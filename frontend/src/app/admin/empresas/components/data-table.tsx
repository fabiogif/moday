'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Search,
  Eye,
  CheckCircle2,
  Clock,
  XCircle,
  AlertCircle,
  RefreshCw,
  ArrowRightLeft,
} from 'lucide-react'
import { ChangeTenantPlanDialog } from './change-tenant-plan-dialog'

interface Tenant {
  id: number
  name: string
  subdomain: string
  account_status: string
  plan_id: number | null
  subscription_plan: string
  is_blocked: boolean
  mrr: number
  last_login_at: string | null
  created_at: string
}

interface DataTableProps {
  data: Tenant[]
  onRefresh: () => void
  isLoading: boolean
  canManage?: boolean
}

export function DataTable({ data, onRefresh, isLoading, canManage = false }: DataTableProps) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [planDialogTenant, setPlanDialogTenant] = useState<Tenant | null>(null)

  // Filtrar dados localmente
  const filteredData = data.filter((tenant) => {
    const matchesSearch =
      tenant.name.toLowerCase().includes(search.toLowerCase()) ||
      tenant.subdomain.toLowerCase().includes(search.toLowerCase())

    const matchesStatus =
      statusFilter === 'all' || tenant.account_status === statusFilter

    return matchesSearch && matchesStatus
  })

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: any; icon: any; label: string }> = {
      active: {
        variant: 'default',
        icon: CheckCircle2,
        label: 'Ativa',
      },
      trial: {
        variant: 'secondary',
        icon: Clock,
        label: 'Trial',
      },
      expired: {
        variant: 'destructive',
        icon: XCircle,
        label: 'Expirada',
      },
      suspended: {
        variant: 'outline',
        icon: AlertCircle,
        label: 'Suspensa',
      },
    }

    const config = variants[status] || variants.expired
    const Icon = config.icon

    return (
      <Badge variant={config.variant} className="gap-1">
        <Icon className="h-3 w-3" />
        {config.label}
      </Badge>
    )
  }

  const formatDate = (date: string | null) => {
    if (!date) return 'Nunca'
    const now = new Date()
    const dateObj = new Date(date)
    const diffMs = now.getTime() - dateObj.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 60) return `${diffMins} min atrás`
    if (diffHours < 24) return `${diffHours}h atrás`
    if (diffDays < 7) return `${diffDays}d atrás`
    
    return dateObj.toLocaleDateString('pt-BR')
  }

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value)
  }

  return (
    <Card>
      <CardHeader>
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <CardTitle>Lista de Empresas</CardTitle>
          <Button
            variant="outline"
            size="sm"
            onClick={onRefresh}
            disabled={isLoading}
          >
            <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
            Atualizar
          </Button>
        </div>

        {/* Filtros */}
        <div className="grid gap-4 md:grid-cols-3 mt-4">
          <div className="relative col-span-2">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder="Buscar por nome ou subdomínio..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9"
            />
          </div>

          <Select
            value={statusFilter}
            onValueChange={(value) => setStatusFilter(value)}
          >
            <SelectTrigger>
              <SelectValue placeholder="Filtrar por status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Todos os status</SelectItem>
              <SelectItem value="active">Ativas</SelectItem>
              <SelectItem value="trial">Trial</SelectItem>
              <SelectItem value="expired">Expiradas</SelectItem>
              <SelectItem value="suspended">Suspensas</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardHeader>

      <CardContent className="px-6">
        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Empresa</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Plano</TableHead>
                <TableHead>MRR</TableHead>
                <TableHead>Último Acesso</TableHead>
                <TableHead className="text-right">Ações</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredData.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                    Nenhuma empresa encontrada
                  </TableCell>
                </TableRow>
              ) : (
                filteredData.map((tenant) => (
                  <TableRow key={tenant.id}>
                    <TableCell>
                      <div>
                        <div className="font-medium">{tenant.name}</div>
                        <div className="text-sm text-muted-foreground">
                          {tenant.subdomain}.albtec.app
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1">
                        {getStatusBadge(tenant.account_status)}
                        {tenant.is_blocked && (
                          <Badge variant="destructive" className="ml-1">
                            Bloqueado
                          </Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <span className="capitalize">{tenant.subscription_plan || 'Trial'}</span>
                    </TableCell>
                    <TableCell>{formatCurrency(tenant.mrr)}</TableCell>
                    <TableCell>
                      <span className="text-sm">
                        {formatDate(tenant.last_login_at)}
                      </span>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-1">
                        {canManage && (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setPlanDialogTenant(tenant)}
                          >
                            <ArrowRightLeft className="h-4 w-4 mr-2" />
                            Plano
                          </Button>
                        )}
                        <Link href={`/admin/empresas/${tenant.id}`}>
                          <Button variant="ghost" size="sm">
                            <Eye className="h-4 w-4 mr-2" />
                            Detalhes
                          </Button>
                        </Link>
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>
      </CardContent>

      {canManage && planDialogTenant && (
        <ChangeTenantPlanDialog
          open={Boolean(planDialogTenant)}
          onOpenChange={(open) => {
            if (!open) setPlanDialogTenant(null)
          }}
          tenant={{
            id: planDialogTenant.id,
            name: planDialogTenant.name,
            plan_id: planDialogTenant.plan_id,
            subscription_plan: planDialogTenant.subscription_plan,
          }}
          onSuccess={() => {
            setPlanDialogTenant(null)
            onRefresh()
          }}
        />
      )}
    </Card>
  )
}

