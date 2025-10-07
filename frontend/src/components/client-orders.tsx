"use client"

import { useEffect, useState } from 'react'
import { useClientAuth } from '@/contexts/client-auth-context'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Package, Calendar, MapPin, CreditCard, Truck, ShoppingBag } from 'lucide-react'
import { toast } from 'sonner'

interface OrderProduct {
  uuid: string
  name: string
  price: number
  quantity: number
  subtotal: number
  image?: string
}

interface Order {
  id: number
  identify: string
  total: number
  formatted_total: string
  status: string
  origin: string
  is_delivery: boolean
  delivery_address?: string
  delivery_city?: string
  delivery_state?: string
  payment_method?: string
  shipping_method?: string
  created_at: string
  created_at_human: string
  products: OrderProduct[]
  table?: {
    name: string
    uuid: string
  }
}

interface ClientOrdersProps {
  slug: string
}

export function ClientOrders({ slug }: ClientOrdersProps) {
  const { token, isAuthenticated, client } = useClientAuth()
  const [orders, setOrders] = useState<Order[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (isAuthenticated && token) {
      loadOrders()
    } else {
      setLoading(false)
    }
  }, [isAuthenticated, token])

  async function loadOrders() {
    try {
      setLoading(true)
      
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL}/api/store/${slug}/orders`,
        {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        }
      )

      const data = await response.json()

      if (data.success) {
        setOrders(data.data.orders)
      } else {
        toast.error(data.message || 'Erro ao carregar pedidos')
      }
    } catch (error) {
      console.error('Error loading orders:', error)
      toast.error('Erro ao carregar pedidos')
    } finally {
      setLoading(false)
    }
  }

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      'Pendente': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
      'Em Preparo': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
      'Pronto': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
      'Entregue': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
      'Cancelado': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
  }

  const getPaymentMethodLabel = (method?: string) => {
    const labels: Record<string, string> = {
      'pix': 'PIX',
      'credit_card': 'Cartão de Crédito',
      'debit_card': 'Cartão de Débito',
      'money': 'Dinheiro',
      'bank_transfer': 'Transferência Bancária',
    }
    return method ? labels[method] || method : 'Não informado'
  }

  if (!isAuthenticated) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Meus Pedidos</CardTitle>
          <CardDescription>
            Faça login para ver seus pedidos
          </CardDescription>
        </CardHeader>
      </Card>
    )
  }

  if (loading) {
    return (
      <div className="space-y-4">
        <Card>
          <CardHeader>
            <Skeleton className="h-6 w-48" />
            <Skeleton className="h-4 w-64" />
          </CardHeader>
        </Card>
        {[1, 2, 3].map((i) => (
          <Card key={i}>
            <CardHeader>
              <Skeleton className="h-5 w-32" />
            </CardHeader>
            <CardContent>
              <Skeleton className="h-20 w-full" />
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  if (orders.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Meus Pedidos</CardTitle>
          <CardDescription>
            Você ainda não fez nenhum pedido
          </CardDescription>
        </CardHeader>
        <CardContent className="flex flex-col items-center justify-center py-8">
          <ShoppingBag className="h-16 w-16 text-muted-foreground mb-4" />
          <p className="text-muted-foreground">
            Nenhum pedido encontrado
          </p>
        </CardContent>
      </Card>
    )
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Meus Pedidos</h2>
          <p className="text-muted-foreground">
            Total de {orders.length} pedido{orders.length !== 1 ? 's' : ''}
          </p>
        </div>
      </div>

      <div className="space-y-4">
        {orders.map((order) => (
          <Card key={order.id}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Package className="h-5 w-5" />
                  <CardTitle className="text-lg">Pedido #{order.identify}</CardTitle>
                </div>
                <Badge className={getStatusColor(order.status)}>
                  {order.status}
                </Badge>
              </div>
              <CardDescription className="flex items-center gap-2">
                <Calendar className="h-4 w-4" />
                {order.created_at} ({order.created_at_human})
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Products */}
              <div>
                <h4 className="font-semibold mb-2">Produtos:</h4>
                <div className="space-y-2">
                  {order.products.map((product, index) => (
                    <div key={index} className="flex justify-between items-center py-2 border-b last:border-0">
                      <div className="flex items-center gap-3">
                        {product.image && (
                          <div className="h-12 w-12 rounded overflow-hidden bg-muted flex-shrink-0">
                            <img 
                              src={product.image} 
                              alt={product.name}
                              className="h-full w-full object-cover"
                            />
                          </div>
                        )}
                        <div>
                          <p className="font-medium">{product.name}</p>
                          <p className="text-sm text-muted-foreground">
                            {product.quantity}x R$ {product.price.toFixed(2)}
                          </p>
                        </div>
                      </div>
                      <p className="font-semibold">
                        R$ {product.subtotal.toFixed(2)}
                      </p>
                    </div>
                  ))}
                </div>
              </div>

              {/* Delivery Info */}
              {order.is_delivery && order.delivery_address && (
                <div className="flex items-start gap-2 text-sm">
                  <MapPin className="h-4 w-4 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="font-semibold">Endereço de Entrega:</p>
                    <p className="text-muted-foreground">
                      {order.delivery_address}, {order.delivery_city}/{order.delivery_state}
                    </p>
                  </div>
                </div>
              )}

              {/* Table Info */}
              {order.table && (
                <div className="flex items-center gap-2 text-sm">
                  <Package className="h-4 w-4" />
                  <p>
                    <span className="font-semibold">Mesa:</span> {order.table.name}
                  </p>
                </div>
              )}

              {/* Payment Method */}
              {order.payment_method && (
                <div className="flex items-center gap-2 text-sm">
                  <CreditCard className="h-4 w-4" />
                  <p>
                    <span className="font-semibold">Pagamento:</span>{' '}
                    {getPaymentMethodLabel(order.payment_method)}
                  </p>
                </div>
              )}

              {/* Shipping Method */}
              {order.shipping_method && (
                <div className="flex items-center gap-2 text-sm">
                  <Truck className="h-4 w-4" />
                  <p>
                    <span className="font-semibold">Entrega:</span>{' '}
                    {order.shipping_method === 'delivery' ? 'Entrega' : 'Retirada'}
                  </p>
                </div>
              )}

              {/* Total */}
              <div className="flex justify-between items-center pt-4 border-t">
                <p className="font-semibold text-lg">Total:</p>
                <p className="font-bold text-xl">{order.formatted_total}</p>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
