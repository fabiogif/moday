"use client"

import { useState } from "react"
import { DataTable } from "./components/data-table"
import { ProfileFormDialog } from "./components/profile-form-dialog"
import { useAuthenticatedProfiles, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"

interface Profile {
  id: number
  name: string
  description?: string
  created_at: string
  updated_at: string
}

interface ProfileFormValues {
  name: string
  description?: string
}

export default function ProfilesPage() {
  const { data: profiles, loading, error, refetch, isAuthenticated } = useAuthenticatedProfiles()
  const { mutate: createProfile, loading: creating } = useMutation()
  const { mutate: deleteProfile, loading: deleting } = useMutation()

  // Debug: verificar dados recebidos
  console.log('ProfilesPage - profiles:', profiles)
  console.log('ProfilesPage - loading:', loading)
  console.log('ProfilesPage - error:', error)
  console.log('ProfilesPage - isAuthenticated:', isAuthenticated)

  const handleAddProfile = async (profileData: ProfileFormValues) => {
    try {
      const result = await createProfile(
        endpoints.profiles.create,
        'POST',
        profileData
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao criar perfil:', error)
    }
  }

  const handleDeleteProfile = async (id: number) => {
    try {
      const result = await deleteProfile(
        endpoints.profiles.delete(id.toString()),
        'DELETE'
      )
      
      if (result) {
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao excluir perfil:', error)
    }
  }

  const handleEditProfile = (id: number) => {
    // TODO: Implementar edição
    console.log('Editar perfil:', id)
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
        <div className="text-muted-foreground">Carregando perfis...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar perfis: {error}</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Perfis</h1>
          <p className="text-muted-foreground">
            Gerencie os perfis do sistema
          </p>
        </div>
        <ProfileFormDialog onAddProfile={handleAddProfile} />
      </div>
      
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <DataTable 
          profiles={profiles?.profiles || []}
          onDeleteProfile={handleDeleteProfile}
          onEditProfile={handleEditProfile}
          onAddProfile={handleAddProfile}
        />
      </div>
    </div>
  )
}
