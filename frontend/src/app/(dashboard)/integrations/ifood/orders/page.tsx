"use client"

import { useEffect, useMemo, useState } from "react"
import { apiClient, endpoints } from "@/lib/api-client"
import { useAuth } from "@/contexts/auth-context"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Separator } from "@/components/ui/separator"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Loader2, RefreshCcw, ShoppingBag } from "lucide-react"

interface IfoodOrderListResponse {
  data: any[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

const STATUS_OPTIONS = [
  { value: "CONFIRMED", label: "Confirmado" },
  { value: "IN_PREPARATION", label: "Em preparação" },
  { value: "READY_TO_PICKUP", label: "Pronto para retirada" },
  { value: "OUT_FOR_DELIVERY", label: "Saiu para entrega" },
  { value: "COMPLETED", label: "Concluído" },
  { value: "CANCELLED", label: "Cancelado" },
]

type IfoodOrderDetail = {
  id: string
  [key: string]: any
}

function formatCurrency(value?: number | null): string {
  if (value === undefined || value === null) {
    return "-"
  }
  return value.toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
  })
}

function formatDate(value?: string | null): string {
  if (!value) {
    return "-"
  }
  return new Date(value).toLocaleString("pt-BR")
}

export default function IfoodOrdersPage() {
  const { user } = useAuth()
  const tenantId = useMemo(() => user?.tenant_id ?? null, [user])

  const [orders, setOrders] = useState<any[]>([])
  const [pagination, setPagination] = useState<IfoodOrderListResponse["meta"] | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)

  const [selectedOrder, setSelectedOrder] = useState<IfoodOrderDetail | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)

  const [statusForm, setStatusForm] = useState({
    status: "",
    reason: "",
    description: "",
  })
  const [resendLoading, setResendLoading] = useState(false)
  const [confirmLoading, setConfirmLoading] = useState(false)

  const loadOrders = async (page = 1) => {
    if (!tenantId) {
      return
    }

    setLoading(true)
    setError(null)
    setSuccessMessage(null)

    try {
      const response = await apiClient.get<IfoodOrderListResponse>(
        endpoints.integrations.ifood.orders.list,
        {
          tenant_id: tenantId,
          per_page: 10,
          page,
        }
      )

      setOrders(response.data?.data ?? [])
      setPagination(response.data?.meta ?? null)
    } catch (err: any) {
      setError(err?.message || "Não foi possível carregar os pedidos iFood.")
      setOrders([])
      setPagination(null)
    } finally {
      setLoading(false)
    }
  }

  const loadOrderDetail = async (externalOrderId: string) => {
    if (!tenantId) {
      return
    }

    setSelectedOrder((previous: IfoodOrderDetail | null) =>
      previous && previous.id === externalOrderId ? previous : { id: externalOrderId }
    )
    setDetailLoading(true)
    setError(null)

    try {
      const response = await apiClient.get<any>(
        endpoints.integrations.ifood.orders.show(externalOrderId),
        {
          tenant_id: tenantId,
        }
      )

      setSelectedOrder(response.data ?? null)
      setStatusForm({
        status: "",
        reason: "",
        description: "",
      })
    } catch (err: any) {
      setError(err?.message || "Não foi possível carregar o detalhe do pedido.")
    } finally {
      setDetailLoading(false)
    }
  }

  const handleConfirmOrder = async () => {
    if (!selectedOrder || !tenantId) {
      return
    }

    setConfirmLoading(true)
    setError(null)
    setSuccessMessage(null)

    try {
      await apiClient.post(endpoints.integrations.ifood.orders.confirm(selectedOrder.id), {
        tenant_id: tenantId,
      })

      setSuccessMessage("Pedido confirmado no iFood com sucesso.")
      await loadOrders(pagination?.current_page ?? 1)
      await loadOrderDetail(selectedOrder.id)
    } catch (err: any) {
      setError(err?.message || "Falha ao confirmar o pedido no iFood.")
    } finally {
      setConfirmLoading(false)
    }
  }

  const handleResendStatus = async () => {
    if (!selectedOrder || !tenantId || !statusForm.status) {
      setError("Selecione um status para reenviar.")
      return
    }

    setResendLoading(true)
    setError(null)
    setSuccessMessage(null)

    try {
      await apiClient.post(
        endpoints.integrations.ifood.orders.resendStatus(selectedOrder.id),
        {
          tenant_id: tenantId,
          status: statusForm.status,
          reason: statusForm.reason || undefined,
          description: statusForm.description || undefined,
        }
      )

      setSuccessMessage("Status reenviado ao iFood com sucesso.")
      await loadOrders(pagination?.current_page ?? 1)
      await loadOrderDetail(selectedOrder.id)
    } catch (err: any) {
      setError(err?.message || "Falha ao reenviar status para o iFood.")
    } finally {
      setResendLoading(false)
    }
  }

  useEffect(() => {
    if (tenantId) {
      loadOrders()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tenantId])

  return (
    <div className="space-y-6 px-4 lg:px-6">
      <div className="flex flex-col gap-2">
        <h1 className="text-3xl font-bold flex items-center gap-2">
          <ShoppingBag className="h-7 w-7" />
          Pedidos iFood
        </h1>
        <p className="text-muted-foreground">
          Acompanhe os pedidos recebidos pela integração iFood, visualize detalhes e reenviar atualizações de status diretamente para a plataforma.
        </p>
      </div>

      <Alert>
        <AlertTitle>Integração com fonte externa</AlertTitle>
        <AlertDescription>
          Os dados desta tela são sincronizados com a API oficial do iFood. É necessário que o tenant possua credenciais válidas e webhook configurado.
        </AlertDescription>
      </Alert>

      <Card>
        <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <CardTitle>Pedidos sincronizados</CardTitle>
            <CardDescription>
              Listagem paginada dos pedidos armazenados na base local a partir do fluxo de ingestão iFood.
            </CardDescription>
          </div>
          <Button
            variant="outline"
            onClick={() => loadOrders(pagination?.current_page ?? 1)}
            disabled={loading}
          >
            {loading ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <RefreshCcw className="mr-2 h-4 w-4" />
            )}
            Atualizar
          </Button>
        </CardHeader>
        <CardContent className="space-y-4">
          {error && <p className="text-sm text-destructive font-medium">{error}</p>}
          {successMessage && (
            <p className="text-sm text-muted-foreground">{successMessage}</p>
          )}

          <div className="rounded-md border overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>ID</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Canal</TableHead>
                  <TableHead>Total</TableHead>
                  <TableHead>Recebido em</TableHead>
                  <TableHead>Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {orders.length === 0 && !loading ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-sm text-muted-foreground py-6">
                      Nenhum pedido encontrado. 
                    </TableCell>
                  </TableRow>
                ) : (
                  orders.map((order) => (
                    <TableRow key={order.id}>
                      <TableCell className="font-medium">{order.display_id ?? order.id}</TableCell>
                      <TableCell>
                        <Badge variant="outline">{order.status}</Badge>
                      </TableCell>
                      <TableCell>{order.sales_channel ?? "-"}</TableCell>
                      <TableCell>
                        {formatCurrency(order?.totals?.order_amount ?? null)}
                      </TableCell>
                      <TableCell>
                        {formatDate(order?.timestamps?.received_at)}
                      </TableCell>
                      <TableCell>
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={() => loadOrderDetail(order.id)}
                        >
                          Detalhes
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>

          {pagination && (
            <div className="flex items-center justify-between text-xs text-muted-foreground">
              <span>
                Página {pagination.current_page} de {pagination.last_page} • Total: {pagination.total}
              </span>
              <div className="flex gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  disabled={pagination.current_page <= 1 || loading}
                  onClick={() => loadOrders(pagination.current_page - 1)}
                >
                  Anterior
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  disabled={pagination.current_page >= pagination.last_page || loading}
                  onClick={() => loadOrders(pagination.current_page + 1)}
                >
                  Próxima
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      <Dialog open={!!selectedOrder} onOpenChange={(open) => !open && setSelectedOrder(null)}>
        <DialogContent className="max-w-4xl">
          <DialogHeader>
            <DialogTitle>
              Pedido {selectedOrder?.display_id ?? selectedOrder?.id ?? "-"}
            </DialogTitle>
            <DialogDescription>
              Dados completos do pedido sincronizado via integração iFood.
            </DialogDescription>
          </DialogHeader>

          {detailLoading ? (
            <div className="flex items-center justify-center py-10">
              <Loader2 className="h-6 w-6 animate-spin" />
            </div>
          ) : selectedOrder ? (
            <div className="space-y-6">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <h3 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                    Status
                  </h3>
                  <div className="flex flex-wrap gap-2 items-center">
                    <Badge variant="default">{selectedOrder.status}</Badge>
                    {selectedOrder.order_type && (
                      <Badge variant="outline">{selectedOrder.order_type}</Badge>
                    )}
                    {selectedOrder.order_timing && (
                      <Badge variant="outline">{selectedOrder.order_timing}</Badge>
                    )}
                  </div>
                  <p className="text-sm text-muted-foreground">
                    Recebido em {formatDate(selectedOrder?.timestamps?.received_at)}
                  </p>
                </div>
                <div className="space-y-2">
                  <h3 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                    Totais
                  </h3>
                  <div className="text-sm">
                    <p>Pedido: {formatCurrency(selectedOrder?.totals?.order_amount)}</p>
                    <p>Itens: {formatCurrency(selectedOrder?.totals?.items_total)}</p>
                    <p>Entrega: {formatCurrency(selectedOrder?.totals?.delivery_fee)}</p>
                  </div>
                </div>
              </div>

              <Separator />

              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <h3 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                    Cliente
                  </h3>
                  <div className="text-sm">
                    <p>{selectedOrder?.customer?.name ?? "-"}</p>
                    <p className="text-muted-foreground">
                      {selectedOrder?.customer?.phone ?? "-"}
                    </p>
                    {selectedOrder?.addresses?.delivery?.street && (
                      <p className="text-muted-foreground text-xs">
                        Entrega: {selectedOrder.addresses.delivery.street},{" "}
                        {selectedOrder.addresses.delivery.number} -{" "}
                        {selectedOrder.addresses.delivery.district}
                      </p>
                    )}
                  </div>
                </div>
                <div className="space-y-2">
                  <h3 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                    Pedido interno
                  </h3>
                  {selectedOrder?.internal_order ? (
                    <div className="text-sm">
                      <p>ID interno: {selectedOrder.internal_order.id}</p>
                      <p>Status: {selectedOrder.internal_order.status}</p>
                      <p>Total: {formatCurrency(selectedOrder.internal_order.total)}</p>
                    </div>
                  ) : (
                    <p className="text-sm text-muted-foreground">
                      Ainda não há pedido interno vinculado.
                    </p>
                  )}
                </div>
              </div>

              <Separator />

              <div className="space-y-3">
                <h3 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                  Itens
                </h3>
                <div className="rounded-md border overflow-hidden">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Item</TableHead>
                        <TableHead>Quantidade</TableHead>
                        <TableHead>Total</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {selectedOrder?.items?.length ? (
                        selectedOrder.items.map((item: any) => (
                          <TableRow key={item.unique_id ?? item.id}>
                            <TableCell>
                              <div className="flex flex-col">
                                <span className="font-medium">{item.name}</span>
                                {item.observations && (
                                  <span className="text-xs text-muted-foreground">
                                    {item.observations}
                                  </span>
                                )}
                              </div>
                            </TableCell>
                            <TableCell>{item.quantity}</TableCell>
                            <TableCell>{formatCurrency(item.total_price)}</TableCell>
                          </TableRow>
                        ))
                      ) : (
                        <TableRow>
                          <TableCell colSpan={3} className="text-center text-sm text-muted-foreground py-4">
                            Nenhum item associado ao pedido.
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </div>
              </div>

              <Separator />

              <div className="space-y-3">
                <h3 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                  Histórico de status
                </h3>
                <div className="rounded-md border bg-muted/30 p-3 space-y-2 text-sm">
                  {selectedOrder?.status_logs?.length ? (
                    selectedOrder.status_logs.map((log: any) => (
                      <div key={log.id} className="flex flex-col">
                        <span className="font-medium">{log.status}</span>
                        <span className="text-xs text-muted-foreground">
                          {formatDate(log.created_at)}
                          {log.reason ? ` • ${log.reason}` : ""}
                        </span>
                      </div>
                    ))
                  ) : (
                    <p className="text-sm text-muted-foreground">
                      Nenhum log registrado até o momento.
                    </p>
                  )}
                </div>
              </div>

              <Separator />

              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-3">
                  <h3 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                    Reenviar status
                  </h3>
                  <Select
                    value={statusForm.status}
                    onValueChange={(value) =>
                      setStatusForm((prev) => ({ ...prev, status: value }))
                    }
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione o novo status" />
                    </SelectTrigger>
                    <SelectContent>
                      {STATUS_OPTIONS.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                          {option.label} ({option.value})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <Input
                    placeholder="Motivo (opcional)"
                    value={statusForm.reason}
                    onChange={(event) =>
                      setStatusForm((prev) => ({
                        ...prev,
                        reason: event.target.value,
                      }))
                    }
                  />
                  <Textarea
                    placeholder="Descrição (opcional)"
                    value={statusForm.description}
                    onChange={(event) =>
                      setStatusForm((prev) => ({
                        ...prev,
                        description: event.target.value,
                      }))
                    }
                    rows={3}
                  />
                  <Button
                    onClick={handleResendStatus}
                    disabled={resendLoading}
                  >
                    {resendLoading && (
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    )}
                    Reenviar status
                  </Button>
                </div>
                <div className="space-y-3">
                  <h3 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                    Operações
                  </h3>
                  <Button
                    variant="secondary"
                    onClick={handleConfirmOrder}
                    disabled={confirmLoading}
                  >
                    {confirmLoading && (
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    )}
                    Confirmar no iFood
                  </Button>
                  <p className="text-xs text-muted-foreground">
                    Utilize este botão para confirmar o pedido diretamente no iFood quando ainda estiver pendente de confirmação.
                  </p>
                </div>
              </div>
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">Selecione um pedido para visualizar os detalhes.</p>
          )}
        </DialogContent>
      </Dialog>
    </div>
  )
}

