"use client"

import { useState, useEffect } from "react"
import { useParams, useRouter } from "next/navigation"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import {
  ArrowLeft,
  Save,
  Shield,
  FileText,
  Calendar,
  Edit,
  Trash2,
  User,
  Settings,
} from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Separator } from "@/components/ui/separator"
import { Badge } from "@/components/ui/badge"
import { Switch } from "@/components/ui/switch"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
  FormDescription,
} from "@/components/ui/form"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { PageLoading } from "@/components/ui/loading-progress"
import { useAuthenticatedApi, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { toast } from "sonner"

// Schema de validação
const permissionSchema = z.object({
  name: z.string().min(2, "Nome deve ter pelo menos 2 caracteres"),
  slug: z.string().min(2, "Slug deve ter pelo menos 2 caracteres"),
  description: z.string().optional(),
})

type PermissionFormValues = z.infer<typeof permissionSchema>

interface Permission {
  id: number
  name: string
  slug: string
  description?: string
  created_at: string
  updated_at: string
}

export default function EditPermissionPage() {
  const params = useParams()
  const router = useRouter()
  const permissionId = params.id as string

  const [permission, setPermission] = useState<Permission | null>(null)
  const [isEditing, setIsEditing] = useState(false)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)

  const { data: permissionData, loading: apiLoading, error: apiError } = useAuthenticatedApi<Permission>(
    permissionId ? endpoints.permissions.show(permissionId) : ''
  )

  const { mutate: updatePermission, loading: updating } = useMutation()
  const { mutate: deletePermission, loading: deleting } = useMutation()

  const form = useForm<PermissionFormValues>({
    resolver: zodResolver(permissionSchema),
    defaultValues: {
      name: "",
      slug: "",
      description: "",
    },
  })

  useEffect(() => {
    if (permissionData) {
      setPermission(permissionData)
      form.reset({
        name: permissionData.name,
        slug: permissionData.slug,
        description: permissionData.description || "",
      })
    }
  }, [permissionData, form])

  const onSubmit = async (data: PermissionFormValues) => {
    try {
      const result = await updatePermission(
        endpoints.permissions.update(parseInt(permissionId)),
        'PUT',
        data
      )
      
      if (result) {
        toast.success('Permissão atualizada com sucesso!')
        setIsEditing(false)
        // Atualizar dados locais
        setPermission(prev => prev ? { ...prev, ...data } : null)
      }
    } catch (error: any) {

      toast.error(error.message || 'Erro ao atualizar permissão')
    }
  }

  const handleDelete = async () => {
    try {
      const result = await deletePermission(
        endpoints.permissions.delete(permissionId),
        'DELETE'
      )
      
      if (result) {
        toast.success('Permissão excluída com sucesso!')
        router.push('/permissions')
      }
    } catch (error: any) {

      toast.error(error.message || 'Erro ao excluir permissão')
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  if (apiLoading) {
    return <PageLoading />
  }

  if (apiError || !permission) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Erro ao carregar permissão
          </h3>
          <p className="text-gray-600 mb-4">{apiError || 'Permissão não encontrada'}</p>
          <Button onClick={() => router.push('/permissions')}>
            <ArrowLeft className="mr-2 h-4 w-4" />
            Voltar para Permissões
          </Button>
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-6 py-2 px-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.push('/permissions')}
          >
            <ArrowLeft className="mr-2 h-4 w-4" />
            Voltar
          </Button>
          <div>
            <h1 className="text-3xl font-bold">Editar Permissão</h1>
            <p className="text-muted-foreground">
              Gerencie os dados da permissão
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          {!isEditing ? (
            <Button onClick={() => setIsEditing(true)}>
              <Edit className="mr-2 h-4 w-4" />
              Editar
            </Button>
          ) : (
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  setIsEditing(false)
                  form.reset({
                    name: permission.name,
                    slug: permission.slug,
                    description: permission.description || "",
                  })
                }}
              >
                Cancelar
              </Button>
              <Button
                onClick={form.handleSubmit(onSubmit)}
                disabled={updating}
              >
                <Save className="mr-2 h-4 w-4" />
                {updating ? 'Salvando...' : 'Salvar'}
              </Button>
            </div>
          )}
        </div>
      </div>

      {/* Informações da Permissão */}
      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
              {/* Informações Básicas */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Shield className="w-5 h-5" />
                    Informações Básicas
                  </CardTitle>
                  <CardDescription>
                    Dados principais da permissão
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <FormField
                    control={form.control}
                    name="name"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Nome da Permissão</FormLabel>
                        <FormControl>
                          <Input {...field} disabled={!isEditing} />
                        </FormControl>
                        <FormDescription>
                          Nome descritivo da permissão
                        </FormDescription>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  
                  <FormField
                    control={form.control}
                    name="slug"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Slug</FormLabel>
                        <FormControl>
                          <Input {...field} disabled={!isEditing} />
                        </FormControl>
                        <FormDescription>
                          Identificador único da permissão (ex: create_users)
                        </FormDescription>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  
                  <FormField
                    control={form.control}
                    name="description"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Descrição</FormLabel>
                        <FormControl>
                          <Textarea 
                            {...field} 
                            disabled={!isEditing}
                            rows={4}
                            className="resize-none"
                          />
                        </FormControl>
                        <FormDescription>
                          Descrição detalhada da permissão
                        </FormDescription>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </CardContent>
              </Card>
            </form>
          </Form>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Informações do Sistema */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Settings className="w-5 h-5" />
                Informações do Sistema
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium">ID</span>
                <Badge variant="outline">{permission.id}</Badge>
              </div>
              
              <Separator />
              
              <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm">
                  <Calendar className="w-4 h-4 text-muted-foreground" />
                  <span className="text-muted-foreground">Criado em:</span>
                </div>
                <p className="text-sm">{formatDate(permission.created_at)}</p>
              </div>
              
              <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm">
                  <Edit className="w-4 h-4 text-muted-foreground" />
                  <span className="text-muted-foreground">Atualizado em:</span>
                </div>
                <p className="text-sm">{formatDate(permission.updated_at)}</p>
              </div>
            </CardContent>
          </Card>

          {/* Ações Perigosas */}
          <Card className="border-red-200">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-red-600">
                <Trash2 className="w-5 h-5" />
                Ações Perigosas
              </CardTitle>
              <CardDescription>
                Estas ações não podem ser desfeitas
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button
                variant="destructive"
                onClick={() => setDeleteDialogOpen(true)}
                disabled={deleting}
                className="w-full"
              >
                <Trash2 className="mr-2 h-4 w-4" />
                {deleting ? 'Excluindo...' : 'Excluir Permissão'}
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Dialog de Confirmação de Exclusão */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir Permissão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir a permissão "{permission.name}"? 
              Esta ação não pode ser desfeita e pode afetar usuários que possuem esta permissão.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              className="bg-red-600 hover:bg-red-700"
            >
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
