'use client'

import { useEffect, useState } from 'react'
import { useRouter, useParams } from 'next/navigation'
import { useAdminAuth } from '@/contexts/admin-auth-context'
import adminApi from '@/lib/admin-api-client'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { PageLoading } from '@/components/ui/loading-progress'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Separator } from '@/components/ui/separator'
import { toast } from 'sonner'
import {
  ArrowLeft,
  Building2,
  Calendar,
  Users,
  MessageSquare,
  DollarSign,
  CheckCircle2,
  XCircle,
  Ban,
  PlayCircle,
  Pause,
  Edit,
  Save,
  Trash2,
  ShieldAlert,
  AlertTriangle,
  ArrowRightLeft,
} from 'lucide-react'
import { ChangeTenantPlanDialog } from '../components/change-tenant-plan-dialog'
import { OwnerAccessCard, TenantOwner } from '../components/owner-access-card'

interface TenantDetails {
  tenant: {
    id: number
    name: string
    subdomain: string
    account_status: string
    plan_id: number | null
    subscription_plan: string
    is_blocked: boolean
    blocked_at: string | null
    blocked_reason: string | null
    admin_notes: string | null
    mrr: number
    users_limit: number
    messages_limit: number
    total_logins: number
    last_login_at: string | null
    trial_started_at: string | null
    trial_expires_at: string | null
    activated_at: string | null
    created_at: string
  }
  metrics: {
    total_orders: number
    total_revenue: number
    total_messages: number
    total_logins: number
    daily_data: Array<{
      date: string
      orders: number
      revenue: number
      messages: number
      logins: number
    }>
  }
  billing: Array<{
    id: number
    invoice_number: string
    amount: number
    status: string
    billing_date: string
    due_date: string
    paid_at: string | null
  }>
  recent_access: Array<{
    user: string
    logged_in_at: string
    session_duration: number | null
    ip_address: string | null
  }>
  action_history: Array<{
    action: string
    description: string
    admin: string
    created_at: string
  }>
  owner?: TenantOwner | null
}

export default function TenantDetailsPage() {
  const router = useRouter()
  const params = useParams()
  const { isAuthenticated, isLoading: authLoading, hasPermission } = useAdminAuth()
  const [data, setData] = useState<TenantDetails | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [isEditing, setIsEditing] = useState(false)
  const [isSaving, setIsSaving] = useState(false)
  const [planDialogOpen, setPlanDialogOpen] = useState(false)

  // Estados de edição
  const [editData, setEditData] = useState({
    users_limit: 0,
    messages_limit: 0,
    admin_notes: '',
  })

  // Estados de ações
  const [actionDialog, setActionDialog] = useState<{
    open: boolean
    action: string
    title: string
    description: string
    reason?: string
  }>({
    open: false,
    action: '',
    title: '',
    description: '',
    reason: '',
  })

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/admin/login')
    }
  }, [authLoading, isAuthenticated, router])

  useEffect(() => {
    if (isAuthenticated && params.id) {
      loadData()
    }
  }, [isAuthenticated, params.id])

  const loadData = async () => {
    try {
      setIsLoading(true)

      const response = await adminApi.getTenant(params.id as string)

      // :', response.data.billing?.length || 0)
      // :', response.data.recent_access?.length || 0)
      // :', response.data.action_history?.length || 0)
      
      setData(response.data)
      
      // Inicializar dados de edição
      setEditData({
        users_limit: response.data.tenant.users_limit,
        messages_limit: response.data.tenant.messages_limit,
        admin_notes: response.data.tenant.admin_notes || '',
      })
    } catch (error) {

      toast.error('Erro ao carregar detalhes da empresa')
    } finally {
      setIsLoading(false)
    }
  }

  const handleSave = async () => {
    try {
      setIsSaving(true)
      await adminApi.updateTenant(params.id as string, editData)
      toast.success('Empresa atualizada com sucesso!')
      setIsEditing(false)
      await loadData()
    } catch (error) {

      toast.error('Erro ao salvar alterações')
    } finally {
      setIsSaving(false)
    }
  }

  const handleAction = (action: string, title: string, description: string) => {
    setActionDialog({
      open: true,
      action,
      title,
      description,
      reason: '',
    })
  }

  const executeAction = async () => {
    try {
      const tenantId = params.id as string

      switch (actionDialog.action) {
        case 'activate':

          const activateResponse = await adminApi.activateTenant(tenantId)

          toast.success('Empresa ativada com sucesso!')
          break
        case 'suspend':
          if (!actionDialog.reason) {
            toast.error('Informe o motivo da suspensão')
            return
          }
          await adminApi.suspendTenant(tenantId, actionDialog.reason)
          toast.success('Empresa suspensa com sucesso!')
          break
        case 'block':
          if (!actionDialog.reason) {
            toast.error('Informe o motivo do bloqueio')
            return
          }
          await adminApi.blockTenant(tenantId, actionDialog.reason)
          toast.success('Empresa bloqueada com sucesso!')
          break
        case 'unblock':
          await adminApi.unblockTenant(tenantId)
          toast.success('Empresa desbloqueada com sucesso!')
          break
        case 'pause':
          if (!actionDialog.reason) {
            toast.error('Informe o motivo da pausa')
            return
          }

          const pauseResponse = await adminApi.pauseTenantAccess(tenantId, actionDialog.reason)

          toast.success('Acesso pausado com sucesso! A empresa não poderá fazer login.')
          break
        case 'restore':
          await adminApi.restoreTenantAccess(tenantId)
          toast.success('Acesso restaurado com sucesso!')
          break
        case 'delete':
          await adminApi.deleteTenant(tenantId)
          toast.success('Empresa excluída com sucesso!')
          setTimeout(() => router.push('/admin/empresas'), 1500)
          break
      }

      setActionDialog({ open: false, action: '', title: '', description: '' })
      
      // Não recarregar se foi deletado (vai redirecionar)
      if (actionDialog.action !== 'delete') {
        await loadData()
      }
    } catch (error) {

      toast.error('Erro ao executar ação')
    }
  }

  if (authLoading || !isAuthenticated) {
    return null
  }

  if (isLoading) {
    return <PageLoading message="Carregando detalhes da empresa..." />
  }

  if (!data) {
    return (
      <div className="flex-1 space-y-6 px-6 pt-0">
        <p className="text-muted-foreground">Empresa não encontrada</p>
      </div>
    )
  }

  const { tenant } = data

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: any; icon: any; label: string }> = {
      active: { variant: 'default', icon: CheckCircle2, label: 'Ativa' },
      trial: { variant: 'secondary', icon: Calendar, label: 'Trial' },
      expired: { variant: 'destructive', icon: XCircle, label: 'Expirada' },
      suspended: { variant: 'outline', icon: Pause, label: 'Suspensa' },
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
    if (!date) return '-'
    return new Date(date).toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value)
  }

  const canManage = hasPermission('tenants.manage')

  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
      {/* Header */}
      <div className="flex md:flex-row flex-col md:items-center justify-between gap-4 md:gap-6">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" onClick={() => router.back()}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div className="flex flex-col gap-2">
            <h1 className="text-2xl font-bold tracking-tight">{tenant.name}</h1>
            <p className="text-muted-foreground">
              {tenant.subdomain}.albtec.app
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          {getStatusBadge(tenant.account_status)}
          {tenant.is_blocked && (
            <Badge variant="destructive" className="gap-1">
              <ShieldAlert className="h-3 w-3" />
              {tenant.blocked_reason ? 'Bloqueada' : 'Acesso Pausado'}
            </Badge>
          )}
        </div>
      </div>

      {/* Ações Rápidas */}
      {canManage && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Ações Administrativas</CardTitle>
            <CardDescription>
              Gerencie o status e acesso da empresa
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {/* Ações de Status */}
              <div>
                <Label className="text-sm font-medium mb-2 block">Gerenciamento de Status</Label>
                <div className="flex flex-wrap gap-2">
                  {/* Mostrar Ativar para trial, expired, suspended */}
                  {['trial', 'expired', 'suspended'].includes(tenant.account_status) && !tenant.is_blocked && (
                    <Button
                      onClick={() =>
                        handleAction('activate', 'Ativar Empresa', 'Tem certeza que deseja ativar esta empresa e iniciar cobrança?')
                      }
                    >
                      <PlayCircle className="h-4 w-4 mr-2" />
                      Ativar Empresa
                    </Button>
                  )}
                  
                  {/* Mostrar Suspender apenas para empresas ativas */}
                  {tenant.account_status === 'active' && !tenant.is_blocked && (
                    <Button
                      variant="outline"
                      onClick={() =>
                        handleAction(
                          'suspend',
                          'Suspender Empresa',
                          'Informe o motivo da suspensão:'
                        )
                      }
                    >
                      <Pause className="h-4 w-4 mr-2" />
                      Suspender
                    </Button>
                  )}

                  {/* Pausar Acesso - bloqueia login temporariamente */}
                  {!tenant.is_blocked && (
                    <Button
                      variant="outline"
                      onClick={() =>
                        handleAction(
                          'pause',
                          'Pausar Acesso',
                          'Pausar o acesso temporariamente (bloqueia login). Informe o motivo:'
                        )
                      }
                    >
                      <ShieldAlert className="h-4 w-4 mr-2" />
                      Pausar Acesso
                    </Button>
                  )}

                  {/* Bloquear - bloqueio mais severo */}
                  {!tenant.is_blocked && tenant.account_status === 'active' && (
                    <Button
                      variant="outline"
                      onClick={() =>
                        handleAction(
                          'block',
                          'Bloquear Empresa',
                          'Bloquear acesso por violação de termos ou inadimplência. Informe o motivo:'
                        )
                      }
                    >
                      <ShieldAlert className="h-4 w-4 mr-2" />
                      Bloquear
                    </Button>
                  )}

                  {/* Restaurar/Desbloquear quando bloqueado */}
                  {tenant.is_blocked && (
                    <>
                      <Button
                        variant="default"
                        onClick={() =>
                          handleAction('restore', 'Restaurar Acesso', 'Tem certeza que deseja restaurar o acesso desta empresa?')
                        }
                      >
                        <CheckCircle2 className="h-4 w-4 mr-2" />
                        Restaurar Acesso
                      </Button>
                      
                      <Button
                        variant="default"
                        onClick={() =>
                          handleAction('unblock', 'Desbloquear Empresa', 'Tem certeza que deseja desbloquear esta empresa?')
                        }
                      >
                        <CheckCircle2 className="h-4 w-4 mr-2" />
                        Desbloquear
                      </Button>
                    </>
                  )}
                </div>
              </div>

              {/* Ações Destrutivas */}
              <div>
                <Label className="text-sm font-medium mb-2 block text-red-600">Ações Destrutivas</Label>
                <div className="flex flex-wrap gap-2">
                  <Button
                    variant="destructive"
                    onClick={() =>
                      handleAction(
                        'delete',
                        'Excluir Empresa',
                        'ATENÇÃO: Esta ação excluirá a empresa e todos os seus dados. Tem certeza?'
                      )
                    }
                  >
                    <Trash2 className="h-4 w-4 mr-2" />
                    Excluir Empresa
                  </Button>
                </div>
                <p className="text-xs text-muted-foreground mt-2">
                  ⚠️ A exclusão remove todos os dados: pedidos, clientes, produtos, etc.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      <OwnerAccessCard
        tenantId={params.id as string}
        owner={data.owner ?? null}
        canManage={canManage}
        onUpdated={loadData}
      />

      {/* Informações Gerais */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Informações Gerais</CardTitle>
            {canManage && (
              <Button
                variant={isEditing ? 'default' : 'outline'}
                size="sm"
                onClick={() => (isEditing ? handleSave() : setIsEditing(true))}
                disabled={isSaving}
              >
                {isEditing ? (
                  <>
                    <Save className="h-4 w-4 mr-2" />
                    {isSaving ? 'Salvando...' : 'Salvar'}
                  </>
                ) : (
                  <>
                    <Edit className="h-4 w-4 mr-2" />
                    Editar
                  </>
                )}
              </Button>
            )}
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div>
              <Label className="text-muted-foreground">Nome</Label>
              <p className="font-medium">{tenant.name}</p>
            </div>
            <div>
              <Label className="text-muted-foreground">Subdomínio</Label>
              <p className="font-medium">{tenant.subdomain}.albtec.app</p>
            </div>
            <div>
              <Label className="text-muted-foreground">Status</Label>
              <div className="mt-1">{getStatusBadge(tenant.account_status)}</div>
            </div>
            <div>
              <Label className="text-muted-foreground">Plano</Label>
              <div className="mt-1 flex flex-wrap items-center gap-2">
                <p className="font-medium capitalize">{tenant.subscription_plan || 'Trial'}</p>
                {canManage && (
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setPlanDialogOpen(true)}
                  >
                    <ArrowRightLeft className="h-4 w-4 mr-2" />
                    Alterar plano
                  </Button>
                )}
              </div>
            </div>
            <div>
              <Label className="text-muted-foreground">MRR</Label>
              <p className="font-medium text-green-600">{formatCurrency(tenant.mrr)}</p>
            </div>
            <div>
              <Label className="text-muted-foreground">Data de Cadastro</Label>
              <p className="font-medium">{formatDate(tenant.created_at)}</p>
            </div>
            {tenant.trial_started_at && (
              <div>
                <Label className="text-muted-foreground">Trial Iniciado</Label>
                <p className="font-medium">{formatDate(tenant.trial_started_at)}</p>
              </div>
            )}
            {tenant.trial_expires_at && (
              <div>
                <Label className="text-muted-foreground">Trial Expira</Label>
                <p className="font-medium">{formatDate(tenant.trial_expires_at)}</p>
              </div>
            )}
            {tenant.activated_at && (
              <div>
                <Label className="text-muted-foreground">Ativado em</Label>
                <p className="font-medium">{formatDate(tenant.activated_at)}</p>
              </div>
            )}
            <div>
              <Label className="text-muted-foreground">Último Acesso</Label>
              <p className="font-medium">{formatDate(tenant.last_login_at)}</p>
            </div>
          </div>

          <Separator />

          {/* Limites e Configurações */}
          <div className="space-y-4">
            <h3 className="font-semibold">Limites e Configurações</h3>
            
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="users_limit">Limite de Usuários</Label>
                <Input
                  id="users_limit"
                  type="number"
                  value={editData.users_limit}
                  onChange={(e) => setEditData({ ...editData, users_limit: parseInt(e.target.value) })}
                  disabled={!isEditing}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="messages_limit">Limite de Mensagens/Mês</Label>
                <Input
                  id="messages_limit"
                  type="number"
                  value={editData.messages_limit}
                  onChange={(e) => setEditData({ ...editData, messages_limit: parseInt(e.target.value) })}
                  disabled={!isEditing}
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="admin_notes">Notas Administrativas</Label>
              <Textarea
                id="admin_notes"
                value={editData.admin_notes}
                onChange={(e) => setEditData({ ...editData, admin_notes: e.target.value })}
                disabled={!isEditing}
                rows={4}
                placeholder="Adicione notas sobre esta empresa..."
              />
            </div>

            {tenant.is_blocked && tenant.blocked_reason && (
              <div className="bg-red-50 dark:bg-red-950/50 p-4 rounded-lg border border-red-200 dark:border-red-900">
                <p className="text-sm font-medium text-red-900 dark:text-red-100">
                  Motivo do Bloqueio:
                </p>
                <p className="text-sm text-red-700 dark:text-red-300 mt-1">
                  {tenant.blocked_reason}
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Métricas de Uso (últimos 30 dias) */}
      <Card>
        <CardHeader>
          <CardTitle>Métricas de Uso (últimos 30 dias)</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-4">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-primary/10 rounded-lg">
                <Building2 className="h-5 w-5 text-primary" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Pedidos</p>
                <p className="text-2xl font-bold">{data.metrics.total_orders}</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <div className="p-2 bg-green-500/10 rounded-lg">
                <DollarSign className="h-5 w-5 text-green-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Receita</p>
                <p className="text-2xl font-bold">{formatCurrency(data.metrics.total_revenue)}</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <div className="p-2 bg-blue-500/10 rounded-lg">
                <MessageSquare className="h-5 w-5 text-blue-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Mensagens</p>
                <p className="text-2xl font-bold">{data.metrics.total_messages}</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <div className="p-2 bg-purple-500/10 rounded-lg">
                <Users className="h-5 w-5 text-purple-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Logins</p>
                <p className="text-2xl font-bold">{data.metrics.total_logins}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Histórico de Faturamento */}
      <Card>
        <CardHeader>
          <CardTitle>Histórico de Faturamento</CardTitle>
          <CardDescription>Últimas faturas e pagamentos</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nº Fatura</TableHead>
                  <TableHead>Valor</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Vencimento</TableHead>
                  <TableHead>Pagamento</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.billing.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                      Nenhuma fatura registrada
                    </TableCell>
                  </TableRow>
                ) : (
                  data.billing.map((bill) => (
                    <TableRow key={bill.id}>
                      <TableCell className="font-mono text-sm">{bill.invoice_number}</TableCell>
                      <TableCell className="font-medium">{formatCurrency(bill.amount)}</TableCell>
                      <TableCell>
                        <Badge variant={bill.status === 'paid' ? 'default' : bill.status === 'overdue' ? 'destructive' : 'secondary'}>
                          {bill.status === 'paid' ? 'Pago' : bill.status === 'overdue' ? 'Vencido' : 'Pendente'}
                        </Badge>
                      </TableCell>
                      <TableCell>{new Date(bill.due_date).toLocaleDateString('pt-BR')}</TableCell>
                      <TableCell>{bill.paid_at ? new Date(bill.paid_at).toLocaleDateString('pt-BR') : '-'}</TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Acessos Recentes */}
      <Card>
        <CardHeader>
          <CardTitle>Acessos Recentes</CardTitle>
          <CardDescription>Últimos 10 logins realizados</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Usuário</TableHead>
                  <TableHead>Data/Hora</TableHead>
                  <TableHead>Duração</TableHead>
                  <TableHead>IP</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.recent_access.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                      Nenhum acesso registrado
                    </TableCell>
                  </TableRow>
                ) : (
                  data.recent_access.map((access, index) => (
                    <TableRow key={index}>
                      <TableCell className="font-medium">{access.user || 'N/A'}</TableCell>
                      <TableCell>{formatDate(access.logged_in_at)}</TableCell>
                      <TableCell>
                        {access.session_duration ? `${access.session_duration} min` : 'Em andamento'}
                      </TableCell>
                      <TableCell className="font-mono text-xs">{access.ip_address || '-'}</TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Histórico de Ações */}
      <Card>
        <CardHeader>
          <CardTitle>Histórico de Ações Administrativas</CardTitle>
          <CardDescription>Últimas 20 ações realizadas por administradores</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Ação</TableHead>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Admin</TableHead>
                  <TableHead>Data</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.action_history.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                      Nenhuma ação registrada
                    </TableCell>
                  </TableRow>
                ) : (
                  data.action_history.map((log, index) => (
                    <TableRow key={index}>
                      <TableCell>
                        <Badge variant="outline">{log.action}</Badge>
                      </TableCell>
                      <TableCell className="max-w-md truncate">{log.description}</TableCell>
                      <TableCell>{log.admin || 'N/A'}</TableCell>
                      <TableCell className="text-sm">{formatDate(log.created_at)}</TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Dialog de Confirmação de Ações */}
      <AlertDialog open={actionDialog.open} onOpenChange={(open) => setActionDialog({ ...actionDialog, open })}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{actionDialog.title}</AlertDialogTitle>
            <AlertDialogDescription>{actionDialog.description}</AlertDialogDescription>
          </AlertDialogHeader>

          {(actionDialog.action === 'suspend' || 
            actionDialog.action === 'block' || 
            actionDialog.action === 'pause') && (
            <div className="space-y-2">
              <Label htmlFor="reason">Motivo</Label>
              <Textarea
                id="reason"
                value={actionDialog.reason}
                onChange={(e) => setActionDialog({ ...actionDialog, reason: e.target.value })}
                placeholder="Informe o motivo..."
                rows={3}
                required
              />
            </div>
          )}

          {actionDialog.action === 'delete' && (
            <Alert className="border-red-200 bg-red-50 dark:bg-red-950/50">
              <AlertTriangle className="h-4 w-4 text-red-600" />
              <AlertDescription className="text-red-800 dark:text-red-200">
                <p className="font-semibold mb-2">Esta ação é IRREVERSÍVEL!</p>
                <p className="text-sm">
                  Todos os dados serão excluídos permanentemente:
                </p>
                <ul className="text-sm mt-2 ml-4 list-disc">
                  <li>Pedidos e histórico de vendas</li>
                  <li>Clientes cadastrados</li>
                  <li>Produtos e categorias</li>
                  <li>Configurações e customizações</li>
                </ul>
              </AlertDescription>
            </Alert>
          )}

          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction 
              onClick={executeAction}
              className={actionDialog.action === 'delete' ? 'bg-red-600 hover:bg-red-700' : ''}
            >
              {actionDialog.action === 'delete' ? 'Sim, Excluir Permanentemente' : 'Confirmar'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {canManage && (
        <ChangeTenantPlanDialog
          open={planDialogOpen}
          onOpenChange={setPlanDialogOpen}
          tenant={{
            id: tenant.id,
            name: tenant.name,
            plan_id: tenant.plan_id,
            subscription_plan: tenant.subscription_plan,
          }}
          onSuccess={loadData}
        />
      )}
    </div>
  )
}

