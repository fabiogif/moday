"use client"

import { useState } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"
import {
  CategoryFormDialog,
  type CategoryFormValues,
  type EditableCategory,
} from "./components/category-form-dialog"
import { useAuthenticatedCategories, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"
import { useAuth } from "@/contexts/auth-context"
import { toast } from "sonner"

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

export default function CategoriesPage() {
  const { data: categories, loading, error, refetch, isAuthenticated } = useAuthenticatedCategories()
  const { isLoading: authLoading } = useAuth()
  const { mutate: createCategory } = useMutation()
  const { mutate: updateCategory } = useMutation()
  const { mutate: deleteCategory } = useMutation()

  const [editingCategory, setEditingCategory] = useState<EditableCategory | null>(null)
  const [editOpen, setEditOpen] = useState(false)

  const handleAddCategory = async (categoryData: CategoryFormValues) => {
    try {
      const result = await createCategory(endpoints.categories.create, "POST", {
        name: categoryData.name,
        description: categoryData.description || "",
        status: categoryData.isActive ? "A" : "I",
      })

      if (result) {
        toast.success("Categoria criada com sucesso")
        await refetch()
      }
    } catch (error: any) {
      toast.error(error?.message || "Erro ao criar categoria")
    }
  }

  const handleUpdateCategory = async (identify: string, categoryData: CategoryFormValues) => {
    try {
      const result = await updateCategory(endpoints.categories.update(identify), "PUT", {
        name: categoryData.name,
        description: categoryData.description || "",
        status: categoryData.isActive ? "A" : "I",
        isActive: categoryData.isActive,
      })

      if (result) {
        toast.success("Categoria atualizada com sucesso")
        await refetch()
      }
    } catch (error: any) {
      toast.error(error?.message || "Erro ao atualizar categoria")
      throw error
    }
  }

  const handleDeleteCategory = async (identify: string) => {
    try {
      const result = await deleteCategory(endpoints.categories.delete(identify), "DELETE")

      if (result) {
        toast.success("Categoria excluída com sucesso")
        await refetch()
      }
    } catch (error: any) {
      toast.error(error?.message || "Erro ao excluir categoria")
    }
  }

  const handleEditCategory = (category: Category) => {
    setEditingCategory({
      identify: category.identify,
      name: category.name,
      description: category.description,
      color: category.color,
      isActive: category.isActive ?? category.status === "A",
      status: category.status,
    })
    setEditOpen(true)
  }

  if (!authLoading && !isAuthenticated) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Usuário não autenticado. Faça login para continuar.</div>
      </div>
    )
  }

  if (loading) {
    return <PageLoading isLoading={loading} message="Carregando categorias..." />
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

      <CategoryFormDialog
        hideTrigger
        open={editOpen}
        onOpenChange={(open) => {
          setEditOpen(open)
          if (!open) setEditingCategory(null)
        }}
        categoryToEdit={editingCategory}
        onAddCategory={handleAddCategory}
        onEditCategory={handleUpdateCategory}
      />
    </div>
  )
}
