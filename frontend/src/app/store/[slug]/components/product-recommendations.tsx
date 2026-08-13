"use client"

import { useMemo, useState, useEffect } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Sparkles, TrendingUp, Package } from "lucide-react"
import Image from "next/image"
import { toast } from "sonner"
import { resolveImageUrl } from "@/lib/resolve-image-url"

interface Product {
  uuid: string
  name: string
  price: number | string
  promotional_price?: number | string
  image?: string
  image_url?: string
  description?: string
  categories?: Array<{ uuid: string; name: string }>
}

interface CartItem {
  uuid: string
  name: string
  price: number | string
  promotional_price?: number | string
  categories?: Array<{ uuid: string; name: string }>
}

interface ProductRecommendationsProps {
  cart: CartItem[]
  allProducts: Product[]
  onAddProduct: (product: Product) => void
}

/**
 * Componente de Recomendações de Produtos para Cardápio Público
 * 
 * Versão simplificada que funciona sem autenticação, usando lógica local:
 * - Produtos da mesma categoria dos itens no carrinho
 * - Produtos com preços promocionais
 * - Produtos mais populares (baseado em categorias)
 */
export function ProductRecommendations({
  cart,
  allProducts,
  onAddProduct,
}: ProductRecommendationsProps) {
  const [recommendedProducts, setRecommendedProducts] = useState<Product[]>([])

  // Calcular recomendações baseadas no carrinho
  const recommendations = useMemo(() => {
    if (cart.length === 0 || allProducts.length === 0) {
      return []
    }

    // Extrair categorias dos produtos no carrinho
    const cartCategories = new Set<string>()
    cart.forEach(item => {
      if (item.categories && item.categories.length > 0) {
        item.categories.forEach(cat => cartCategories.add(cat.uuid))
      }
    })

    // IDs dos produtos já no carrinho
    const cartProductIds = new Set(cart.map(item => item.uuid))

    // Produtos candidatos (não estão no carrinho)
    const candidates: Array<{ product: Product; score: number }> = []

    allProducts.forEach(product => {
      // Pular se já está no carrinho
      if (cartProductIds.has(product.uuid)) {
        return
      }

      let score = 0

      // Pontuação por categoria (produtos da mesma categoria)
      if (product.categories && product.categories.length > 0) {
        product.categories.forEach(cat => {
          if (cartCategories.has(cat.uuid)) {
            score += 10 // Produtos da mesma categoria ganham pontos
          }
        })
      }

      // Pontuação por promoção (produtos em promoção)
      if (product.promotional_price) {
        const promoPrice = typeof product.promotional_price === 'string' 
          ? parseFloat(product.promotional_price) 
          : product.promotional_price
        const regularPrice = typeof product.price === 'string' 
          ? parseFloat(product.price) 
          : product.price
        
        if (promoPrice < regularPrice) {
          score += 5 // Produtos em promoção ganham pontos
        }
      }

      // Se tiver algum score, adicionar aos candidatos
      if (score > 0) {
        candidates.push({ product, score })
      }
    })

    // Ordenar por score e retornar top 6
    return candidates
      .sort((a, b) => b.score - a.score)
      .slice(0, 6)
      .map(c => c.product)
  }, [cart, allProducts])

  // Atualizar recomendações quando mudar
  useEffect(() => {
    setRecommendedProducts(recommendations)
  }, [recommendations])

  // Não mostrar se não houver recomendações ou se o carrinho estiver vazio
  if (recommendedProducts.length === 0 || cart.length === 0) {
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
          Produtos que combinam com seu pedido
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-primary/20 scrollbar-track-transparent">
          {recommendedProducts.map((product) => {
            const priceInfo = getProductPrice(product)
            const imageUrl =
              resolveImageUrl(product.image) ||
              resolveImageUrl(product.image_url)

            return (
              <div
                key={product.uuid}
                className="flex-shrink-0 w-32 sm:w-40 rounded-xl border bg-card hover:border-primary/50 transition-all cursor-pointer group"
                onClick={() => onAddProduct(product)}
              >
                <div className="relative h-24 sm:h-32 w-full overflow-hidden rounded-t-xl bg-muted">
                  {imageUrl ? (
                    <Image
                      src={imageUrl}
                      alt={product.name}
                      fill
                      className="object-cover transition-transform duration-300 ease-out group-hover:scale-110"
                      sizes="(max-width: 640px) 128px, 160px"
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
      </CardContent>
    </Card>
  )
}

