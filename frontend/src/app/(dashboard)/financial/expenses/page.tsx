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
import { useExpenses, useExpenseStats, useExpenseMutation, Expense } from '@/hooks/use-expenses'
import { useFinancialCategories } from '@/hooks/use-financial-categories'
import { useSuppliers } from '@/hooks/use-suppliers'
import { ExpenseFormDialog } from './components/expense-form-dialog'
import { UsersPagination } from '../../users/components/users-pagination'
import { 
  Plus, Search, X, Edit, Trash2, Loader2, TrendingDown, 
  FileText, Calendar, DollarSign, AlertCircle, Paperclip 
} from 'lucide-react'
import { format, parseISO } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { toast } from 'sonner'
import { endpoints } from '@/lib/api-client'
import { cn } from '@/lib/utils'

export default function ExpensesPage() {
  const [searchQuery, setSearchQuery] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [formDialogOpen, setFormDialogOpen] = useState(false)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [selectedExpense, setSelectedExpense] = useState<Expense | null>(null)
  const [expenseToDelete, setExpenseToDelete] = useState<Expense | null>(null)
  const [currentPage, setCurrentPage] = useState(1)

  const { data: expenses, loading, refetch, pagination } = useExpenses(
    statusFilter !== 'all' ? { status: statusFilter } : undefined,
    currentPage
  )
  const { data: stats } = useExpenseStats()
  const { data: categories } = useFinancialCategories('despesa')
  const { data: suppliers } = useSuppliers()
  const { mutate, loading: mutating } = useExpenseMutation()

  useEffect(() => {
    setCurrentPage(1)
  }, [statusFilter])

  // Filtrar despesas
  const filteredExpenses = (expenses || []).filter(expense => {
    const matchesSearch = expense.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
      expense.supplier?.name.toLowerCase().includes(searchQuery.toLowerCase())
    
    const matchesStatus = statusFilter === 'all' || expense.status === statusFilter

    return matchesSearch && matchesStatus
  })

  const handleCreate = () => {
    setSelectedExpense(null)
    setFormDialogOpen(true)
  }

  const handleEdit = (expense: Expense) => {
    setSelectedExpense(expense)
    setFormDialogOpen(true)
  }

  const handleDeleteClick = (expense: Expense) => {
    setExpenseToDelete(expense)
    setDeleteDialogOpen(true)
  }

  const handleConfirmDelete = async () => {
    if (!expenseToDelete) return

    try {
      await mutate(endpoints.expenses.delete(expenseToDelete.uuid), 'DELETE')
      toast.success('Despesa excluída com sucesso!')
      setDeleteDialogOpen(false)
      setExpenseToDelete(null)
      refetch()
    } catch (error: any) {
      toast.error(error.message || 'Erro ao excluir despesa')
    }
  }

  const handleSubmit = async (data: any) => {
    try {
      if (selectedExpense) {
        await mutate(endpoints.expenses.update(selectedExpense.uuid), 'PUT', data)
        toast.success('Despesa atualizada com sucesso!')
      } else {
        await mutate(endpoints.expenses.create, 'POST', data)
        toast.success('Despesa criada com sucesso!')
      }
      
      setFormDialogOpen(false)
      await refetch()
      setSelectedExpense(null)
    } catch (error: any) {
      throw error
    }
  }

  const getStatusBadge = (status: string) => {
    const variants = {
      pendente: 'bg-yellow-100 text-yellow-800',
      pago: 'bg-green-100 text-green-800',
      cancelado: 'bg-gray-100 text-gray-800',
    }
    return variants[status as keyof typeof variants] || variants.pendente
  }

  return (
    <div className="flex flex-col gap-4">
      {/* Estatísticas */}
      {stats && (
        <div className="@container/main px-4 lg:px-6">
          <div className="grid gap-4 md:grid-cols-3">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total do Mês</CardTitle>
                <DollarSign className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  R$ {stats.total_month?.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) || '0,00'}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Pendentes</CardTitle>
                <AlertCircle className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.pending || 0}</div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Pagas no Mês</CardTitle>
                <TrendingDown className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.paid_month || 0}</div>
              </CardContent>
            </Card>
          </div>
        </div>
      )}

      {/* Header e Filtros */}
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
              <TrendingDown className="h-8 w-8" />
              Despesas
            </h1>
            <p className="text-muted-foreground">
              Gerencie todas as suas despesas
            </p>
          </div>
          <Button onClick={handleCreate} size="lg">
            <Plus className="mr-2 h-5 w-5" />
            Nova Despesa
          </Button>
        </div>

        {/* Filtros */}
        <Card>
          <CardContent className="pt-6">
            <div className="grid md:grid-cols-2 gap-4">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Buscar despesas..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10 pr-10"
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery('')}
                    className="absolute right-3 top-1/2 -translate-y-1/2"
                  >
                    <X className="h-4 w-4" />
                  </button>
                )}
              </div>

              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger>
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos os Status</SelectItem>
                  <SelectItem value="pendente">Pendente</SelectItem>
                  <SelectItem value="pago">Pago</SelectItem>
                  <SelectItem value="cancelado">Cancelado</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Tabela */}
      <div className="@container/main px-4 lg:px-6">
        <Card>
          <CardHeader>
            <CardTitle>Lista de Despesas</CardTitle>
            <CardDescription>
              {filteredExpenses.length} despesa(s) encontrada(s)
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex items-center justify-center h-64">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
              </div>
            ) : filteredExpenses.length === 0 ? (
              <div className="text-center py-12">
                <FileText className="h-12 w-12 mx-auto mb-4 text-muted-foreground opacity-50" />
                <p className="text-muted-foreground mb-4">
                  {searchQuery ? 'Nenhuma despesa encontrada' : 'Nenhuma despesa cadastrada'}
                </p>
                {!searchQuery && (
                  <Button onClick={handleCreate}>
                    <Plus className="mr-2 h-4 w-4" />
                    Cadastrar Primeira Despesa
                  </Button>
                )}
              </div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Descrição</TableHead>
                      <TableHead>Categoria</TableHead>
                      <TableHead>Fornecedor</TableHead>
                      <TableHead>Vencimento</TableHead>
                      <TableHead>Valor</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead className="text-right">Ações</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {filteredExpenses.map((expense) => (
                      <TableRow key={expense.id}>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <span className="font-medium">{expense.description}</span>
                            {expense.attachment_path && (
                              <Paperclip className="h-3 w-3 text-muted-foreground" />
                            )}
                          </div>
                          {expense.notes && (
                            <p className="text-xs text-muted-foreground mt-1">{expense.notes.substring(0, 50)}</p>
                          )}
                        </TableCell>
                        <TableCell>
                          {expense.category && (
                            <Badge variant="outline" style={{ borderColor: expense.category.color }}>
                              {expense.category.name}
                            </Badge>
                          )}
                        </TableCell>
                        <TableCell>
                          {expense.supplier ? (
                            <span className="text-sm">{expense.supplier.name}</span>
                          ) : (
                            <span className="text-xs text-muted-foreground">-</span>
                          )}
                        </TableCell>
                        <TableCell>
                          <div className="text-sm">
                            {expense.due_date_formatted}
                            {expense.is_overdue && expense.status === 'pendente' && (
                              <Badge variant="destructive" className="ml-2 text-xs">
                                Vencida
                              </Badge>
                            )}
                          </div>
                        </TableCell>
                        <TableCell className="font-semibold">
                          {expense.amount_formatted}
                        </TableCell>
                        <TableCell>
                          <Badge className={getStatusBadge(expense.status)}>
                            {expense.status_label}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                          <div className="flex justify-end gap-2">
                            <Button
                              variant="outline"
                              size="icon"
                              onClick={() => handleEdit(expense)}
                            >
                              <Edit className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="destructive"
                              size="icon"
                              onClick={() => handleDeleteClick(expense)}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
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
      </div>

      {/* Dialogs */}
      <ExpenseFormDialog
        open={formDialogOpen}
        onOpenChange={setFormDialogOpen}
        expense={selectedExpense}
        categories={categories || []}
        suppliers={suppliers || []}
        onSubmit={handleSubmit}
        isLoading={mutating}
      />

      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2">
              <Trash2 className="h-5 w-5 text-destructive" />
              Excluir Despesa
            </AlertDialogTitle>
            <AlertDialogDescription asChild>
              <div>
                <p>
                  Tem certeza que deseja excluir a despesa <strong>"{expenseToDelete?.description}"</strong>?
                </p>
                {expenseToDelete && (
                  <div className="mt-3 p-3 bg-muted rounded-md text-sm">
                    <p>Valor: {expenseToDelete.amount_formatted}</p>
                    <p>Vencimento: {expenseToDelete.due_date_formatted}</p>
                  </div>
                )}
                <p className="text-destructive font-medium mt-3">
                  Esta ação não pode ser desfeita.
                </p>
              </div>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={mutating}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleConfirmDelete}
              disabled={mutating}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {mutating ? 'Excluindo...' : 'Excluir Despesa'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}

