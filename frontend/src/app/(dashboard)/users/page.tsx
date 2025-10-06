"use client"

import { useState } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"
import { UsersPagination } from "./components/users-pagination"
import { useAuthenticatedUsers, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"
import { toast } from "sonner"

interface Profile {
  id: number
  name: string
  description: string
  is_active: boolean
}

interface User {
  id: number
  name: string
  email: string
  phone?: string
  avatar?: string
  is_active: boolean
  created_at: string
  updated_at: string
  profiles?: Profile[]
}

interface UserFormValues {
  name: string
  email: string
  password: string
  phone?: string
  is_active: boolean
}

export default function UsersPage() {
  const [currentPage, setCurrentPage] = useState(1)
  const { data: usersData, loading, error, refetch, isAuthenticated, pagination } = useAuthenticatedUsers(currentPage, 15)
  const { mutate: createUser, loading: creating } = useMutation()
  const { mutate: deleteUser, loading: deleting } = useMutation()

  const handlePageChange = async (page: number) => {
    setCurrentPage(page)
    // O hook será recriado automaticamente com os novos parâmetros
  }

  const handleAddUser = async (userData: UserFormValues) => {
    try {
      const result = await createUser(
        endpoints.users.create,
        'POST',
        userData
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao criar usuário:', error)
    }
  }

  const handleDeleteUser = async (id: number) => {
    try {
      const result = await deleteUser(
        endpoints.users.delete(id.toString()),
        'DELETE'
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao excluir usuário:', error)
    }
  }

  const handleEditUser = async (id: number, userData: UserFormValues) => {
    try {
      const result = await createUser(
        endpoints.users.update(id),
        'PUT',
        userData
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao editar usuário:', error)
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
            Erro ao carregar usuários
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

  // Extract users from the response
  const users = usersData?.users || (Array.isArray(usersData) ? usersData : [])

  return (
    <div className="flex flex-col gap-6 p-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Usuários</h1>
          <p className="text-muted-foreground">
            Gerencie os usuários do sistema
          </p>
        </div>
      </div>

      <StatCards />

      <DataTable 
        users={users}
        onDeleteUser={handleDeleteUser}
        onEditUser={handleEditUser}
        onAddUser={handleAddUser}
        onRefresh={refetch}
      />

      {pagination && (
        <UsersPagination
          onPageChange={handlePageChange}
          currentPage={pagination.current_page}
          totalPages={pagination.last_page}
          totalItems={pagination.total}
          itemsPerPage={pagination.per_page}
        />
      )}
    </div>
  )
}
