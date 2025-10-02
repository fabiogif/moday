"use client"

import { useState } from "react"
import { DataTable } from "./components/data-table"
import { RoleFormDialog } from "./components/role-form-dialog"
import { useAuthenticatedRoles, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"

interface Role {
  id: number
  name: string
  slug: string
  created_at: string
  updated_at: string
}

interface RoleFormValues {
  name: string
  slug: string
}

export default function RolesPage() {
  const { data: roles, loading, error, refetch, isAuthenticated } = useAuthenticatedRoles()
  const { mutate: createRole, loading: creating } = useMutation()
  const { mutate: deleteRole, loading: deleting } = useMutation()


  const handleAddRole = async (roleData: RoleFormValues) => {
    try {
      const result = await createRole(
        endpoints.roles.create,
        'POST',
        roleData
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao criar role:', error)
    }
  }

  const handleDeleteRole = async (id: number) => {
    try {
      const result = await deleteRole(
        endpoints.roles.delete(id.toString()),
        'DELETE'
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao excluir role:', error)
    }
  }

  const handleEditRole = (id: number) => {
    // TODO: Implementar edição
    console.log('Editar role:', id)
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
        <div className="text-muted-foreground">Carregando roles...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar Função: {error}</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Função</h1>
          <p className="text-muted-foreground">
            Gerencie as funções do sistema
          </p>
        </div>
        <RoleFormDialog onAddRole={handleAddRole} />
      </div>
      
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <DataTable 
          roles={roles?.roles || []}
          onDeleteRole={handleDeleteRole}
          onEditRole={handleEditRole}
          onAddRole={handleAddRole}
        />
      </div>
    </div>
  )
}
