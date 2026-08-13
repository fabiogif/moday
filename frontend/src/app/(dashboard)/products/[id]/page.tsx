"use client"

import { useState } from "react"
import { useParams, useRouter } from "next/navigation"
import { resolveImageUrl } from "@/lib/resolve-image-url"
import {
  ArrowLeft,
  Package,
  DollarSign,
  Tag,
  Calendar,
  Edit,
  Trash2,
  BarChart3,
} from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Label } from "@/components/ui/label"
import { Separator } from "@/components/ui/separator"
import { Badge } from "@/components/ui/badge"
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
import { useAuthenticatedApi, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { toast } from "sonner"

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
  url?: string
  categories?: Array<{
    identify: string
    name: string
  }>
  variations?: Array<{
    id: string
    name: string
    price: number
  }>
}

export default function ProductDetailPage() {
  const params = useParams()
  const router = useRouter()
  const productId = params.id as string
  
  const [showDeleteDialog, setShowDeleteDialog] = useState(false)
  
  const { data: product, loading, error } = useAuthenticatedApi<Product>(
    endpoints.products.getById(productId)
  )
  
  const { mutate: deleteProduct, loading: deleting } = useMutation()

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
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    )
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
          <Button
            variant="default"
            onClick={() => router.push(`/products/${productId}/edit`)}
          >
            <Edit className="w-4 h-4 mr-2" />
            Editar Produto
          </Button>
          <Button
            variant="destructive"
            onClick={() => setShowDeleteDialog(true)}
          >
            <Trash2 className="w-4 h-4 mr-2" />
            Excluir
          </Button>
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
      
      <div className="space-y-6">
        <div className="grid gap-6 md:grid-cols-3">
          {/* Imagem do Produto */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Package className="w-5 h-5" />
                Imagem
              </CardTitle>
              <CardDescription>
                Foto do produto
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="aspect-square relative overflow-hidden rounded-lg border bg-muted">
                {product.image || product.url ? (
                  <img
                    src={resolveImageUrl(product.url || product.image) || ""}
                    alt={product.name}
                    className="object-cover w-full h-full"
                  />
                ) : (
                  <div className="flex items-center justify-center w-full h-full">
                    <div className="text-center p-4">
                      <Package className="w-12 h-12 mx-auto text-muted-foreground mb-2" />
                      <p className="text-sm text-muted-foreground">Sem imagem</p>
                    </div>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
          
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
              <div>
                <Label>Nome do Produto</Label>
                <p className="mt-1 text-lg font-medium">{product.name}</p>
              </div>
              
              <div>
                <Label>Descrição</Label>
                <p className="mt-1 text-muted-foreground">{product.description}</p>
              </div>
              
              <div className="flex items-center gap-4">
                <div className="flex-1 rounded-lg border p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Status</Label>
                      <p className="text-sm text-muted-foreground">
                        {isActive ? "Produto ativo e disponível para venda" : "Produto inativo"}
                      </p>
                    </div>
                    <Badge variant={isActive ? "default" : "secondary"}>
                      {isActive ? "Ativo" : "Inativo"}
                    </Badge>
                  </div>
                </div>
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
              <div>
                <Label>Preço de Venda</Label>
                <p className="mt-1 text-xl font-bold">
                  R$ {Number(product.price).toFixed(2)}
                </p>
              </div>
              
              <div>
                <Label>Custo Unitário</Label>
                <p className="mt-1 text-lg">
                  R$ {Number(product.price_cost || 0).toFixed(2)}
                </p>
                <p className="text-sm text-muted-foreground mt-1">
                  Custo de aquisição/produção
                </p>
              </div>
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
              <div>
                <Label>Quantidade em Estoque</Label>
                <p className="mt-1 text-xl font-bold">{stock}</p>
                <p className="text-sm text-muted-foreground mt-1">
                  Unidades disponíveis
                </p>
              </div>
              
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
        
        {/* Opcionais do Produto */}
        {product.variations && product.variations.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <DollarSign className="w-5 h-5" />
                Opcionais do Produto
              </CardTitle>
              <CardDescription>
                Itens adicionais que alteram o preço final (tamanhos, complementos, etc.)
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                {product.variations.map((optional) => (
                  <div key={optional.id} className="rounded-lg border p-4 bg-muted/30">
                    <div className="flex items-start justify-between gap-2">
                      <h4 className="font-medium">{optional.name}</h4>
                      {Number(optional.price) > 0 && (
                        <Badge variant="secondary" className="shrink-0">
                          +R$ {Number(optional.price).toFixed(2)}
                        </Badge>
                      )}
                      {Number(optional.price) === 0 && (
                        <Badge variant="outline" className="shrink-0">
                          Incluso
                        </Badge>
                      )}
                      {Number(optional.price) < 0 && (
                        <Badge variant="default" className="shrink-0 bg-green-600">
                          {Number(optional.price).toFixed(2)}
                        </Badge>
                      )}
                    </div>
                    <p className="text-xs text-muted-foreground mt-1">
                      {Number(optional.price) > 0 && 'Valor adicional ao preço base'}
                      {Number(optional.price) === 0 && 'Sem custo adicional'}
                      {Number(optional.price) < 0 && 'Desconto no preço base'}
                    </p>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}
      </div>
      
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
