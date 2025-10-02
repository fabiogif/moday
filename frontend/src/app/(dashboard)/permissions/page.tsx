"use client"

import { useState } from "react"
import { DataTable } from "./components/data-table"
import { PermissionFormDialog } from "./components/permission-form-dialog"
import { useAuthenticatedPermissions, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"

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
  slug: string
  description?: string
}

export default function PermissionsPage() {
  const { data: permissions, loading, error, refetch, isAuthenticated } = useAuthenticatedPermissions()
  const { mutate: createPermission, loading: creating } = useMutation()
  const { mutate: deletePermission, loading: deleting } = useMutation()

  // Debug: verificar dados recebidos
  console.log('PermissionsPage - permissions:', permissions)
  console.log('PermissionsPage - loading:', loading)
  console.log('PermissionsPage - error:', error)
  console.log('PermissionsPage - isAuthenticated:', isAuthenticated)

  const handleAddPermission = async (permissionData: PermissionFormValues) => {
    try {
      const result = await createPermission(
        endpoints.permissions.create,
        'POST',
        permissionData
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao criar permissão:', error)
    }
  }

  const handleDeletePermission = async (id: number) => {
    try {
      const result = await deletePermission(
        endpoints.permissions.delete(id.toString()),
        'DELETE'
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao excluir permissão:', error)
    }
  }

  const handleEditPermission = (id: number) => {
    // TODO: Implementar edição
    console.log('Editar permissão:', id)
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
      <div className="flex items-center justify-center h-96">
        <div className="text-muted-foreground">Carregando permissões...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar permissões: {error}</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Permissões</h1>
          <p className="text-muted-foreground">
            Gerencie as permissões do sistema
          </p>
        </div>
        <PermissionFormDialog onAddPermission={handleAddPermission} />
      </div>
      
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <DataTable 
          permissions={permissions?.permissions || []}
          onDeletePermission={handleDeletePermission}
          onEditPermission={handleEditPermission}
          onAddPermission={handleAddPermission}
        />
      </div>
    </div>
  )
}
