'use client'

import { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { useSuppliers, useSupplierMutation, Supplier } from '@/hooks/use-suppliers'
import { SupplierFormDialog } from './components/supplier-form-dialog'
import { Plus, Search, X, Edit, Trash2, Eye, Loader2, Truck, Phone, Mail } from 'lucide-react'
import { toast } from 'sonner'
import { endpoints } from '@/lib/api-client'

export default function SuppliersPage() {
  const [searchQuery, setSearchQuery] = useState('')
  const [formDialogOpen, setFormDialogOpen] = useState(false)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [selectedSupplier, setSelectedSupplier] = useState<Supplier | null>(null)
  const [supplierToDelete, setSupplierToDelete] = useState<Supplier | null>(null)

  const { data: suppliers, loading, refetch } = useSuppliers()
  const { mutate, loading: mutating } = useSupplierMutation()

  // Filtrar fornecedores pela busca
  const filteredSuppliers = (suppliers || []).filter(supplier =>
    supplier.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    supplier.document.includes(searchQuery) ||
    supplier.email?.toLowerCase().includes(searchQuery.toLowerCase())
  )

  const handleCreate = () => {
    setSelectedSupplier(null)
    setFormDialogOpen(true)
  }

  const handleEdit = (supplier: Supplier) => {
    setSelectedSupplier(supplier)
    setFormDialogOpen(true)
  }

  const handleDeleteClick = (supplier: Supplier) => {
    setSupplierToDelete(supplier)
    setDeleteDialogOpen(true)
  }

  const handleConfirmDelete = async () => {
    if (!supplierToDelete) return

    try {
      await mutate(endpoints.suppliers.delete(supplierToDelete.uuid), 'DELETE')
      toast.success('Fornecedor excluído com sucesso!')
      setDeleteDialogOpen(false)
      setSupplierToDelete(null)
      refetch()
    } catch (error: any) {
      toast.error(error.message || 'Erro ao excluir fornecedor')
    }
  }

  const handleSubmit = async (data: any) => {
    try {
      if (selectedSupplier) {
        await mutate(endpoints.suppliers.update(selectedSupplier.uuid), 'PUT', data)
        toast.success('Fornecedor atualizado com sucesso!')
      } else {
        await mutate(endpoints.suppliers.create, 'POST', data)
        toast.success('Fornecedor criado com sucesso!')
      }
      
      setFormDialogOpen(false)
      await refetch()
      setSelectedSupplier(null)
    } catch (error: any) {
      throw error
    }
  }

  return (
    <div className="flex flex-col gap-4">
      {/* Header e Busca */}
      <div className="@container/main px-4 lg:px-6">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
              <Truck className="h-8 w-8" />
              Fornecedores
            </h1>
            <p className="text-muted-foreground">
              Gerencie seus fornecedores e dados de contato
            </p>
          </div>
          <Button onClick={handleCreate} size="lg">
            <Plus className="mr-2 h-5 w-5" />
            Novo Fornecedor
          </Button>
        </div>

        {/* Campo de Busca */}
        <Card>
          <CardContent className="pt-6">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Buscar por nome, CNPJ/CPF ou e-mail..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10 pr-10"
              />
              {searchQuery && (
                <button
                  onClick={() => setSearchQuery('')}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  <X className="h-4 w-4" />
                </button>
              )}
            </div>
            {searchQuery && (
              <p className="text-sm text-muted-foreground mt-2">
                {filteredSuppliers.length} fornecedor(es) encontrado(s)
              </p>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Tabela de Fornecedores */}
      <div className="@container/main px-4 lg:px-6">
        <Card>
          <CardHeader>
            <CardTitle>Lista de Fornecedores</CardTitle>
            <CardDescription>
              {filteredSuppliers.length} fornecedor(es) cadastrado(s)
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex items-center justify-center h-64">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
              </div>
            ) : filteredSuppliers.length === 0 ? (
              <div className="text-center py-12">
                <Truck className="h-12 w-12 mx-auto mb-4 text-muted-foreground opacity-50" />
                <p className="text-muted-foreground mb-4">
                  {searchQuery ? 'Nenhum fornecedor encontrado' : 'Nenhum fornecedor cadastrado'}
                </p>
                {!searchQuery && (
                  <Button onClick={handleCreate}>
                    <Plus className="mr-2 h-4 w-4" />
                    Cadastrar Primeiro Fornecedor
                  </Button>
                )}
              </div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nome/Razão Social</TableHead>
                    <TableHead>CNPJ/CPF</TableHead>
                    <TableHead>Contato</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Ações</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredSuppliers.map((supplier) => (
                    <TableRow key={supplier.id}>
                      <TableCell>
                        <div>
                          <p className="font-medium">{supplier.name}</p>
                          {supplier.fantasy_name && (
                            <p className="text-sm text-muted-foreground">{supplier.fantasy_name}</p>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">
                          {supplier.document_type.toUpperCase()}: {supplier.document}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="space-y-1">
                          {supplier.phone && (
                            <div className="flex items-center gap-1 text-sm">
                              <Phone className="h-3 w-3" />
                              {supplier.phone}
                            </div>
                          )}
                          {supplier.email && (
                            <div className="flex items-center gap-1 text-sm text-muted-foreground">
                              <Mail className="h-3 w-3" />
                              {supplier.email}
                            </div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant={supplier.is_active ? 'default' : 'secondary'}>
                          {supplier.is_active ? 'Ativo' : 'Inativo'}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          <Button
                            variant="outline"
                            size="icon"
                            onClick={() => handleEdit(supplier)}
                            title="Editar"
                          >
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="destructive"
                            size="icon"
                            onClick={() => handleDeleteClick(supplier)}
                            title="Excluir"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Dialogs */}
      <SupplierFormDialog
        open={formDialogOpen}
        onOpenChange={setFormDialogOpen}
        supplier={selectedSupplier}
        onSubmit={handleSubmit}
        isLoading={mutating}
      />

      {/* AlertDialog de Confirmação de Exclusão */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2">
              <Trash2 className="h-5 w-5 text-destructive" />
              Excluir Fornecedor
            </AlertDialogTitle>
            <AlertDialogDescription className="space-y-2">
              <p>
                Tem certeza que deseja excluir o fornecedor <strong>"{supplierToDelete?.name}"</strong>?
              </p>
              {supplierToDelete && (
                <div className="mt-3 p-3 bg-muted rounded-md text-sm">
                  <p>{supplierToDelete.document_type.toUpperCase()}: {supplierToDelete.document}</p>
                  {supplierToDelete.email && <p>Email: {supplierToDelete.email}</p>}
                </div>
              )}
              <p className="text-destructive font-medium mt-3">
                Esta ação não pode ser desfeita. Certifique-se de que não há despesas vinculadas.
              </p>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={mutating}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleConfirmDelete}
              disabled={mutating}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {mutating ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Excluindo...
                </>
              ) : (
                <>
                  <Trash2 className="mr-2 h-4 w-4" />
                  Excluir Fornecedor
                </>
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}

