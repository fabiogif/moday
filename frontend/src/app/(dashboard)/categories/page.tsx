"use client"

import { useState } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"
import { useAuthenticatedCategories, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"

interface Category {
  id?: number
  identify: string
  name: string
  description: string
  url: string
  color?: string
  productCount?: number
  isActive?: boolean
  status: string
  created_at: string
  createdAt?: string
}

interface CategoryFormValues {
  name: string
  description: string
  color: string
  isActive: boolean
}

export default function CategoriesPage() {
  const { data: categories, loading, error, refetch, isAuthenticated } = useAuthenticatedCategories()
  const { mutate: createCategory, loading: creating } = useMutation()
  const { mutate: deleteCategory, loading: deleting } = useMutation()

  const handleAddCategory = async (categoryData: CategoryFormValues) => {
    try {
      const result = await createCategory(
        endpoints.categories.create,
        'POST',
        categoryData
      )
      
      if (result) {
        // Recarregar dados após criação
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao criar categoria:', error)
    }
  }

  const handleDeleteCategory = async (identify: string) => {
    try {
      const result = await deleteCategory(
        endpoints.categories.delete(identify),
        'DELETE'
      )
      
      if (result) {
        // Recarregar dados após exclusão
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao excluir categoria:', error)
    }
  }

  const handleEditCategory = (category: Category) => {
    console.log("Edit category:", category)
  }

  if (!isAuthenticated) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Usuário não autenticado. Faça login para continuar.</div>
      </div>
    )
  }

  if (loading) {
    return (
      <PageLoading 
        isLoading={loading}
        message="Carregando categorias..."
      />
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar categorias: {error}</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <StatCards />
      </div>
      
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <DataTable 
          categories={Array.isArray(categories) ? categories : []}
          onDeleteCategory={handleDeleteCategory}
          onEditCategory={handleEditCategory}
          onAddCategory={handleAddCategory}
        />
      </div>
    </div>
  )
}
