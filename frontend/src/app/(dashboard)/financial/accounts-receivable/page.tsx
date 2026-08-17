'use client'

import { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
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
import { 
  useAccountsReceivable, 
  useAccountReceivableStats,
  useAccountReceivableMutation,
  AccountReceivable 
} from '@/hooks/use-accounts-receivable'
import { useFinancialCategories } from '@/hooks/use-financial-categories'
import { AccountReceivableFormDialog } from './components/account-receivable-form-dialog'
import { UsersPagination } from '../../users/components/users-pagination'
import { 
  Plus, Search, X, Edit, Trash2, Loader2, TrendingUp, 
  FileText, Calendar, DollarSign, AlertCircle, CheckCircle, ShoppingBag 
} from 'lucide-react'
import { format, parseISO } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { toast } from 'sonner'
import { endpoints } from '@/lib/api-client'
import { cn } from '@/lib/utils'

export default function AccountsReceivablePage() {
  const [searchQuery, setSearchQuery] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [formDialogOpen, setFormDialogOpen] = useState(false)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [selectedAccount, setSelectedAccount] = useState<AccountReceivable | null>(null)
  const [accountToDelete, setAccountToDelete] = useState<AccountReceivable | null>(null)
  const [currentPage, setCurrentPage] = useState(1)

  const { data: accounts, loading, refetch, pagination } = useAccountsReceivable(
    statusFilter !== 'all' ? { status: statusFilter } : undefined,
    currentPage
  )
  const { data: stats } = useAccountReceivableStats()
  const { data: categories } = useFinancialCategories('receita')
  const { mutate, loading: mutating } = useAccountReceivableMutation()

  useEffect(() => {
    setCurrentPage(1)
  }, [statusFilter])

  // Filtrar contas
  const filteredAccounts = (accounts || []).filter(account => {
    const matchesSearch = account.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
      account.client?.name.toLowerCase().includes(searchQuery.toLowerCase())
    
    const matchesStatus = statusFilter === 'all' || account.status === statusFilter

    return matchesSearch && matchesStatus
  })

  const handleCreate = () => {
    setSelectedAccount(null)
    setFormDialogOpen(true)
  }

  const handleEdit = (account: AccountReceivable) => {
    setSelectedAccount(account)
    setFormDialogOpen(true)
  }

  const handleDeleteClick = (account: AccountReceivable) => {
    setAccountToDelete(account)
    setDeleteDialogOpen(true)
  }

  const handleConfirmDelete = async () => {
    if (!accountToDelete) return

    try {
      await mutate(endpoints.accountsReceivable.delete(accountToDelete.uuid), 'DELETE')
      toast.success('Conta a receber excluída com sucesso!')
      setDeleteDialogOpen(false)
      setAccountToDelete(null)
      refetch()
    } catch (error: any) {
      toast.error(error.message || 'Erro ao excluir conta')
    }
  }

  const handleSubmit = async (data: any) => {
    try {
      if (selectedAccount) {
        await mutate(endpoints.accountsReceivable.update(selectedAccount.uuid), 'PUT', data)
        toast.success('Conta a receber atualizada com sucesso!')
      } else {
        await mutate(endpoints.accountsReceivable.create, 'POST', data)
        toast.success('Conta a receber criada com sucesso!')
      }
      setFormDialogOpen(false)
      setSelectedAccount(null)
      refetch()
    } catch (error: any) {
      toast.error(error.message || 'Erro ao salvar conta')
    }
  }

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: any; color: string }> = {
      pendente: { variant: 'secondary', color: 'text-yellow-600' },
      recebido: { variant: 'default', color: 'text-green-600' },
      parcial: { variant: 'outline', color: 'text-blue-600' },
      vencido: { variant: 'destructive', color: 'text-red-600' },
      cancelado: { variant: 'outline', color: 'text-gray-600' },
    }

    const config = variants[status] || variants.pendente

    return (
      <Badge variant={config.variant} className={cn('capitalize', config.color)}>
        {status === 'pendente' && 'Pendente'}
        {status === 'recebido' && 'Recebido'}
        {status === 'parcial' && 'Parcial'}
        {status === 'vencido' && 'Vencido'}
        {status === 'cancelado' && 'Cancelado'}
      </Badge>
    )
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    )
  }

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Contas a Receber</h1>
        <p className="text-muted-foreground mt-1">
          Gerencie suas contas a receber e recebimentos
        </p>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Pendente</CardTitle>
            <DollarSign className="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              R$ {(stats?.total_pending || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Recebido</CardTitle>
            <CheckCircle className="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              R$ {(stats?.total_received || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Vencido</CardTitle>
            <AlertCircle className="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              R$ {(stats?.total_overdue || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filtros e Ações */}
      <Card>
        <CardHeader>
          <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div className="flex-1 flex gap-4">
              <div className="relative flex-1 max-w-md">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Buscar por descrição ou cliente..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-9 pr-9"
                />
                {searchQuery && (
                  <Button
                    variant="ghost"
                    size="sm"
                    className="absolute right-1 top-1/2 h-7 w-7 -translate-y-1/2 p-0"
                    onClick={() => setSearchQuery('')}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                )}
              </div>

              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Filtrar por status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="pendente">Pendente</SelectItem>
                  <SelectItem value="recebido">Recebido</SelectItem>
                  <SelectItem value="parcial">Parcial</SelectItem>
                  <SelectItem value="vencido">Vencido</SelectItem>
                  <SelectItem value="cancelado">Cancelado</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button onClick={handleCreate}>
              <Plus className="mr-2 h-4 w-4" />
              Nova Conta
            </Button>
          </div>
        </CardHeader>

        <CardContent>
          {filteredAccounts.length === 0 ? (
            <div className="text-center py-12">
              <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
              <h3 className="mt-4 text-lg font-semibold">Nenhuma conta encontrada</h3>
              <p className="text-sm text-muted-foreground mt-2">
                {searchQuery || statusFilter !== 'all'
                  ? 'Tente ajustar os filtros'
                  : 'Cadastre sua primeira conta a receber'}
              </p>
              {!searchQuery && statusFilter === 'all' && (
                <Button onClick={handleCreate} className="mt-4">
                  <Plus className="mr-2 h-4 w-4" />
                  Nova Conta
                </Button>
              )}
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Categoria</TableHead>
                  <TableHead>Cliente</TableHead>
                  <TableHead>Vencimento</TableHead>
                  <TableHead className="text-right">Valor</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredAccounts.map((account) => (
                  <TableRow key={account.uuid}>
                    <TableCell className="font-medium">
                      <div className="flex flex-col">
                        <span>{account.description}</span>
                        {account.order_id && (
                          <span className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                            <ShoppingBag className="h-3 w-3" />
                            Pedido #{account.order_id}
                          </span>
                        )}
                        {account.installment_number && account.total_installments && (
                          <span className="text-xs text-muted-foreground">
                            Parcela {account.installment_number}/{account.total_installments}
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      {account.category ? (
                        <Badge variant="outline" style={{ borderColor: account.category.color }}>
                          {account.category.name}
                        </Badge>
                      ) : (
                        <span className="text-xs text-muted-foreground">-</span>
                      )}
                    </TableCell>
                    <TableCell>
                      {account.client?.name || '-'}
                    </TableCell>
                    <TableCell>
                      <div className="flex flex-col">
                        <span>{account.due_date_formatted}</span>
                        {account.is_overdue && (
                          <span className="text-xs text-red-600">
                            Vencido há {Math.abs(account.days_until_due)} dias
                          </span>
                        )}
                        {!account.is_overdue && account.days_until_due <= 7 && account.status === 'pendente' && (
                          <span className="text-xs text-orange-600">
                            Vence em {account.days_until_due} dias
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex flex-col">
                        <span className="font-semibold">{account.total_amount_formatted}</span>
                        {account.amount_received > 0 && (
                          <span className="text-xs text-green-600">
                            Recebido: R$ {account.amount_received.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      {getStatusBadge(account.status)}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEdit(account)}
                          title="Editar"
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleDeleteClick(account)}
                          title="Excluir"
                        >
                          <Trash2 className="h-4 w-4 text-red-600" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
          {pagination && (
            <div className="mt-4">
              <UsersPagination
                onPageChange={setCurrentPage}
                currentPage={pagination.current_page}
                totalPages={pagination.last_page}
                totalItems={pagination.total}
                itemsPerPage={pagination.per_page}
              />
            </div>
          )}
        </CardContent>
      </Card>

      {/* Form Dialog */}
      <AccountReceivableFormDialog
        open={formDialogOpen}
        onOpenChange={setFormDialogOpen}
        account={selectedAccount}
        onSubmit={handleSubmit}
        categories={categories || []}
      />

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir Conta a Receber</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir a conta "{accountToDelete?.description}"?
              Esta ação não pode ser desfeita.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleConfirmDelete}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}

