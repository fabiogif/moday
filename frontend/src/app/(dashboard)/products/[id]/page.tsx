"use client"

import { useState, useEffect } from "react"
import { useParams, useRouter } from "next/navigation"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import {
  ArrowLeft,
  Save,
  Package,
  DollarSign,
  Tag,
  Calendar,
  Edit,
  Trash2,
  Image as ImageIcon,
  BarChart3,
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
const productSchema = z.object({
  name: z.string().min(3, "Nome deve ter pelo menos 3 caracteres"),
  description: z.string().min(10, "Descrição deve ter pelo menos 10 caracteres"),
  price: z.number().min(0.01, "Preço deve ser maior que zero"),
  price_cost: z.number().min(0, "Custo não pode ser negativo").optional(),
  qtd_stock: z.number().int().min(0, "Estoque não pode ser negativo"),
  is_active: z.boolean().optional(),
})

type ProductFormValues = z.infer<typeof productSchema>

interface Product {
  id: number
  identify?: string
  name: string
  description: string
  price: number
  price_cost?: number
  category: string
  stock: number
  qtd_stock?: number
  isActive: boolean
  is_active?: boolean
  createdAt: string
  created_at?: string
  image?: string
  categories?: Array<{
    identify: string
    name: string
  }>
}

export default function ProductDetailPage() {
  const params = useParams()
  const router = useRouter()
  const productId = params.id as string
  
  const [isEditing, setIsEditing] = useState(false)
  const [showDeleteDialog, setShowDeleteDialog] = useState(false)
  
  const { data: product, loading, error, refetch } = useAuthenticatedApi<Product>(
    endpoints.products.getById(productId)
  )
  
  const { mutate: updateProduct, loading: updating } = useMutation()
  const { mutate: deleteProduct, loading: deleting } = useMutation()
  
  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productSchema),
    defaultValues: {
      name: "",
      description: "",
      price: 0,
      price_cost: 0,
      qtd_stock: 0,
      is_active: true,
    },
  })
  
  // Atualizar formulário quando produto carregar
  useEffect(() => {
    if (product) {
      form.reset({
        name: product.name || "",
        description: product.description || "",
        price: Number(product.price) || 0,
        price_cost: Number(product.price_cost) || 0,
        qtd_stock: Number(product.qtd_stock || product.stock) || 0,
        is_active: product.is_active ?? product.isActive ?? true,
      })
    }
  }, [product, form])
  
  const onSubmit = async (data: ProductFormValues) => {
    try {
      const response = await updateProduct(endpoints.products.update(productId), 'PUT', data)
      
      if (response) {
        toast.success("Produto atualizado com sucesso!")
        setIsEditing(false)
        refetch()
      }
    } catch (error: any) {
      toast.error(error.message || "Erro ao atualizar produto")
    }
  }
  
  const handleDelete = async () => {
    try {
      const response = await deleteProduct(endpoints.products.delete(productId), 'DELETE')
      
      if (response) {
        toast.success("Produto excluído com sucesso!")
        router.push("/products")
      }
    } catch (error: any) {
      toast.error(error.message || "Erro ao excluir produto")
    }
  }
  
  // Calcular margem de lucro
  const calculateProfit = () => {
    if (!product) return 0
    const cost = Number(product.price_cost) || 0
    const price = Number(product.price) || 0
    if (cost === 0) return 0
    return ((price - cost) / cost * 100).toFixed(2)
  }
  
  if (loading) {
    return <PageLoading />
  }
  
  if (error || !product) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px]">
        <p className="text-muted-foreground mb-4">Produto não encontrado</p>
        <Button onClick={() => router.push("/products")} variant="outline">
          <ArrowLeft className="w-4 h-4 mr-2" />
          Voltar para Produtos
        </Button>
      </div>
    )
  }
  
  const isActive = product.is_active ?? product.isActive
  const stock = product.qtd_stock ?? product.stock
  
  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => router.push("/products")}
          >
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <h1 className="text-3xl font-bold">{product.name}</h1>
            <p className="text-muted-foreground">Detalhes do Produto</p>
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
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Preço de Venda</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              R$ {Number(product.price).toFixed(2)}
            </div>
            <p className="text-xs text-muted-foreground">
              Preço atual
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Custo</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              R$ {Number(product.price_cost || 0).toFixed(2)}
            </div>
            <p className="text-xs text-muted-foreground">
              Custo unitário
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Margem</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {calculateProfit()}%
            </div>
            <p className="text-xs text-muted-foreground">
              Margem de lucro
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Estoque</CardTitle>
            <Package className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stock}</div>
            <p className="text-xs text-muted-foreground">
              {stock > 0 ? "Unidades disponíveis" : "Sem estoque"}
            </p>
          </CardContent>
        </Card>
      </div>
      
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <div className="grid gap-6 md:grid-cols-2">
            {/* Informações Básicas */}
            <Card className="md:col-span-2">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Package className="w-5 h-5" />
                  Informações Básicas
                </CardTitle>
                <CardDescription>
                  Dados principais do produto
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nome do Produto</FormLabel>
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
                
                <div className="flex items-center gap-4">
                  <FormField
                    control={form.control}
                    name="is_active"
                    render={({ field }) => (
                      <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                        <div className="space-y-0.5">
                          <FormLabel className="text-base">
                            Produto Ativo
                          </FormLabel>
                          <FormDescription>
                            Produto disponível para venda
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
                  
                  <Badge variant={isActive ? "default" : "secondary"} className="h-fit">
                    {isActive ? "Ativo" : "Inativo"}
                  </Badge>
                </div>
                
                {product.categories && product.categories.length > 0 && (
                  <div>
                    <Label>Categorias</Label>
                    <div className="flex gap-2 mt-2">
                      {product.categories.map((cat) => (
                        <Badge key={cat.identify} variant="outline">
                          <Tag className="w-3 h-3 mr-1" />
                          {cat.name}
                        </Badge>
                      ))}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
            
            {/* Preços */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <DollarSign className="w-5 h-5" />
                  Preços
                </CardTitle>
                <CardDescription>
                  Valores de custo e venda
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <FormField
                  control={form.control}
                  name="price"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Preço de Venda</FormLabel>
                      <FormControl>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          {...field}
                          onChange={(e) => field.onChange(parseFloat(e.target.value))}
                          disabled={!isEditing}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="price_cost"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Custo Unitário</FormLabel>
                      <FormControl>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          {...field}
                          onChange={(e) => field.onChange(parseFloat(e.target.value))}
                          disabled={!isEditing}
                        />
                      </FormControl>
                      <FormDescription>
                        Custo de aquisição/produção
                      </FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </CardContent>
            </Card>
            
            {/* Estoque */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Package className="w-5 h-5" />
                  Estoque
                </CardTitle>
                <CardDescription>
                  Controle de quantidade
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <FormField
                  control={form.control}
                  name="qtd_stock"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Quantidade em Estoque</FormLabel>
                      <FormControl>
                        <Input
                          type="number"
                          min="0"
                          step="1"
                          {...field}
                          onChange={(e) => field.onChange(parseInt(e.target.value))}
                          disabled={!isEditing}
                        />
                      </FormControl>
                      <FormDescription>
                        Unidades disponíveis
                      </FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <div className="pt-4 border-t">
                  <div className="flex items-center gap-2 text-sm">
                    <Calendar className="w-4 h-4 text-muted-foreground" />
                    <span className="text-muted-foreground">
                      Criado em: {product.created_at || product.createdAt}
                    </span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </form>
      </Form>
      
      {/* Dialog de Confirmação de Exclusão */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar Exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir o produto <strong>{product.name}</strong>?
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
