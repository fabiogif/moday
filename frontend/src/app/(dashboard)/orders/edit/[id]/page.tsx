"use client"

import { useState, useEffect } from "react"
import { useParams, useRouter } from "next/navigation"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import { format } from "date-fns"
import { ptBR } from "date-fns/locale"
import {
  ArrowLeft,
  Save,
  Plus,
  Trash2,
  User,
  MapPin,
  Package,
  DollarSign,
  Calendar,
  MessageSquare,
} from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Switch } from "@/components/ui/switch"
import { Separator } from "@/components/ui/separator"
import { Badge } from "@/components/ui/badge"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { PageLoading } from "@/components/ui/loading-progress"
import { useAuthenticatedApi, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { OrderDetails } from "../../types"
import { toast } from "sonner"

// Schema de validação para edição de pedido
const orderEditSchema = z.object({
  status: z.string().min(1, "Status é obrigatório"),
  comment: z.string().optional(),
  is_delivery: z.boolean(),
  use_client_address: z.boolean().optional(),
  delivery_address: z.string().optional(),
  delivery_city: z.string().optional(),
  delivery_state: z.string().optional(),
  delivery_zip_code: z.string().optional(),
  delivery_neighborhood: z.string().optional(),
  delivery_number: z.string().optional(),
  delivery_complement: z.string().optional(),
  delivery_notes: z.string().optional(),
})

type OrderEditFormValues = z.infer<typeof orderEditSchema>

const statusOptions = [
  { value: "Pendente", label: "Pendente" },
  { value: "Em Preparo", label: "Em Preparo" },
  { value: "Pronto", label: "Pronto" },
  { value: "Entregue", label: "Entregue" },
  { value: "Cancelado", label: "Cancelado" },
]

export default function EditOrderPage() {
  const params = useParams()
  const router = useRouter()
  const orderId = params.id as string

  const [order, setOrder] = useState<OrderDetails | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const { data: orderData, loading: apiLoading, error: apiError } = useAuthenticatedApi<OrderDetails>(
    orderId ? endpoints.orders.show(orderId) : ''
  )

  const { mutate: updateOrder, loading: updating } = useMutation()

  const form = useForm<OrderEditFormValues>({
    resolver: zodResolver(orderEditSchema),
    defaultValues: {
      status: "Pendente",
      comment: "",
      is_delivery: false,
      use_client_address: false,
      delivery_address: "",
      delivery_city: "",
      delivery_state: "",
      delivery_zip_code: "",
      delivery_neighborhood: "",
      delivery_number: "",
      delivery_complement: "",
      delivery_notes: "",
    },
  })

  const isDelivery = form.watch("is_delivery")
  const useClientAddress = form.watch("use_client_address")

  useEffect(() => {
    if (orderData) {
      setOrder(orderData)
      
      // Preencher formulário com dados do pedido
      form.reset({
        status: orderData.status || "Pendente",
        comment: orderData.comment || "",
        is_delivery: orderData.is_delivery || false,
        use_client_address: orderData.use_client_address || false,
        delivery_address: orderData.delivery_address || "",
        delivery_city: orderData.delivery_city || "",
        delivery_state: orderData.delivery_state || "",
        delivery_zip_code: orderData.delivery_zip_code || "",
        delivery_neighborhood: orderData.delivery_neighborhood || "",
        delivery_number: orderData.delivery_number || "",
        delivery_complement: orderData.delivery_complement || "",
        delivery_notes: orderData.delivery_notes || "",
      })
      
      setLoading(false)
    }
    if (apiError) {
      setError(apiError)
      setLoading(false)
    }
    if (!apiLoading && !orderData && !apiError) {
      setLoading(false)
    }
  }, [orderData, apiLoading, apiError, form])

  const onSubmit = async (data: OrderEditFormValues) => {
    try {
      const result = await updateOrder(
        endpoints.orders.update(orderId),
        'PUT',
        data
      )

      if (result) {
        toast.success('Pedido atualizado com sucesso!')
        router.push('/orders')
      }
    } catch (error: any) {
      console.error('Erro ao atualizar pedido:', error)
      toast.error(error.message || 'Erro ao atualizar pedido')
    }
  }

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(value)
  }

  const formatDate = (dateString: string) => {
    try {
      if (dateString && dateString.includes('/')) {
        return dateString
      }
      return format(new Date(dateString), "dd/MM/yyyy 'às' HH:mm", { locale: ptBR })
    } catch (error) {
      return dateString || 'Data inválida'
    }
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case "Entregue":
        return "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
      case "Pendente":
        return "text-orange-600 bg-orange-50 dark:text-orange-400 dark:bg-orange-900/20"
      case "Em Preparo":
        return "text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20"
      case "Pronto":
        return "text-purple-600 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/20"
      case "Cancelado":
        return "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
      default:
        return "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20"
    }
  }

  if (!orderId) {
    return (
      <div className="flex flex-col items-center justify-center h-96 space-y-4">
        <div className="text-center">
          <h2 className="text-lg font-semibold mb-2">ID do pedido não informado</h2>
          <p className="text-muted-foreground">Por favor, selecione um pedido para editar.</p>
        </div>
        <Button onClick={() => router.push('/orders')}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Voltar para Pedidos
        </Button>
      </div>
    )
  }

  if (loading || apiLoading) {
    return (
      <PageLoading 
        isLoading={true}
        message="Carregando pedido..."
      />
    )
  }

  if (error || apiError) {
    return (
      <div className="flex flex-col items-center justify-center h-96 space-y-4">
        <div className="text-destructive text-center">
          <h2 className="text-lg font-semibold mb-2">Erro ao carregar pedido</h2>
          <p>{error || apiError}</p>
        </div>
        <Button onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
      </div>
    )
  }

  if (!order) {
    return (
      <div className="flex flex-col items-center justify-center h-96 space-y-4">
        <div className="text-center">
          <h2 className="text-lg font-semibold mb-2">Pedido não encontrado</h2>
          <p className="text-muted-foreground">O pedido solicitado não existe ou foi removido.</p>
        </div>
        <Button onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.back()}
          >
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">
              Editar Pedido #{order.identify || order.orderNumber}
            </h1>
            <p className="text-muted-foreground">
              Edite as informações do pedido
            </p>
          </div>
        </div>
        <Badge className={getStatusColor(order.status)}>
          {order.status}
        </Badge>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Coluna Principal - Formulário */}
        <div className="lg:col-span-2 space-y-6">
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
              {/* Status e Comentários */}
              <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-base font-medium">Status e Observações</CardTitle>
                  <MessageSquare className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent className="space-y-4">
                  <FormField
                    control={form.control}
                    name="status"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Status do Pedido</FormLabel>
                        <Select onValueChange={field.onChange} value={field.value}>
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue placeholder="Selecione o status" />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            {statusOptions.map((status) => (
                              <SelectItem key={status.value} value={status.value}>
                                {status.label}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="comment"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Comentários/Observações</FormLabel>
                        <FormControl>
                          <Textarea
                            placeholder="Adicione observações sobre o pedido..."
                            className="resize-none"
                            {...field}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </CardContent>
              </Card>

              {/* Configurações de Entrega */}
              <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-base font-medium">Configurações de Entrega</CardTitle>
                  <MapPin className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent className="space-y-4">
                  <FormField
                    control={form.control}
                    name="is_delivery"
                    render={({ field }) => (
                      <FormItem className="flex flex-row items-center justify-between rounded-lg border p-3 shadow-sm">
                        <div className="space-y-0.5">
                          <FormLabel>Entrega</FormLabel>
                          <div className="text-sm text-muted-foreground">
                            Marcar se este pedido é para entrega
                          </div>
                        </div>
                        <FormControl>
                          <Switch
                            checked={field.value}
                            onCheckedChange={field.onChange}
                          />
                        </FormControl>
                      </FormItem>
                    )}
                  />

                  {isDelivery && (
                    <>
                      <FormField
                        control={form.control}
                        name="use_client_address"
                        render={({ field }) => (
                          <FormItem className="flex flex-row items-center justify-between rounded-lg border p-3 shadow-sm">
                            <div className="space-y-0.5">
                              <FormLabel>Usar endereço do cliente</FormLabel>
                              <div className="text-sm text-muted-foreground">
                                Usar o endereço cadastrado do cliente
                              </div>
                            </div>
                            <FormControl>
                              <Switch
                                checked={field.value}
                                onCheckedChange={field.onChange}
                              />
                            </FormControl>
                          </FormItem>
                        )}
                      />

                      {!useClientAddress && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <FormField
                            control={form.control}
                            name="delivery_address"
                            render={({ field }) => (
                              <FormItem className="md:col-span-2">
                                <FormLabel>Endereço de Entrega</FormLabel>
                                <FormControl>
                                  <Input placeholder="Rua, Avenida..." {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          <FormField
                            control={form.control}
                            name="delivery_number"
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Número</FormLabel>
                                <FormControl>
                                  <Input placeholder="123" {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          <FormField
                            control={form.control}
                            name="delivery_complement"
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Complemento</FormLabel>
                                <FormControl>
                                  <Input placeholder="Apt, Bloco..." {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          <FormField
                            control={form.control}
                            name="delivery_neighborhood"
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Bairro</FormLabel>
                                <FormControl>
                                  <Input placeholder="Centro" {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          <FormField
                            control={form.control}
                            name="delivery_city"
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Cidade</FormLabel>
                                <FormControl>
                                  <Input placeholder="São Paulo" {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          <FormField
                            control={form.control}
                            name="delivery_state"
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Estado</FormLabel>
                                <FormControl>
                                  <Input placeholder="SP" {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          <FormField
                            control={form.control}
                            name="delivery_zip_code"
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>CEP</FormLabel>
                                <FormControl>
                                  <Input placeholder="01234-567" {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />

                          <FormField
                            control={form.control}
                            name="delivery_notes"
                            render={({ field }) => (
                              <FormItem className="md:col-span-2">
                                <FormLabel>Observações de Entrega</FormLabel>
                                <FormControl>
                                  <Textarea
                                    placeholder="Instruções especiais para entrega..."
                                    className="resize-none"
                                    {...field}
                                  />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />
                        </div>
                      )}
                    </>
                  )}
                </CardContent>
              </Card>

              {/* Botões de Ação */}
              <div className="flex justify-end space-x-4">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => router.back()}
                >
                  Cancelar
                </Button>
                <Button type="submit" disabled={updating}>
                  <Save className="mr-2 h-4 w-4" />
                  {updating ? 'Salvando...' : 'Salvar Alterações'}
                </Button>
              </div>
            </form>
          </Form>
        </div>

        {/* Coluna Lateral - Informações do Pedido */}
        <div className="space-y-6">
          {/* Informações do Cliente */}
          {order.client && (
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-base font-medium">Cliente</CardTitle>
                <User className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent className="space-y-2">
                <div>
                  <p className="font-medium">{order.client.name}</p>
                  <p className="text-sm text-muted-foreground">{order.client.email}</p>
                  <p className="text-sm text-muted-foreground">{order.client.phone}</p>
                </div>
              </CardContent>
            </Card>
          )}

          {/* Informações do Pedido */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-base font-medium">Informações</CardTitle>
              <Calendar className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Data:</span>
                <span className="text-sm font-medium">
                  {formatDate(order.date || order.orderDate || '')}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Tipo:</span>
                <span className="text-sm font-medium">
                  {order.is_delivery ? "Delivery" : "Balcão"}
                </span>
              </div>
              {order.table && (
                <div className="flex justify-between">
                  <span className="text-sm text-muted-foreground">Mesa:</span>
                  <span className="text-sm font-medium">{order.table.name}</span>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Itens do Pedido */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-base font-medium">Itens</CardTitle>
              <Package className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {(order.products || order.items || []).map((item, index) => (
                  <div key={`item-${item.id}-${index}`} className="flex justify-between items-center">
                    <div className="flex-1">
                      <p className="text-sm font-medium">{item.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {item.quantity || 1}x {formatCurrency(item.price)}
                      </p>
                    </div>
                    <p className="text-sm font-medium">
                      {formatCurrency(item.total || (item.price * (item.quantity || 1)))}
                    </p>
                  </div>
                ))}
                <Separator />
                <div className="flex justify-between items-center">
                  <span className="font-medium">Total:</span>
                  <span className="font-bold text-lg">{formatCurrency(order.total)}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}