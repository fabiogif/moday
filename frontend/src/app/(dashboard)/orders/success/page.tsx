'use client'

import { useEffect, useState, Suspense } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { CheckCircle2, Package, MapPin, User, Phone, Mail, ArrowLeft, Download, CreditCard, MessageCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { Badge } from '@/components/ui/badge'
import { OrderStatusTracker } from '@/components/order-status-tracker'
import { PageLoading } from '@/components/ui/loading-progress'
import { apiClient, endpoints } from '@/lib/api-client'
import { toast } from 'sonner'
import type { Order } from '../types'
import {
  getClientPhone,
  normalizeOrderItems,
  openOrderWhatsApp,
  printOrderReceipt,
  toNumber,
} from '../utils/order-receipt'

interface OrderData {
  identify: string
  status: string
  total: number | string
  is_delivery: boolean
  payment_method?: string
  payment_method_name?: string
  client_phone?: string
  comment?: string
  full_delivery_address?: string
  client?: {
    name: string
    email?: string
    phone?: string
  }
  delivery_address?: string
  delivery_city?: string
  delivery_state?: string
  delivery_zip_code?: string
  delivery_neighborhood?: string
  delivery_number?: string
  delivery_complement?: string
  delivery_notes?: string
  products: Array<{
    name: string
    quantity?: number
    qty?: number
    price: number | string
    unit_price?: number
    total?: number
  }>
  created_at: string
  updated_at?: string
}

function OrderSuccessContent() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const orderId = searchParams.get('orderId')

  const [order, setOrder] = useState<OrderData | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!orderId) {
      toast.error('ID do pedido não encontrado')
      router.push('/orders')
      return
    }

    const fetchOrder = async () => {
      try {
        const response = await apiClient.get<any>(endpoints.orders.show(orderId))
        if (response.success && response.data) {
          setOrder(response.data)
        } else {
          throw new Error('Pedido não encontrado')
        }
      } catch (error) {

        toast.error('Não foi possível carregar os detalhes do pedido')
        router.push('/orders')
      } finally {
        setLoading(false)
      }
    }

    fetchOrder()
  }, [orderId, router])

  if (loading) {
    return <PageLoading />
  }

  if (!order) {
    return null
  }

  const orderForReceipt = order as unknown as Order
  const clientPhone = getClientPhone(orderForReceipt)
  const normalizedItems = normalizeOrderItems(orderForReceipt)

  const formatCurrency = (value: number): string =>
    value.toLocaleString('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })

  const handlePrintReceipt = () => {
    const companyName = orderForReceipt.tenant?.name || 'Alba Tec'
    const printed = printOrderReceipt(orderForReceipt, companyName)
    if (!printed) {
      toast.error('Não foi possível imprimir o comprovante. Tente novamente ou use Ctrl+P nesta página.')
      return
    }
    toast.success('Comprovante enviado para impressão')
  }

  const handleSendWhatsApp = () => {
    if (!clientPhone) {
      toast.error('Este pedido não possui telefone do cliente para envio pelo WhatsApp.')
      return
    }

    if (openOrderWhatsApp(orderForReceipt)) {
      toast.success('WhatsApp aberto com o comprovante do pedido')
    }
  }

  const fullAddress = order.is_delivery && order.delivery_address
    ? `${order.delivery_address}${order.delivery_number ? ', ' + order.delivery_number : ''} - ${order.delivery_neighborhood || ''}, ${order.delivery_city || ''} - ${order.delivery_state || ''} ${order.delivery_zip_code ? '- CEP: ' + order.delivery_zip_code : ''}`
    : null

  return (
    <div className="order-success-page min-h-screen bg-gradient-to-br from-emerald-50 via-background to-blue-50 dark:from-emerald-950/20 dark:via-background dark:to-blue-950/20 print:bg-white print:dark:bg-white">
      <div className="order-success-print max-w-4xl mx-auto p-4 lg:p-8 space-y-6 print:max-w-none print:p-0 print:space-y-4">
        {/* Success Header */}
        <div className="text-center space-y-4 py-8 print:py-4 animate-in fade-in-0 zoom-in-95 duration-500 print:animate-none">
          <div className="inline-block animate-in zoom-in-50 duration-500 print:hidden" style={{ animationDelay: '200ms' }}>
            <div className="h-24 w-24 rounded-full bg-emerald-100 dark:bg-emerald-950 flex items-center justify-center mx-auto relative">
              <CheckCircle2 className="h-16 w-16 text-emerald-600 dark:text-emerald-400" />
              <span className="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-30" />
            </div>
          </div>

          <div className="animate-in slide-in-from-bottom-4 duration-500 print:animate-none" style={{ animationDelay: '300ms' }}>
            <h1 className="text-4xl font-bold bg-gradient-to-r from-emerald-600 to-blue-600 bg-clip-text text-transparent print:text-2xl print:text-black print:bg-none print:dark:text-black">
              Pedido Realizado com Sucesso!
            </h1>
            <p className="text-lg text-muted-foreground mt-2 print:text-sm print:text-black">
              Seu pedido foi confirmado e já está sendo processado
            </p>
          </div>

          <div className="flex items-center justify-center gap-2 animate-in slide-in-from-bottom-4 duration-500 print:animate-none" style={{ animationDelay: '400ms' }}>
            <span className="text-sm text-muted-foreground print:text-black">Número do pedido:</span>
            <Badge className="text-lg px-4 py-1.5 font-mono print:text-base print:border print:border-black print:bg-white print:text-black">
              #{order.identify}
            </Badge>
          </div>
        </div>

        {/* Status Tracker - Only for delivery */}
        {order.is_delivery && (
          <div className="animate-in slide-in-from-bottom-4 duration-500 print:animate-none" style={{ animationDelay: '500ms' }}>
            <Card className="print:shadow-none print:border print:break-inside-avoid">
              <CardHeader className="print:px-0 print:py-2">
                <CardTitle className="flex items-center gap-2 print:text-base">
                  <Package className="h-5 w-5 print:hidden" />
                  Status do Pedido
                </CardTitle>
              </CardHeader>
              <CardContent className="print:px-0 print:py-2">
                <OrderStatusTracker
                  currentStatus={order.status}
                  createdAt={order.created_at}
                  updatedAt={order.updated_at}
                  estimatedDelivery="30-45 minutos"
                />
              </CardContent>
            </Card>
          </div>
        )}

        <div className="grid gap-6 lg:grid-cols-2 print:grid-cols-1 print:gap-4">
          {/* Order Details */}
          <div
            className="animate-in slide-in-from-left-4 duration-500"
            style={{ animationDelay: '600ms' }}
          >
            <Card className="print:shadow-none print:border print:break-inside-avoid">
              <CardHeader className="print:px-0 print:py-2">
                <CardTitle className="flex items-center gap-2 print:text-base">
                  <Package className="h-5 w-5 print:hidden" />
                  Detalhes do Pedido
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4 print:px-0 print:py-2">
                {/* Products */}
                <div className="space-y-3">
                  {normalizedItems.map((product, index) => (
                    <div key={index} className="flex justify-between items-start">
                      <div className="flex-1">
                        <div className="flex items-center gap-2">
                          <Badge variant="secondary" className="text-xs">
                            {product.quantity}x
                          </Badge>
                          <span className="text-sm font-medium">{product.name}</span>
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                          Unitário: R$ {formatCurrency(product.unitPrice)}
                        </p>
                      </div>
                      <span className="text-sm font-semibold">
                        R$ {formatCurrency(product.lineTotal)}
                      </span>
                    </div>
                  ))}
                </div>

                <Separator />

                {/* Total */}
                <div className="flex justify-between items-center pt-2">
                  <span className="text-base font-semibold">Total</span>
                  <span className="text-2xl font-bold text-foreground print:text-black">
                    R$ {formatCurrency(toNumber(order.total))}
                  </span>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Customer & Delivery Info */}
          <div
            className="space-y-6 animate-in slide-in-from-right-4 duration-500"
            style={{ animationDelay: '700ms' }}
          >
            {/* Customer Info */}
            {order.client && (
              <Card className="print:shadow-none print:border print:break-inside-avoid">
                <CardHeader className="print:px-0 print:py-2">
                  <CardTitle className="flex items-center gap-2 print:text-base">
                    <User className="h-5 w-5 print:hidden" />
                    Informações do Cliente
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 print:px-0 print:py-2">
                  <div className="flex items-center gap-2 text-sm">
                    <User className="h-4 w-4 text-muted-foreground" />
                    <span>{order.client.name}</span>
                  </div>
                  {order.client.email && (
                    <div className="flex items-center gap-2 text-sm">
                      <Mail className="h-4 w-4 text-muted-foreground" />
                      <span>{order.client.email}</span>
                    </div>
                  )}
                  {order.client.phone && (
                    <div className="flex items-center gap-2 text-sm">
                      <Phone className="h-4 w-4 text-muted-foreground" />
                      <span>{order.client.phone}</span>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}

            {!order.client && clientPhone && (
              <Card className="print:shadow-none print:border print:break-inside-avoid">
                <CardHeader className="print:px-0 print:py-2">
                  <CardTitle className="flex items-center gap-2 print:text-base">
                    <Phone className="h-5 w-5 print:hidden" />
                    Telefone do Cliente
                  </CardTitle>
                </CardHeader>
                <CardContent className="print:px-0 print:py-2">
                  <span className="text-sm">{clientPhone}</span>
                </CardContent>
              </Card>
            )}

            {/* Payment Method */}
            <Card className="print:shadow-none print:border print:break-inside-avoid">
              <CardHeader className="print:px-0 print:py-2">
                <CardTitle className="flex items-center gap-2 print:text-base">
                  <CreditCard className="h-5 w-5 print:hidden" />
                  Forma de Pagamento
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3 print:px-0 print:py-2">
                <div className="flex items-center justify-between text-sm">
                  <span className="text-muted-foreground">Método selecionado</span>
                  <span className="font-semibold">
                    {order.payment_method_name ||
                      order.payment_method ||
                      'Não informado'}
                  </span>
                </div>
              </CardContent>
            </Card>

            {/* Delivery Address */}
            {order.is_delivery && fullAddress && (
              <Card className="print:shadow-none print:border print:break-inside-avoid">
                <CardHeader className="print:px-0 print:py-2">
                  <CardTitle className="flex items-center gap-2 print:text-base">
                    <MapPin className="h-5 w-5 print:hidden" />
                    Endereço de Entrega
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 print:px-0 print:py-2">
                  <p className="text-sm leading-relaxed">
                    {fullAddress}
                  </p>
                  {order.delivery_complement && (
                    <p className="text-sm text-muted-foreground">
                      <strong>Complemento:</strong> {order.delivery_complement}
                    </p>
                  )}
                  {order.delivery_notes && (
                    <div className="p-3 bg-muted/50 rounded-lg">
                      <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1">
                        Observações
                      </p>
                      <p className="text-sm italic">"{order.delivery_notes}"</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}
          </div>
        </div>

        {/* Actions */}
        <div className="no-print flex flex-wrap gap-3 justify-center animate-in slide-in-from-bottom-4 duration-500" style={{ animationDelay: '800ms' }}>
          <Button
            onClick={() => router.push('/orders')}
            variant="outline"
            className="gap-2"
          >
            <ArrowLeft className="h-4 w-4" />
            Ver Todos os Pedidos
          </Button>
          
          <Button
            onClick={() => router.push('/orders/new')}
            className="gap-2"
          >
            <Package className="h-4 w-4" />
            Fazer Novo Pedido
          </Button>

          <Button
            variant="outline"
            className="gap-2"
            onClick={handlePrintReceipt}
          >
            <Download className="h-4 w-4" />
            Imprimir Comprovante
          </Button>

          <Button
            variant="outline"
            className="gap-2"
            onClick={handleSendWhatsApp}
            disabled={!clientPhone}
            title={clientPhone ? 'Enviar comprovante pelo WhatsApp do cliente' : 'Pedido sem telefone cadastrado'}
          >
            <MessageCircle className="h-4 w-4" />
            Enviar WhatsApp
          </Button>
        </div>
      </div>
    </div>
  )
}

export default function OrderSuccessPage() {
  return (
    <Suspense fallback={<PageLoading />}>
      <OrderSuccessContent />
    </Suspense>
  )
}

