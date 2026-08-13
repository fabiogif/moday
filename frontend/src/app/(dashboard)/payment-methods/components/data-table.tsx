"use client"

import { useState } from "react"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Plus, Edit, Trash2, MoreHorizontal } from "lucide-react"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { PaymentMethodFormDialog } from "./payment-method-form-dialog"

interface PaymentMethod {
  id?: number
  uuid: string
  name: string
  description?: string
  is_active: boolean
  tenant_id?: number
  created_at: string
  updated_at: string
}

interface PaymentMethodFormValues {
  name: string
  description?: string
  is_active?: boolean
}

interface DataTableProps {
  paymentMethods: PaymentMethod[]
  onAdd: (data: PaymentMethodFormValues) => Promise<void>
  onEdit: (uuid: string, data: PaymentMethodFormValues) => Promise<void>
  onDelete: (uuid: string) => Promise<void>
  loading?: boolean
}

export function DataTable({ paymentMethods, onAdd, onEdit, onDelete, loading }: DataTableProps) {
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [methodToDelete, setMethodToDelete] = useState<PaymentMethod | null>(null)

  const handleDeleteClick = (method: PaymentMethod) => {
    setMethodToDelete(method)
    setDeleteDialogOpen(true)
  }

  const handleDeleteConfirm = async () => {
    if (methodToDelete) {
      await onDelete(methodToDelete.uuid)
      setDeleteDialogOpen(false)
      setMethodToDelete(null)
    }
  }

  const getStatusBadge = (method: PaymentMethod) => {
    if (!method.is_active) {
      return <Badge variant="destructive">Inativo</Badge>
    }
    return <Badge variant="default">Ativo</Badge>
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  }

  return (
    <>
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Formas de Pagamento</CardTitle>
              <CardDescription>
                Gerencie as formas de pagamento do seu estabelecimento
              </CardDescription>
            </div>
            <PaymentMethodFormDialog onSubmit={onAdd}>
              <Button>
                <Plus className="mr-2 h-4 w-4" />
                Nova Forma
              </Button>
            </PaymentMethodFormDialog>
          </div>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nome</TableHead>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Criado em</TableHead>
                  <TableHead className="w-[70px]">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {paymentMethods.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="h-24 text-center">
                      <div className="flex flex-col items-center justify-center space-y-2">
                        <p className="text-sm text-muted-foreground">
                          Nenhuma forma de pagamento cadastrada
                        </p>
                        <PaymentMethodFormDialog onSubmit={onAdd}>
                          <Button variant="outline" size="sm">
                            <Plus className="mr-2 h-4 w-4" />
                            Cadastrar primeira forma
                          </Button>
                        </PaymentMethodFormDialog>
                      </div>
                    </TableCell>
                  </TableRow>
                ) : (
                  paymentMethods.map((method) => (
                    <TableRow key={method.uuid}>
                      <TableCell className="font-medium">{method.name}</TableCell>
                      <TableCell className="max-w-[200px]">
                        <div className="truncate" title={method.description}>
                          {method.description || '-'}
                        </div>
                      </TableCell>
                      <TableCell>{getStatusBadge(method)}</TableCell>
                      <TableCell>{formatDate(method.created_at)}</TableCell>
                      <TableCell>
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                              <span className="sr-only">Abrir menu</span>
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <PaymentMethodFormDialog 
                              paymentMethod={method} 
                              onSubmit={(data) => onEdit(method.uuid, data)}
                            >
                              <DropdownMenuItem onSelect={(e) => e.preventDefault()}>
                                <Edit className="mr-2 h-4 w-4" />
                                Editar
                              </DropdownMenuItem>
                            </PaymentMethodFormDialog>
                            <DropdownMenuItem 
                              onClick={() => handleDeleteClick(method)}
                              className="text-red-600"
                            >
                              <Trash2 className="mr-2 h-4 w-4" />
                              Excluir
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir a forma de pagamento "{methodToDelete?.name}"?
              Esta ação não pode ser desfeita.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteConfirm}
              className="bg-red-600 hover:bg-red-700"
              disabled={loading}
            >
              {loading ? "Excluindo..." : "Excluir"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}