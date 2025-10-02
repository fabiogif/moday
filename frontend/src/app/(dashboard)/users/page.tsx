"use client"

import { useState } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"
import { useUsers, useMutation } from "@/hooks/use-api"
import { endpoints } from "@/lib/api-client"

interface User {
  id: number
  name: string
  email: string
  avatar: string
  role: string
  plan: string
  billing: string
  status: string
  joinedDate: string
  lastLogin: string
}

interface UserFormValues {
  name: string
  email: string
  role: string
  plan: string
  billing: string
  status: string
}

export default function UsersPage() {
  const { data: users, loading, error, refetch } = useUsers()
  const { mutate: createUser, loading: creating } = useMutation()
  const { mutate: deleteUser, loading: deleting } = useMutation()

  const generateAvatar = (name: string) => {
    const names = name.split(" ")
    if (names.length >= 2) {
      return `${names[0][0]}${names[1][0]}`.toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
  }

  const handleAddUser = async (userData: UserFormValues) => {
    try {
      const result = await createUser(
        endpoints.users.create,
        'POST',
        userData
      )
      
      if (result) {
        // Recarregar dados após criação
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
        // Recarregar dados após exclusão
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao excluir usuário:', error)
    }
  }

  const handleEditUser = (user: User) => {
    // For now, just log the user to edit
    // In a real app, you'd open an edit dialog
    console.log("Edit user:", user)
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-muted-foreground">Carregando usuários...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar usuários: {error}</div>
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
          users={Array.isArray(users) ? users : []}
          onDeleteUser={handleDeleteUser}
          onEditUser={handleEditUser}
          onAddUser={handleAddUser}
        />
      </div>
    </div>
  )
}
