"use client"

import { useEffect, useState } from "react"
import { Star, TrendingUp, Package } from "lucide-react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import { useAuth } from "@/contexts/auth-context"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"

interface TopProduct {
  rank: number
  id: number
  uuid: string
  name: string
  image: string | null
  price: number
  formatted_price: string
  total_quantity: number
  total_revenue: number
  formatted_revenue: string
  orders_count: number
}

export function TopProducts() {
  const { isAuthenticated, isLoading: authLoading } = useAuth()
  const [products, setProducts] = useState<TopProduct[]>([])
  const [totalRevenue, setTotalRevenue] = useState<string>('')

  // Use authenticated API hook for top products
  const { data: topProductsData, loading, error, refetch } = useAuthenticatedApi<{
    products: TopProduct[]
    formatted_total_revenue: string
  }>(
    '/api/dashboard/top-products',
    { immediate: false }
  )

  useEffect(() => {
    // Aguardar autenticação completa antes de carregar
    if (!authLoading && isAuthenticated) {
      refetch()
    }
  }, [authLoading, isAuthenticated, refetch])

  useEffect(() => {
    if (topProductsData) {
      setProducts(topProductsData.products)
      setTotalRevenue(topProductsData.formatted_total_revenue)
    }
  }, [topProductsData])

  if (authLoading || loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Principais Produtos</CardTitle>
          <CardDescription>Produtos com melhor desempenho neste mês</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="flex items-center gap-4">
                <Skeleton className="h-10 w-10 rounded" />
                <div className="flex-1 space-y-2">
                  <Skeleton className="h-4 w-32" />
                  <Skeleton className="h-3 w-24" />
                </div>
                <Skeleton className="h-6 w-20" />
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Principais Produtos</CardTitle>
          <CardDescription>Produtos com melhor desempenho neste mês</CardDescription>
        </div>
        <Badge variant="outline" className="gap-1">
          <TrendingUp className="h-3 w-3" />
          {totalRevenue}
        </Badge>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {products.length === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-4">Nenhum produto encontrado</p>
          ) : (
            products.map((product) => (
              <div key={product.id} className="flex items-center gap-4">
                <div className="flex items-center justify-center h-10 w-10 rounded bg-muted">
                  {product.image ? (
                    <img src={product.image} alt={product.name} className="h-full w-full object-cover rounded" />
                  ) : (
                    <Package className="h-5 w-5 text-muted-foreground" />
                  )}
                </div>
                <div className="flex-1 space-y-1">
                  <div className="flex items-center gap-2">
                    <Badge variant="outline" className="h-5 w-5 p-0 justify-center text-xs">
                      {product.rank}
                    </Badge>
                    <p className="text-sm font-medium leading-none">{product.name}</p>
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {product.total_quantity} vendidos • {product.orders_count} pedidos
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-sm font-medium">{product.formatted_revenue}</p>
                  <p className="text-xs text-muted-foreground">{product.formatted_price}/un</p>
                </div>
              </div>
            ))
          )}
        </div>
      </CardContent>
    </Card>
  )
}