"use client"

import { useMemo, useEffect, useState } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Sparkles, TrendingUp, Package, Loader2 } from "lucide-react"
import { cn } from "@/lib/utils"
import Image from "next/image"
import { toast } from "sonner"
import { apiClient, endpoints } from "@/lib/api-client"
import { resolveImageUrl } from "@/lib/resolve-image-url"

interface Product {
  uuid?: string
  identify?: string
  name: string
  price: string | number
  promotional_price?: string | number | null
  image?: string | null
  image_url?: string | null
  description?: string
}

interface CartItem {
  signature: string
  product: Product
  quantity: number
  observation?: string
  selectedVariation?: any
  selectedOptionals?: any[]
}

interface ProductRecommendationsProps {
  cart: CartItem[]
  allProducts: Product[]
  onAddProduct: (product: Product) => void
  orderHistory?: any[] // Histórico de pedidos para análise
}

/**
 * Componente de Recomendações Inteligentes de Produtos
 * 
 * Fase 4: Sistema de recomendações baseado em:
 * - Produtos frequentemente pedidos juntos (backend otimizado)
 * - Produtos complementares ao carrinho atual
 * - Produtos mais vendidos
 */
export function ProductRecommendations({
  cart,
  allProducts,
  onAddProduct,
  orderHistory = [],
}: ProductRecommendationsProps) {
  const [recommendedProducts, setRecommendedProducts] = useState<Product[]>([])
  const [loading, setLoading] = useState(false)

  // Extrair IDs dos produtos no carrinho
  const cartProductIds = useMemo(() => {
    return cart.map(item => item.product.uuid || item.product.identify || item.product.name).filter(Boolean)
  }, [cart])

  // Fallback: análise local se não houver recomendações do backend
  const getLocalRecommendations = useMemo((): Product[] => {
    if (!orderHistory || orderHistory.length === 0) {
      return []
    }

    // Criar mapa de frequência: produto A -> { produto B: count, produto C: count }
    const frequencyMap: Record<string, Record<string, number>> = {}

    orderHistory.forEach((order: any) => {
      const orderProducts = order.products || []
      const productIds = orderProducts.map((p: any) => p.uuid || p.identify || p.id).filter(Boolean)

      // Para cada par de produtos no pedido, incrementar contador
      for (let i = 0; i < productIds.length; i++) {
        for (let j = i + 1; j < productIds.length; j++) {
          const id1 = productIds[i]
          const id2 = productIds[j]

          if (!frequencyMap[id1]) frequencyMap[id1] = {}
          if (!frequencyMap[id2]) frequencyMap[id2] = {}

          frequencyMap[id1][id2] = (frequencyMap[id1][id2] || 0) + 1
          frequencyMap[id2][id1] = (frequencyMap[id2][id1] || 0) + 1
        }
      }
    })

    // Encontrar produtos mais frequentemente pedidos junto com os do carrinho
    const recommendations: Array<{ product: Product; score: number }> = []
    const seenProducts = new Set(cartProductIds)

    cartProductIds.forEach(cartProductId => {
      const relatedProducts = frequencyMap[cartProductId] || {}
      
      Object.entries(relatedProducts).forEach(([relatedId, count]) => {
        if (!seenProducts.has(relatedId)) {
          const product = allProducts.find(
            p => (p.uuid || p.identify || p.name) === relatedId
          )
          
          if (product) {
            const existing = recommendations.find(r => 
              (r.product.uuid || r.product.identify || r.product.name) === relatedId
            )
            
            if (existing) {
              existing.score += count as number
            } else {
              recommendations.push({ product, score: count as number })
            }
          }
        }
      })
    })

    // Ordenar por score e retornar top 6
    return recommendations
      .sort((a, b) => b.score - a.score)
      .slice(0, 6)
      .map(r => r.product)
  }, [cartProductIds, allProducts, orderHistory])

  // Buscar recomendações do backend quando o carrinho mudar
  useEffect(() => {
    const fetchRecommendations = async () => {
      if (cartProductIds.length === 0) {
        setRecommendedProducts([])
        return
      }

      setLoading(true)
      try {
        const response = await apiClient.get(endpoints.orders.getRecommendations(cartProductIds))
        if (response.success && response.data) {
          setRecommendedProducts(Array.isArray(response.data) ? response.data : [])
        } else {
          // Fallback: usar lógica local se a API não retornar dados
          setRecommendedProducts(getLocalRecommendations)
        }
      } catch (error) {

        // Fallback: usar lógica local se a API falhar
        setRecommendedProducts(getLocalRecommendations)
      } finally {
        setLoading(false)
      }
    }

    fetchRecommendations()
  }, [cartProductIds.join(','), getLocalRecommendations]) // Dependência baseada em string para evitar re-renders desnecessários

  // Não mostrar se não houver recomendações ou se o carrinho estiver vazio
  if ((recommendedProducts.length === 0 && !loading) || cart.length === 0) {
    return null
  }

  const formatPrice = (price: string | number) => {
    const num = typeof price === 'string' ? parseFloat(price) : price
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(num || 0)
  }

  const getProductPrice = (product: Product) => {
    if (product.promotional_price) {
      const promo = typeof product.promotional_price === 'string' 
        ? parseFloat(product.promotional_price) 
        : product.promotional_price
      const regular = typeof product.price === 'string' 
        ? parseFloat(product.price) 
        : product.price
      if (promo < regular) {
        return { price: promo, original: regular, hasPromo: true }
      }
    }
    return { 
      price: typeof product.price === 'string' ? parseFloat(product.price) : product.price, 
      original: null, 
      hasPromo: false 
    }
  }

  return (
    <Card className="border-primary/20 bg-gradient-to-br from-primary/5 to-primary/10">
      <CardHeader className="pb-3">
        <div className="flex items-center gap-2">
          <Sparkles className="h-5 w-5 text-primary" />
          <CardTitle className="text-lg">Recomendações para você</CardTitle>
        </div>
        <CardDescription className="text-xs">
          {recommendedProducts.length > 0 && "Frequentemente pedidos juntos"}
          {recommendedProducts.length === 0 && !loading && "Mais vendidos"}
        </CardDescription>
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className="flex items-center justify-center py-8">
            <Loader2 className="h-6 w-6 animate-spin text-primary" />
            <span className="ml-2 text-sm text-muted-foreground">Carregando recomendações...</span>
          </div>
        ) : (
          <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-primary/20 scrollbar-track-transparent">
            {recommendedProducts.map((product) => {
            const priceInfo = getProductPrice(product)
            const productId = product.uuid || product.identify || product.name
            const imageUrl =
              resolveImageUrl(product.image) ||
              resolveImageUrl(product.image_url)

            return (
              <div
                key={productId}
                className="flex-shrink-0 w-32 sm:w-40 rounded-xl border bg-card hover:border-primary/50 transition-all cursor-pointer group"
                onClick={() => {
                  onAddProduct(product)
                  toast.success(`${product.name} adicionado!`)
                }}
              >
                <div className="relative h-24 sm:h-32 w-full overflow-hidden rounded-t-xl bg-muted">
                  {imageUrl ? (
                    <Image
                      src={imageUrl}
                      alt={product.name}
                      fill
                      className="object-cover group-hover:scale-105 transition-transform"
                      unoptimized
                    />
                  ) : (
                    <div className="flex h-full items-center justify-center">
                      <Package className="h-8 w-8 text-muted-foreground" />
                    </div>
                  )}
                  {priceInfo.hasPromo && (
                    <Badge className="absolute top-1 right-1 bg-red-500 text-white text-xs">
                      <TrendingUp className="h-3 w-3 mr-1" />
                      Promoção
                    </Badge>
                  )}
                </div>
                <div className="p-2 space-y-1">
                  <p className="text-xs font-semibold line-clamp-2 leading-tight">
                    {product.name}
                  </p>
                  <div className="flex items-center gap-1">
                    <span className="text-sm font-bold text-primary">
                      {formatPrice(priceInfo.price)}
                    </span>
                    {priceInfo.original && (
                      <span className="text-xs text-muted-foreground line-through">
                        {formatPrice(priceInfo.original)}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            )
          })}
          </div>
        )}
      </CardContent>
    </Card>
  )
}

