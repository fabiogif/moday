"use client"

import { DataTable } from "./components/data-table"
import { ProfileFormDialog } from "./components/profile-form-dialog"
import { StatCards } from "./components/stat-cards"
import { useAuthenticatedProfiles, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"
import { toast } from "sonner"
import { Button } from "@/components/ui/button"
import { Plus } from "lucide-react"

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

  const handleAddProfile = async (profileData: ProfileFormValues) => {
    try {
      const result = await createProfile(
        endpoints.profiles.create,
        'POST',
        profileData
      )
      
      if (result) {
        toast.success('Perfil criado com sucesso!')
        await refetch()
      }
    } catch (error: any) {
      console.error('Erro ao criar perfil:', error)
      toast.error(error.message || 'Erro ao criar perfil')
    }
  }

  const handleDeleteProfile = async (id: number) => {
    try {
      const result = await deleteProfile(
        endpoints.profiles.delete(id.toString()),
        'DELETE'
      )
      
      if (result) {
        toast.success('Perfil excluído com sucesso!')
        await refetch()
      }
    } catch (error: any) {
      console.error('Erro ao excluir perfil:', error)
      toast.error(error.message || 'Erro ao excluir perfil')
    }
  }

  const handleEditProfile = async (id: number, profileData: ProfileFormValues) => {
    try {
      const result = await createProfile(
        endpoints.profiles.update(id),
        'PUT',
        profileData
      )
      
      if (result) {
        toast.success('Perfil atualizado com sucesso!')
        await refetch()
      }
    } catch (error: any) {
      console.error('Erro ao editar perfil:', error)
      toast.error(error.message || 'Erro ao editar perfil')
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
            Erro ao carregar perfis
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
          <h1 className="text-3xl font-bold">Perfis</h1>
          <p className="text-muted-foreground">
            Gerencie os perfis do sistema
          </p>
        </div>
      </div>

      <StatCards profiles={profiles || []} />

      <DataTable 
        profiles={profiles || []}
        onDeleteProfile={handleDeleteProfile}
        onEditProfile={handleEditProfile}
        onAddProfile={handleAddProfile}
        onRefresh={refetch}
      />
    </div>
  )
}
