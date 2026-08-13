'use client'

import { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
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
import { useFinancialCategories, useFinancialCategoryMutation, FinancialCategory } from '@/hooks/use-financial-categories'
import { CategoryFormDialog } from './components/category-form-dialog'
import { Plus, FileText, Edit, Trash2, Loader2, TrendingUp, TrendingDown } from 'lucide-react'
import { toast } from 'sonner'
import { endpoints } from '@/lib/api-client'

export default function FinancialCategoriesPage() {
  const [activeTab, setActiveTab] = useState<'all' | 'receita' | 'despesa'>('all')
  const [formDialogOpen, setFormDialogOpen] = useState(false)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [selectedCategory, setSelectedCategory] = useState<FinancialCategory | null>(null)
  const [categoryToDelete, setCategoryToDelete] = useState<FinancialCategory | null>(null)

  const { data: categories, loading, refetch } = useFinancialCategories()
  const { mutate, loading: mutating } = useFinancialCategoryMutation()

  // Filtrar por tipo
  const filteredCategories = (categories || []).filter(cat => {
    if (activeTab === 'all') return true
    return cat.type === activeTab
  })

  const handleCreate = (type?: 'receita' | 'despesa') => {
    setSelectedCategory(null)
    setFormDialogOpen(true)
    if (type) {
      setActiveTab(type)
    }
  }

  const handleEdit = (category: FinancialCategory) => {
    setSelectedCategory(category)
    setFormDialogOpen(true)
  }

  const handleDeleteClick = (category: FinancialCategory) => {
    setCategoryToDelete(category)
    setDeleteDialogOpen(true)
  }

  const handleConfirmDelete = async () => {
    if (!categoryToDelete) return

    try {
      await mutate(endpoints.financialCategories.delete(categoryToDelete.uuid), 'DELETE')
      toast.success('Categoria excluída com sucesso!')
      setDeleteDialogOpen(false)
      setCategoryToDelete(null)
      refetch()
    } catch (error: any) {
      toast.error(error.message || 'Erro ao excluir categoria')
    }
  }

  const handleSubmit = async (data: any) => {
    try {
      if (selectedCategory) {
        await mutate(endpoints.financialCategories.update(selectedCategory.uuid), 'PUT', data)
        toast.success('Categoria atualizada com sucesso!')
      } else {
        await mutate(endpoints.financialCategories.create, 'POST', data)
        toast.success('Categoria criada com sucesso!')
      }
      
      setFormDialogOpen(false)
      await refetch()
      setSelectedCategory(null)
    } catch (error: any) {
      throw error
    }
  }

  const receitasCount = (categories || []).filter(c => c.type === 'receita').length
  const despesasCount = (categories || []).filter(c => c.type === 'despesa').length

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
              <FileText className="h-8 w-8" />
              Categorias Financeiras
            </h1>
            <p className="text-muted-foreground">
              Organize suas receitas e despesas por categorias
            </p>
          </div>
          <Button onClick={() => handleCreate()} size="lg">
            <Plus className="mr-2 h-5 w-5" />
            Nova Categoria
          </Button>
        </div>

        <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as any)} className="w-full">
          <TabsList className="grid w-full md:w-[400px] grid-cols-3">
            <TabsTrigger value="all">
              Todas ({categories?.length || 0})
            </TabsTrigger>
            <TabsTrigger value="receita" className="text-green-600">
              <TrendingUp className="h-4 w-4 mr-2" />
              Receitas ({receitasCount})
            </TabsTrigger>
            <TabsTrigger value="despesa" className="text-red-600">
              <TrendingDown className="h-4 w-4 mr-2" />
              Despesas ({despesasCount})
            </TabsTrigger>
          </TabsList>

          <TabsContent value={activeTab} className="mt-6">
            <Card>
              <CardHeader>
                <CardTitle>Lista de Categorias</CardTitle>
                <CardDescription>
                  {filteredCategories.length} categoria(s) {activeTab !== 'all' ? `de ${activeTab}` : ''}
                </CardDescription>
              </CardHeader>
              <CardContent>
                {loading ? (
                  <div className="flex items-center justify-center h-64">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                  </div>
                ) : filteredCategories.length === 0 ? (
                  <div className="text-center py-12">
                    <FileText className="h-12 w-12 mx-auto mb-4 text-muted-foreground opacity-50" />
                    <p className="text-muted-foreground mb-4">
                      Nenhuma categoria cadastrada
                    </p>
                    <Button onClick={() => handleCreate(activeTab !== 'all' ? activeTab : undefined)}>
                      <Plus className="mr-2 h-4 w-4" />
                      Criar Primeira Categoria
                    </Button>
                  </div>
                ) : (
                  <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    {filteredCategories.map((category) => (
                      <Card key={category.id} className="overflow-hidden">
                        <div
                          className="h-2"
                          style={{ backgroundColor: category.color }}
                        />
                        <CardHeader className="pb-3">
                          <div className="flex items-start justify-between">
                            <div className="flex-1">
                              <CardTitle className="text-lg">{category.name}</CardTitle>
                              <div className="flex items-center gap-2 mt-2">
                                <Badge
                                  variant={category.type === 'receita' ? 'default' : 'destructive'}
                                  className="text-xs"
                                >
                                  {category.type === 'receita' ? (
                                    <><TrendingUp className="h-3 w-3 mr-1" /> Receita</>
                                  ) : (
                                    <><TrendingDown className="h-3 w-3 mr-1" /> Despesa</>
                                  )}
                                </Badge>
                                <Badge variant={category.is_active ? 'outline' : 'secondary'}>
                                  {category.is_active ? 'Ativo' : 'Inativo'}
                                </Badge>
                              </div>
                            </div>
                          </div>
                          {category.description && (
                            <p className="text-sm text-muted-foreground mt-2">
                              {category.description}
                            </p>
                          )}
                        </CardHeader>
                        <CardContent className="pt-0">
                          <div className="flex gap-2">
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => handleEdit(category)}
                              className="flex-1"
                            >
                              <Edit className="h-3 w-3 mr-1" />
                              Editar
                            </Button>
                            <Button
                              variant="destructive"
                              size="sm"
                              onClick={() => handleDeleteClick(category)}
                            >
                              <Trash2 className="h-3 w-3" />
                            </Button>
                          </div>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>

      {/* Dialogs */}
      <CategoryFormDialog
        open={formDialogOpen}
        onOpenChange={setFormDialogOpen}
        category={selectedCategory}
        onSubmit={handleSubmit}
        isLoading={mutating}
        defaultType={activeTab !== 'all' ? activeTab : undefined}
      />

      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2">
              <Trash2 className="h-5 w-5 text-destructive" />
              Excluir Categoria
            </AlertDialogTitle>
            <AlertDialogDescription>
              <p>
                Tem certeza que deseja excluir a categoria <strong>"{categoryToDelete?.name}"</strong>?
              </p>
              <p className="text-destructive font-medium mt-3">
                Esta ação não pode ser desfeita. Certifique-se de que não há lançamentos vinculados.
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
              {mutating ? 'Excluindo...' : 'Excluir Categoria'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}

