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
  useAccountsPayable, 
  useAccountPayableStats, 
  useAccountPayableAlerts,
  useAccountPayableMutation,
  AccountPayable 
} from '@/hooks/use-accounts-payable'
import { useFinancialCategories } from '@/hooks/use-financial-categories'
import { useSuppliers } from '@/hooks/use-suppliers'
import { AccountPayableFormDialog } from './components/account-payable-form-dialog'
import { UsersPagination } from '../../users/components/users-pagination'
import { 
  Plus, Search, X, Edit, Trash2, Loader2, CreditCard, 
  FileText, Calendar, DollarSign, AlertCircle, CheckCircle 
} from 'lucide-react'
import { format, parseISO } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { toast } from 'sonner'
import { endpoints } from '@/lib/api-client'
import { cn } from '@/lib/utils'

export default function AccountsPayablePage() {
  const [searchQuery, setSearchQuery] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [formDialogOpen, setFormDialogOpen] = useState(false)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [selectedAccount, setSelectedAccount] = useState<AccountPayable | null>(null)
  const [accountToDelete, setAccountToDelete] = useState<AccountPayable | null>(null)
  const [currentPage, setCurrentPage] = useState(1)

  const { data: accounts, loading, refetch, pagination } = useAccountsPayable(
    statusFilter !== 'all' ? { status: statusFilter } : undefined,
    currentPage
  )
  const { data: stats } = useAccountPayableStats()
  const { data: alerts } = useAccountPayableAlerts(7)
  const { data: categories } = useFinancialCategories('despesa')
  const { data: suppliers } = useSuppliers()
  const { mutate, loading: mutating } = useAccountPayableMutation()

  useEffect(() => {
    setCurrentPage(1)
  }, [statusFilter])

  // Filtrar contas
  const filteredAccounts = (accounts || []).filter(account => {
    const matchesSearch = account.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
      account.supplier?.name.toLowerCase().includes(searchQuery.toLowerCase())
    
    const matchesStatus = statusFilter === 'all' || account.status === statusFilter

    return matchesSearch && matchesStatus
  })

  const handleCreate = () => {
    setSelectedAccount(null)
    setFormDialogOpen(true)
  }

  const handleEdit = (account: AccountPayable) => {
    setSelectedAccount(account)
    setFormDialogOpen(true)
  }

  const handleDeleteClick = (account: AccountPayable) => {
    setAccountToDelete(account)
    setDeleteDialogOpen(true)
  }

  const handleConfirmDelete = async () => {
    if (!accountToDelete) return

    try {
      await mutate(endpoints.accountsPayable.delete(accountToDelete.uuid), 'DELETE')
      toast.success('Conta a pagar excluída com sucesso!')
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
        await mutate(endpoints.accountsPayable.update(selectedAccount.uuid), 'PUT', data)
        toast.success('Conta a pagar atualizada com sucesso!')
      } else {
        await mutate(endpoints.accountsPayable.create, 'POST', data)
        toast.success('Conta a pagar criada com sucesso!')
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
      pago: { variant: 'default', color: 'text-green-600' },
      parcial: { variant: 'outline', color: 'text-blue-600' },
      vencido: { variant: 'destructive', color: 'text-red-600' },
      cancelado: { variant: 'outline', color: 'text-gray-600' },
    }

    const config = variants[status] || variants.pendente

    return (
      <Badge variant={config.variant} className={cn('capitalize', config.color)}>
        {status === 'pendente' && 'Pendente'}
        {status === 'pago' && 'Pago'}
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
        <h1 className="text-3xl font-bold tracking-tight">Contas a Pagar</h1>
        <p className="text-muted-foreground mt-1">
          Gerencie suas contas a pagar e pagamentos
        </p>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-4">
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
            <CardTitle className="text-sm font-medium">Total Pago</CardTitle>
            <CheckCircle className="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              R$ {(stats?.total_paid || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
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

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Alertas (7 dias)</CardTitle>
            <Calendar className="h-4 w-4 text-orange-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {alerts?.length || 0}
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              contas a vencer
            </p>
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
                  placeholder="Buscar por descrição ou fornecedor..."
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
                  <SelectItem value="pago">Pago</SelectItem>
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
                  : 'Cadastre sua primeira conta a pagar'}
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
                  <TableHead>Fornecedor</TableHead>
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
                      {account.description}
                      {account.installment_number && account.total_installments && (
                        <span className="text-xs text-muted-foreground ml-2">
                          ({account.installment_number}/{account.total_installments})
                        </span>
                      )}
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
                      {account.supplier?.name || '-'}
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
                        {account.amount_paid > 0 && (
                          <span className="text-xs text-green-600">
                            Pago: R$ {account.amount_paid.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
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
      <AccountPayableFormDialog
        open={formDialogOpen}
        onOpenChange={setFormDialogOpen}
        account={selectedAccount}
        onSubmit={handleSubmit}
        categories={categories || []}
        suppliers={suppliers || []}
      />

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir Conta a Pagar</AlertDialogTitle>
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

