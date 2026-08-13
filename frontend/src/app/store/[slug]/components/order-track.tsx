"use client"

import { useEffect, useState } from "react"
import { Search, Loader2, Package, CheckCircle2, Clock, Truck, XCircle } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import { toast } from "sonner"
import { maskPhone } from '@/lib/masks'
import { apiClient, endpoints } from '@/lib/api-client'
import { ReviewModal, type ReviewData } from './review-modal'

interface OrderProduct {
  name: string
  quantity: number
  price: number
}

interface OrderTrackingResult {
  client_name: string
  order_identify: string
  order_date: string
  order_time: string
  status: string
  is_delivery: boolean
  total: number
  products: OrderProduct[]
  payment_method: string
}

interface OrderTrackProps {
  slug: string
}

interface StoreInfoData {
  id?: number
  tenant_id?: number
  name?: string
  email?: string
  phone?: string
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'Pendente':
      return <Clock className="h-5 w-5 text-yellow-600" />
    case 'Aceito':
      return <Package className="h-5 w-5 text-indigo-600" />
    case 'Preparo':
      return <Clock className="h-5 w-5 text-blue-600" />
    case 'Concluído':
      return <CheckCircle2 className="h-5 w-5 text-green-600" />
    case 'Cancelado':
      return <XCircle className="h-5 w-5 text-red-600" />
    default:
      return <Package className="h-5 w-5 text-gray-600" />
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'Pendente':
      return 'bg-yellow-100 text-yellow-800'
    case 'Aceito':
      return 'bg-indigo-100 text-indigo-800'
    case 'Preparo':
      return 'bg-blue-100 text-blue-800'
    case 'Concluído':
      return 'bg-green-100 text-green-800'
    case 'Cancelado':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

export function OrderTrack({ slug }: OrderTrackProps) {
  const [phone, setPhone] = useState('')
  const [loading, setLoading] = useState(false)
  const [orderData, setOrderData] = useState<OrderTrackingResult | null>(null)
  const [storeInfo, setStoreInfo] = useState<StoreInfoData | null>(null)
  const [showReviewModal, setShowReviewModal] = useState(false)

  useEffect(() => {
    const loadStoreInfo = async () => {
      try {
        const response = await apiClient.get<StoreInfoData>(endpoints.store.info(slug))

        if (response.success) {
          setStoreInfo(response.data)
        }
      } catch (error) {

      }
    }

    loadStoreInfo()
  }, [slug])

  const canOpenReview = Boolean(orderData)

  const handleOpenReview = () => {
    if (!orderData) return

    if (!storeInfo || !(storeInfo.tenant_id || storeInfo.id)) {
      toast.error('Não foi possível carregar os dados da loja. Tente novamente em instantes.')
      return
    }

    setShowReviewModal(true)
  }

  const handleReviewSubmit = async (reviewData: ReviewData) => {
    if (!orderData) {
      throw new Error('Pedido não encontrado para avaliação.')
    }

    const tenantId = storeInfo?.tenant_id || storeInfo?.id

    if (!tenantId) {
      throw new Error('Não foi possível identificar a loja deste pedido.')
    }

    try {
      const response = await apiClient.post(endpoints.reviews.public.create, {
        tenant_id: tenantId,
        order_identify: orderData.order_identify,
        rating: reviewData.rating,
        comment: reviewData.comment,
        terms_accepted: reviewData.terms_accepted,
        customer_name: reviewData.customer_name || orderData.client_name,
        customer_email: reviewData.customer_email,
      })

      if (!response.success) {
        throw new Error(response.message || 'Erro ao enviar avaliação. Tente novamente.')
      }
    } catch (error: any) {
      const message = error?.response?.data?.message || error?.message || 'Erro ao enviar avaliação. Tente novamente.'
      throw new Error(message)
    }
  }

  const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const masked = maskPhone(e.target.value)
    setPhone(masked)
  }

  const handleSearch = async () => {
    if (!phone) {
      toast.error('Informe o telefone para consultar')
      return
    }

    setLoading(true)
    setOrderData(null)

    try {
      const response = await apiClient.get(
        endpoints.store.trackOrder(slug),
        { phone }
      )

      if (response.success && response.data) {
        setOrderData(response.data as OrderTrackingResult)
        toast.success('Pedido encontrado!')
      } else {
        toast.error(response.message || 'Nenhum pedido em andamento foi encontrado')
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || 
                          'Nenhum pedido em andamento foi encontrado para este telefone.'
      toast.error(errorMessage)
    } finally {
      setLoading(false)
    }
  }

  return (
    <>
      <Card className="w-full max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Package className="h-6 w-6" />
            Acompanhar Pedido
          </CardTitle>
          <CardDescription>
            Informe seu telefone para consultar o status do seu pedido
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="space-y-2">
            <Label htmlFor="phone">Telefone *</Label>
            <Input
              id="phone"
              type="text"
              placeholder="(00) 00000-0000"
              value={phone}
              onChange={handlePhoneChange}
              maxLength={15}
              disabled={loading}
              required
            />
          </div>

          <Button 
            onClick={handleSearch} 
            disabled={loading || !phone}
            className="w-full"
          >
            {loading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Consultando...
              </>
            ) : (
              <>
                <Search className="mr-2 h-4 w-4" />
                Consultar Pedido
              </>
            )}
          </Button>

          {orderData && (
            <>
              <Separator />
              <div className="space-y-4">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Pedido</p>
                    <p className="text-xl font-semibold">#{orderData.order_identify}</p>
                  </div>
                  <Badge className={getStatusColor(orderData.status)}>
                    <span className="flex items-center gap-1">
                      {getStatusIcon(orderData.status)}
                      {orderData.status}
                    </span>
                  </Badge>
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                  <div>
                    <p className="text-sm text-muted-foreground">Cliente</p>
                    <p className="font-medium">Olá, {orderData.client_name}!</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Data e Hora</p>
                    <p className="font-medium">
                      {orderData.order_date} às {orderData.order_time}
                    </p>
                  </div>
                </div>

                {orderData.is_delivery && (
                  <div className="flex items-center gap-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <Truck className="h-5 w-5 text-blue-600" />
                    <p className="text-sm text-blue-800 font-medium">
                      Pedido com entrega
                    </p>
                  </div>
                )}

                {/* Produtos do Pedido */}
                <div className="space-y-3">
                  <h3 className="font-semibold text-lg">Itens do Pedido</h3>
                  <div className="border rounded-lg divide-y">
                    {orderData.products.map((product, index) => (
                      <div key={index} className="p-3 flex justify-between items-center">
                        <div className="flex-1">
                          <p className="font-medium">{product.name}</p>
                          <p className="text-sm text-muted-foreground">
                            Quantidade: {product.quantity}
                          </p>
                        </div>
                        <div className="text-right">
                          <p className="font-semibold">
                            R$ {(product.price * product.quantity).toFixed(2).replace('.', ',')}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            R$ {product.price.toFixed(2).replace('.', ',')} cada
                          </p>
                        </div>
                      </div>
                    ))}
                    
                    {/* Total */}
                    <div className="p-3 bg-muted flex justify-between items-center font-bold">
                      <span>Total</span>
                      <span className="text-lg">
                        R$ {orderData.total.toFixed(2).replace('.', ',')}
                      </span>
                    </div>
                  </div>

                  {/* Forma de Pagamento */}
                  <div className="flex items-center justify-between p-3 border rounded-lg bg-green-50 border-green-200">
                    <span className="text-sm font-medium text-green-800">Forma de Pagamento</span>
                    <span className="font-semibold text-green-900">{orderData.payment_method}</span>
                  </div>
                </div>

                <div className="p-4 bg-muted rounded-lg">
                  <p className="text-sm text-muted-foreground mb-2">Status atual</p>
                  <p className="text-base">
                    {orderData.status === 'Pendente' &&
                      'Seu pedido foi recebido e aguarda aceite da loja!'}
                    {orderData.status === 'Aceito' &&
                      'Seu pedido foi aceito e logo entrará em preparo!'}
                    {orderData.status === 'Preparo' &&
                      'Seu pedido está sendo preparado com todo carinho e ' +
                      (orderData.is_delivery ? 'será entregue em breve!' : 'logo estará pronto para retirada!')}
                    {orderData.status === 'Concluído' &&
                      'Seu pedido foi concluído! Esperamos que tenha gostado!'}
                    {orderData.status === 'Cancelado' &&
                      'Este pedido foi cancelado.'}
                  </p>
                </div>

                <div className="space-y-3 p-4 border rounded-lg bg-muted/50">
                  <div>
                    <h3 className="font-semibold text-lg">Avalie seu Pedido</h3>
                    <p className="text-sm text-muted-foreground">
                      Conte como foi a sua experiência e ajude a loja a melhorar cada vez mais.
                    </p>
                  </div>

                  <Button
                    className="w-full sm:w-auto"
                    onClick={handleOpenReview}
                    disabled={!canOpenReview}
                  >
                    <span className="flex items-center gap-2">
                      <span aria-hidden="true" role="img">⭐</span>
                      Avaliar Pedido
                    </span>
                  </Button>
                </div>
              </div>
            </>
          )}
        </CardContent>
      </Card>
      {orderData && (
        <ReviewModal
          isOpen={showReviewModal}
          onClose={() => setShowReviewModal(false)}
          onSubmit={handleReviewSubmit}
          orderData={{ id: 0, identify: orderData.order_identify }}
          tenantId={(storeInfo?.tenant_id || storeInfo?.id || 0)}
          customerData={{ name: orderData.client_name }}
        />
      )}
    </>
  )
}

export default OrderTrack

