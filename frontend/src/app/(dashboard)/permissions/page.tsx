"use client"

import { DataTable } from "./components/data-table"
import { PermissionFormDialog } from "./components/permission-form-dialog"
import { StatCards } from "./components/stat-cards"
import { useAuthenticatedPermissions, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"
import { toast } from "sonner"
import { Button } from "@/components/ui/button"
import { Plus } from "lucide-react"

interface Permission {
  id: number
  name: string
  slug: string
  description?: string
  created_at: string
  updated_at: string
}

interface PermissionFormValues {
  name: string
  slug?: string
  description?: string
  module?: string
  action?: string
  resource?: string
}

export default function PermissionsPage() {
  const { data: permissions, loading, error, refetch, isAuthenticated } = useAuthenticatedPermissions()
  const { mutate: createPermission, loading: creating } = useMutation()
  const { mutate: updatePermission, loading: updating } = useMutation()
  const { mutate: deletePermission, loading: deleting } = useMutation()

  // Extrair array de permissões do objeto retornado pela API
  const permissionsArray: Permission[] = Array.isArray(permissions)
    ? permissions
    : permissions && typeof permissions === 'object' && 'permissions' in permissions
      ? (permissions as any).permissions || []
      : []

  const handleAddPermission = async (permissionData: PermissionFormValues) => {
    try {
      const result = await createPermission(
        endpoints.permissions.create,
        'POST',
        permissionData
      )
      
      if (result) {
        toast.success('Permissão criada com sucesso!')
        await refetch()
      }
    } catch (error: any) {
      console.error('Erro ao criar permissão:', error)
      
      // Extrair mensagens de erro de validação
      const errorMessage = error.message || 'Erro ao criar permissão'
      
      // Se houver múltiplas mensagens (separadas por \n), exibir uma por vez
      if (errorMessage.includes('\n')) {
        const messages = errorMessage.split('\n')
        messages.forEach((msg: string) => {
          if (msg.trim()) {
            toast.error(msg.trim())
          }
        })
      } else {
        toast.error(errorMessage)
      }
    }
  }

  const handleDeletePermission = async (id: number) => {
    try {
      const result = await deletePermission(
        endpoints.permissions.delete(id.toString()),
        'DELETE'
      )
      
      if (result) {
        toast.success('Permissão excluída com sucesso!')
        await refetch()
      }
    } catch (error: any) {
      console.error('Erro ao excluir permissão:', error)
      const errorMessage = error.message || 'Erro ao excluir permissão'
      toast.error(errorMessage)
    }
  }

  const handleEditPermission = async (id: number, permissionData: PermissionFormValues) => {
    try {
      const result = await updatePermission(
        endpoints.permissions.update(id),
        'PUT',
        permissionData
      )
      
      if (result) {
        toast.success('Permissão atualizada com sucesso!')
        await refetch()
      }
    } catch (error: any) {
      console.error('Erro ao atualizar permissão:', error)
      
      // Extrair mensagens de erro de validação
      const errorMessage = error.message || 'Erro ao atualizar permissão'
      
      // Se houver múltiplas mensagens (separadas por \n), exibir uma por vez
      if (errorMessage.includes('\n')) {
        const messages = errorMessage.split('\n')
        messages.forEach((msg: string) => {
          if (msg.trim()) {
            toast.error(msg.trim())
          }
        })
      } else {
        toast.error(errorMessage)
      }
    }
  }

  if (!isAuthenticated) {
    return <PageLoading />
  }

  if (loading) {
    return <PageLoading />
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Erro ao carregar permissões
          </h3>
          <p className="text-gray-600 mb-4">{error}</p>
          <button 
            onClick={() => refetch()}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Tentar novamente
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Permissões</h1>
          <p className="text-muted-foreground">
            Gerencie as permissões do sistema
          </p>
        </div>
      </div>

      <StatCards permissions={permissionsArray} />

      <DataTable 
        permissions={permissionsArray}
        onDeletePermission={handleDeletePermission}
        onEditPermission={handleEditPermission}
        onAddPermission={handleAddPermission}
      />
    </div>
  )
}
