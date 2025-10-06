"use client"

import { useState, useEffect } from "react"
import { useParams, useRouter } from "next/navigation"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import {
  ArrowLeft,
  Save,
  Tag,
  FileText,
  Palette,
  Calendar,
  Edit,
  Trash2,
  Package,
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
const categorySchema = z.object({
  name: z.string().min(3, "Nome deve ter pelo menos 3 caracteres"),
  description: z.string().min(10, "Descrição deve ter pelo menos 10 caracteres"),
  color: z.string().optional(),
  isActive: z.boolean().optional(),
})

type CategoryFormValues = z.infer<typeof categorySchema>

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

export default function CategoryDetailPage() {
  const params = useParams()
  const router = useRouter()
  const categoryId = params.id as string
  
  const [isEditing, setIsEditing] = useState(false)
  const [showDeleteDialog, setShowDeleteDialog] = useState(false)
  
  const { data: category, loading, error, refetch } = useAuthenticatedApi<Category>(
    endpoints.categories.getById(categoryId)
  )
  
  const { mutate: updateCategory, loading: updating } = useMutation()
  const { mutate: deleteCategory, loading: deleting } = useMutation()
  
  const form = useForm<CategoryFormValues>({
    resolver: zodResolver(categorySchema),
    defaultValues: {
      name: "",
      description: "",
      color: "#000000",
      isActive: true,
    },
  })
  
  // Atualizar formulário quando categoria carregar
  useEffect(() => {
    if (category) {
      form.reset({
        name: category.name || "",
        description: category.description || "",
        color: category.color || "#000000",
        isActive: category.isActive ?? true,
      })
    }
  }, [category, form])
  
  const onSubmit = async (data: CategoryFormValues) => {
    try {
      const response = await updateCategory(endpoints.categories.update(categoryId), 'PUT', data)
      
      if (response) {
        toast.success("Categoria atualizada com sucesso!")
        setIsEditing(false)
        refetch()
      }
    } catch (error: any) {
      toast.error(error.message || "Erro ao atualizar categoria")
    }
  }
  
  const handleDelete = async () => {
    try {
      const response = await deleteCategory(endpoints.categories.delete(categoryId), 'DELETE')
      
      if (response) {
        toast.success("Categoria excluída com sucesso!")
        router.push("/categories")
      }
    } catch (error: any) {
      toast.error(error.message || "Erro ao excluir categoria")
    }
  }
  
  if (loading) {
    return <PageLoading />
  }
  
  if (error || !category) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px]">
        <p className="text-muted-foreground mb-4">Categoria não encontrada</p>
        <Button onClick={() => router.push("/categories")} variant="outline">
          <ArrowLeft className="w-4 h-4 mr-2" />
          Voltar para Categorias
        </Button>
      </div>
    )
  }
  
  const isActive = category.isActive ?? (category.status === 'active')
  const productCount = category.productCount || 0
  
  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => router.push("/categories")}
          >
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <h1 className="text-3xl font-bold">{category.name}</h1>
            <p className="text-muted-foreground">Detalhes da Categoria</p>
          </div>
        </div>
        
        <div className="flex gap-2">
          {isEditing ? (
            <>
              <Button
                variant="outline"
                onClick={() => {
                  setIsEditing(false)
                  form.reset()
                }}
              >
                Cancelar
              </Button>
              <Button
                onClick={form.handleSubmit(onSubmit)}
                disabled={updating}
              >
                <Save className="w-4 h-4 mr-2" />
                {updating ? "Salvando..." : "Salvar"}
              </Button>
            </>
          ) : (
            <>
              <Button
                variant="outline"
                onClick={() => setIsEditing(true)}
              >
                <Edit className="w-4 h-4 mr-2" />
                Editar
              </Button>
              <Button
                variant="destructive"
                onClick={() => setShowDeleteDialog(true)}
              >
                <Trash2 className="w-4 h-4 mr-2" />
                Excluir
              </Button>
            </>
          )}
        </div>
      </div>
      
      <Separator />
      
      {/* Cards de Estatísticas */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total de Produtos</CardTitle>
            <Package className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{productCount}</div>
            <p className="text-xs text-muted-foreground">
              Produtos nesta categoria
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Status</CardTitle>
            <Tag className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <Badge variant={isActive ? "default" : "secondary"}>
              {isActive ? "Ativa" : "Inativa"}
            </Badge>
            <p className="text-xs text-muted-foreground mt-2">
              Status da categoria
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Data de Criação</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {new Date(category.created_at || category.createdAt || '').toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: 'short' 
              })}
            </div>
            <p className="text-xs text-muted-foreground">
              {new Date(category.created_at || category.createdAt || '').getFullYear()}
            </p>
          </CardContent>
        </Card>
      </div>
      
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          {/* Informações Básicas */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Tag className="w-5 h-5" />
                Informações Básicas
              </CardTitle>
              <CardDescription>
                Dados principais da categoria
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Nome da Categoria</FormLabel>
                    <FormControl>
                      <Input {...field} disabled={!isEditing} />
                    </FormControl>
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
                    <FormMessage />
                  </FormItem>
                )}
              />
              
              <div className="grid gap-4 md:grid-cols-2">
                <FormField
                  control={form.control}
                  name="color"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Cor</FormLabel>
                      <div className="flex gap-2">
                        <FormControl>
                          <Input 
                            {...field} 
                            type="color" 
                            disabled={!isEditing}
                            className="w-20 h-10 cursor-pointer"
                          />
                        </FormControl>
                        <Input 
                          value={field.value} 
                          onChange={field.onChange}
                          disabled={!isEditing}
                          placeholder="#000000"
                        />
                      </div>
                      <FormDescription>
                        Cor de identificação da categoria
                      </FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="isActive"
                  render={({ field }) => (
                    <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                      <div className="space-y-0.5">
                        <FormLabel className="text-base">
                          Categoria Ativa
                        </FormLabel>
                        <FormDescription>
                          Categoria disponível para uso
                        </FormDescription>
                      </div>
                      <FormControl>
                        <Switch
                          checked={field.value}
                          onCheckedChange={field.onChange}
                          disabled={!isEditing}
                        />
                      </FormControl>
                    </FormItem>
                  )}
                />
              </div>
              
              {category.url && (
                <div className="pt-4 border-t">
                  <Label className="text-sm font-medium">URL</Label>
                  <p className="text-sm text-muted-foreground mt-1">{category.url}</p>
                </div>
              )}
              
              {category.identify && (
                <div className="pt-2">
                  <Label className="text-sm font-medium">Identificador</Label>
                  <p className="text-sm text-muted-foreground mt-1 font-mono">{category.identify}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </form>
      </Form>
      
      {/* Dialog de Confirmação de Exclusão */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar Exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir a categoria <strong>{category.name}</strong>?
              {productCount > 0 && (
                <span className="block mt-2 text-orange-600">
                  Atenção: Esta categoria possui {productCount} produto(s) associado(s).
                </span>
              )}
              Esta ação não pode ser desfeita.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              disabled={deleting}
            >
              {deleting ? "Excluindo..." : "Excluir"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
