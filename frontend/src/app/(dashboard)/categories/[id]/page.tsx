"use client"

import { useState, useEffect } from "react"
import Link from "next/link"
import { useParams, useRouter } from "next/navigation"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import {
  ArrowLeft,
  Save,
  Tag,
  Calendar,
  Edit,
  Trash2,
  Package,
  CheckCircle2,
  XCircle,
  ExternalLink,
  Hash,
  Link2,
} from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Separator } from "@/components/ui/separator"
import { Badge } from "@/components/ui/badge"
import { Switch } from "@/components/ui/switch"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
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
import { cn } from "@/lib/utils"

const categorySchema = z.object({
  name: z.string().min(2, "Nome deve ter pelo menos 2 caracteres"),
  description: z.string().max(500).optional().or(z.literal("")),
  isActive: z.boolean().optional(),
})

type CategoryFormValues = z.infer<typeof categorySchema>

interface RelatedProduct {
  identify: string
  name: string
  price?: number | string
  is_active?: boolean
}

interface Category {
  id?: number
  identify: string
  name: string
  description: string
  url: string
  productCount?: number
  isActive?: boolean
  status: string
  created_at: string
  updated_at?: string
  createdAt?: string
  products?: RelatedProduct[]
}

function formatCurrency(value?: number | string) {
  const num = typeof value === "string" ? parseFloat(value) : value
  if (num === undefined || num === null || Number.isNaN(num)) return "?"
  return new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(num)
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
      isActive: true,
    },
  })

  useEffect(() => {
    if (!category) return
    form.reset({
      name: category.name || "",
      description: category.description || "",
      isActive: category.isActive ?? category.status === "A",
    })
  }, [category, form])

  const onSubmit = async (data: CategoryFormValues) => {
    try {
      const response = await updateCategory(endpoints.categories.update(categoryId), "PUT", {
        name: data.name,
        description: data.description || "",
        status: data.isActive ? "A" : "I",
        isActive: data.isActive,
      })

      if (response) {
        toast.success("Categoria atualizada com sucesso!")
        setIsEditing(false)
        refetch()
      }
    } catch (err: any) {
      toast.error(err.message || "Erro ao atualizar categoria")
    }
  }

  const handleDelete = async () => {
    try {
      const response = await deleteCategory(endpoints.categories.delete(categoryId), "DELETE")
      if (response) {
        toast.success("Categoria excluída com sucesso!")
        router.push("/categories")
      }
    } catch (err: any) {
      toast.error(err.message || "Erro ao excluir categoria")
    }
  }

  if (loading) return <PageLoading />

  if (error || !category) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] gap-4 px-6">
        <p className="text-muted-foreground">Categoria não encontrada</p>
        <Button onClick={() => router.push("/categories")} variant="outline">
          <ArrowLeft className="w-4 h-4 mr-2" />
          Voltar para Categorias
        </Button>
      </div>
    )
  }

  const isActive = category.isActive ?? category.status === "A"
  const productCount = category.productCount ?? category.products?.length ?? 0
  const relatedProducts = category.products ?? []

  return (
    <div className="container mx-auto p-6 space-y-6 max-w-5xl">
      {/* Breadcrumb / back */}
      <div className="flex items-center gap-3">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => router.push("/categories")}
          aria-label="Voltar para lista de categorias"
        >
          <ArrowLeft className="w-5 h-5" />
        </Button>
        <Breadcrumb>
          <BreadcrumbList>
            <BreadcrumbItem>
              <BreadcrumbLink asChild>
                <Link href="/categories">Categorias</Link>
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbPage>{category.name}</BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>
      </div>

      {/* Title + status + primary actions */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div className="space-y-2 min-w-0">
          <div className="flex flex-wrap items-center gap-3">
            <h1 className="text-3xl font-bold tracking-tight truncate">{category.name}</h1>
            <Badge
              variant={isActive ? "default" : "secondary"}
              className={cn(
                "gap-1.5",
                isActive
                  ? "bg-emerald-100 text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300"
                  : "bg-slate-100 text-slate-700 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300"
              )}
            >
              {isActive ? (
                <CheckCircle2 className="h-3.5 w-3.5" aria-hidden />
              ) : (
                <XCircle className="h-3.5 w-3.5" aria-hidden />
              )}
              <span>{isActive ? "Ativa" : "Inativa"}</span>
            </Badge>
          </div>
          <p className="text-sm text-muted-foreground font-mono flex items-center gap-1.5">
            <Hash className="h-3.5 w-3.5" />
            {category.identify}
          </p>
        </div>

        {!isEditing && (
          <div className="flex flex-wrap gap-2">
            <Button variant="default" onClick={() => setIsEditing(true)}>
              <Edit className="w-4 h-4 mr-2" />
              Editar
            </Button>
          </div>
        )}
      </div>

      {/* Key details */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Produtos</CardTitle>
          </CardHeader>
          <CardContent className="flex items-center gap-2">
            <Package className="h-5 w-5 text-muted-foreground" />
            <span className="text-2xl font-bold">{productCount}</span>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Status</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              {isActive ? (
                <CheckCircle2 className="h-5 w-5 text-emerald-600" aria-hidden />
              ) : (
                <XCircle className="h-5 w-5 text-slate-500" aria-hidden />
              )}
              <span className="text-lg font-semibold">{isActive ? "Ativa" : "Inativa"}</span>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Criada em</CardTitle>
          </CardHeader>
          <CardContent className="flex items-center gap-2">
            <Calendar className="h-5 w-5 text-muted-foreground" />
            <span className="text-lg font-semibold">
              {category.created_at || category.createdAt || "?"}
            </span>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        {/* Primary details / edit */}
        <Card>
          <CardHeader className="flex flex-row items-start justify-between gap-4">
            <div>
              <CardTitle className="flex items-center gap-2">
                <Tag className="w-5 h-5" />
                Detalhes principais
              </CardTitle>
              <CardDescription>
                {isEditing
                  ? "Altere os campos e salve para aplicar"
                  : "Informações essenciais da categoria"}
              </CardDescription>
            </div>
            {isEditing && (
              <div className="flex gap-2 shrink-0">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    setIsEditing(false)
                    form.reset({
                      name: category.name || "",
                      description: category.description || "",
                      isActive: category.isActive ?? category.status === "A",
                    })
                  }}
                >
                  Cancelar
                </Button>
                <Button size="sm" onClick={form.handleSubmit(onSubmit)} disabled={updating}>
                  <Save className="w-4 h-4 mr-2" />
                  {updating ? "Salvando..." : "Salvar"}
                </Button>
              </div>
            )}
          </CardHeader>
          <CardContent>
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nome</FormLabel>
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
                          placeholder="Descrição da categoria"
                        />
                      </FormControl>
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
                        <FormLabel className="text-base">Categoria ativa</FormLabel>
                        <FormDescription>
                          Quando inativa, deixa de ficar disponível para uso
                        </FormDescription>
                      </div>
                      <FormControl>
                        <Switch
                          checked={!!field.value}
                          onCheckedChange={field.onChange}
                          disabled={!isEditing}
                          aria-label={field.value ? "Ativa" : "Inativa"}
                        />
                      </FormControl>
                    </FormItem>
                  )}
                />
              </form>
            </Form>
          </CardContent>
        </Card>

        {/* Secondary details */}
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Metadados</CardTitle>
              <CardDescription>Identificadores e datas</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
              <div>
                <p className="text-muted-foreground flex items-center gap-1.5 mb-1">
                  <Hash className="h-3.5 w-3.5" /> Identificador
                </p>
                <p className="font-mono break-all">{category.identify}</p>
              </div>
              {category.url && (
                <div>
                  <p className="text-muted-foreground flex items-center gap-1.5 mb-1">
                    <Link2 className="h-3.5 w-3.5" /> URL / slug
                  </p>
                  <p className="font-mono break-all">{category.url}</p>
                </div>
              )}
              <Separator />
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <p className="text-muted-foreground mb-1">Criada em</p>
                  <p>{category.created_at || "?"}</p>
                </div>
                <div>
                  <p className="text-muted-foreground mb-1">Atualizada em</p>
                  <p>{category.updated_at || "?"}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Related items */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
              <div>
                <CardTitle className="text-base flex items-center gap-2">
                  <Package className="h-4 w-4" />
                  Produtos relacionados
                </CardTitle>
                <CardDescription>
                  {productCount === 0
                    ? "Nenhum produto vinculado"
                    : `${productCount} produto(s) nesta categoria`}
                </CardDescription>
              </div>
              <Button variant="outline" size="sm" asChild>
                <Link href="/products">
                  Ver todos
                  <ExternalLink className="h-3.5 w-3.5 ml-1.5" />
                </Link>
              </Button>
            </CardHeader>
            <CardContent>
              {relatedProducts.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  Ainda não há produtos associados a esta categoria.
                </p>
              ) : (
                <ul className="space-y-2">
                  {relatedProducts.map((product) => (
                    <li key={product.identify}>
                      <Link
                        href={`/products/${product.identify}`}
                        className="flex items-center justify-between rounded-md border px-3 py-2 text-sm hover:bg-accent transition-colors"
                      >
                        <span className="truncate font-medium">{product.name}</span>
                        <span className="text-muted-foreground shrink-0 ml-3">
                          {formatCurrency(product.price)}
                        </span>
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Destructive actions ? visually separated */}
      <Card className="border-destructive/30">
        <CardHeader>
          <CardTitle className="text-base text-destructive">Zona de risco</CardTitle>
          <CardDescription>
            Ações irreversíveis. A exclusão pode ser bloqueada se houver produtos ativos vinculados.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Button
            variant="destructive"
            onClick={() => setShowDeleteDialog(true)}
            disabled={isEditing}
          >
            <Trash2 className="w-4 h-4 mr-2" />
            Excluir categoria
          </Button>
        </CardContent>
      </Card>

      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir a categoria <strong>{category.name}</strong>?
              {productCount > 0 && (
                <span className="block mt-2 text-orange-600 dark:text-orange-400">
                  Atenção: esta categoria possui {productCount} produto(s) associado(s).
                </span>
              )}
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
