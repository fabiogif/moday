"use client"

import { useState, useEffect, useCallback } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"
import { OrderDetailsDialog } from "./components/order-details-dialog"
import { ReceiptDialog } from "./components/receipt-dialog"
import { useAuthenticatedOrders, useMutation } from "@/hooks/use-authenticated-api"
import { useOrderRefresh } from "@/hooks/use-order-refresh"
import { apiClient, endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"
import { Order } from "./types"
import { Button } from "@/components/ui/button"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Plus, CheckCircle2 } from "lucide-react"
import { useRouter, useSearchParams } from "next/navigation"
import { toast } from "sonner"
import { useAuth } from "@/contexts/auth-context"
import { canEditOrder } from "@/lib/order-status"
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

const STALE_DAYS = 15

export default function OrdersPage() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const { data: orders, loading, error, refetch, isAuthenticated } = useAuthenticatedOrders()
  const { isLoading: authLoading } = useAuth()

  const { mutate: deleteOrder } = useMutation()
  const { mutate: invoiceOrder } = useMutation()
  const { mutate: bulkDeleteOrders } = useMutation()
  const { mutate: bulkUpdateOrdersStatus } = useMutation()

  const { shouldRefresh, resetRefresh } = useOrderRefresh()

  const [staleCount, setStaleCount] = useState(0)
  const [staleDialogOpen, setStaleDialogOpen] = useState(false)
  const [completingStale, setCompletingStale] = useState(false)
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)
  const [detailsOpen, setDetailsOpen] = useState(false)
  const [receiptOpen, setReceiptOpen] = useState(false)

  const loadStaleSummary = useCallback(async () => {
    if (!isAuthenticated) return
    try {
      const response = await apiClient.get<{ count?: number }>(
        endpoints.orders.staleOpen(STALE_DAYS)
      )
      const count = response?.data?.count ?? 0
      setStaleCount(typeof count === "number" ? count : 0)
    } catch {
      setStaleCount(0)
    }
  }, [isAuthenticated])

  useEffect(() => {
    refetch()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  useEffect(() => {
    loadStaleSummary()
  }, [loadStaleSummary, orders])

  useEffect(() => {
    if (shouldRefresh) {
      refetch()
      resetRefresh()
    }
  }, [shouldRefresh, refetch, resetRefresh])

  useEffect(() => {
    const handleFocus = () => {
      refetch()
    }

    window.addEventListener("focus", handleFocus)
    return () => window.removeEventListener("focus", handleFocus)
  }, [refetch])

  useEffect(() => {
    const viewOrderId = searchParams.get("view")
    if (viewOrderId && orders && Array.isArray(orders)) {
      const orderToView = orders.find((order: Order) => order.identify === viewOrderId)
      if (orderToView) {
        setSelectedOrder(orderToView)
        setDetailsOpen(true)
        router.replace("/orders")
      }
    }
  }, [searchParams, orders, router])

  const handleDeleteOrder = async (order: Order) => {
    const identifier = order.identify || order.id?.toString()
    if (!identifier) {
      toast.error("Pedido não encontrado")
      return
    }

    try {
      await deleteOrder(endpoints.orders.delete(identifier), "DELETE")
      toast.success("Pedido excluído com sucesso!")
      await refetch()
    } catch (error: any) {
      toast.error(error.message || "Erro ao excluir pedido")
    }
  }

  const handleEditOrder = (order: Order) => {
    if (!canEditOrder(order.status)) {
      toast.error("Este pedido está finalizado e não pode ser editado.")
      return
    }

    const orderId = order.identify || order.id?.toString()
    if (!orderId) {
      return
    }

    router.push(`/orders/edit/${orderId}`)
  }

  const handleViewOrder = (order: Order) => {
    setSelectedOrder(order)
    setDetailsOpen(true)
  }

  const handleInvoiceOrder = async (order: Order) => {
    try {
      const orderId = order.identify || order.id?.toString()
      if (!orderId) {
        return
      }

      const result = await invoiceOrder(endpoints.orders.invoice(orderId), "POST")

      if (result) {
        toast.success("Pedido faturado com sucesso!")
        await refetch()
      }
    } catch {
      toast.error("Erro ao faturar pedido")
    }
  }

  const handleReceiptOrder = (order: Order) => {
    setSelectedOrder(order)
    setReceiptOpen(true)
  }

  const handleBulkDelete = async (orderIds: string[]) => {
    if (orderIds.length === 0) {
      toast.error("Nenhum pedido selecionado")
      return
    }

    try {
      const result = await bulkDeleteOrders(endpoints.orders.bulkDelete, "POST", {
        order_ids: orderIds,
      })

      if (result) {
        const totalDeleted = (result as any)?.total_deleted || 0
        toast.success(`${totalDeleted} pedido(s) excluído(s) com sucesso!`)
        await refetch()
      }
    } catch (error: any) {
      toast.error(error.message || "Erro ao excluir pedidos em massa")
    }
  }

  const handleBulkUpdateStatus = async (orderIds: string[], status: string) => {
    if (orderIds.length === 0) {
      toast.error("Nenhum pedido selecionado")
      return
    }

    try {
      const result = await bulkUpdateOrdersStatus(endpoints.orders.bulkUpdateStatus, "POST", {
        order_ids: orderIds,
        status: status,
      })

      if (result) {
        const totalUpdated = (result as any)?.total_updated || 0
        toast.success(`${totalUpdated} pedido(s) atualizado(s) para "${status}" com sucesso!`)
        await refetch()
      }
    } catch (error: any) {
      toast.error(error.message || "Erro ao atualizar status dos pedidos em massa")
    }
  }

  const handleCompleteStaleOrders = async () => {
    setCompletingStale(true)
    try {
      const response = await apiClient.post<{ total_updated?: number }>(
        endpoints.orders.completeStale,
        { days: STALE_DAYS }
      )
      const totalUpdated = response?.data?.total_updated ?? 0
      toast.success(
        totalUpdated > 0
          ? `${totalUpdated} pedido(s) marcado(s) como Concluído.`
          : "Nenhum pedido elegível para conclusão."
      )
      setStaleDialogOpen(false)
      await refetch()
      await loadStaleSummary()
    } catch (error: any) {
      toast.error(error?.message || "Erro ao concluir pedidos antigos")
    } finally {
      setCompletingStale(false)
    }
  }

  if (!authLoading && !isAuthenticated) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Usuário não autenticado. Faça login para continuar.</div>
      </div>
    )
  }

  if (loading) {
    return <PageLoading isLoading={loading} message="Carregando pedidos..." />
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar pedidos: {error}</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <StatCards />
      </div>

      <div className="@container/main px-4 lg:px-6">
        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="min-w-0">
            <h2 className="text-2xl font-bold">Pedidos</h2>
            <p className="text-muted-foreground">Gerencie todos os pedidos</p>
          </div>
          <Button
            className="h-9 w-full shrink-0 sm:w-auto"
            onClick={() => router.push("/orders/new")}
          >
            <Plus className="mr-2 h-4 w-4" />
            Novo Pedido
          </Button>
        </div>

        {staleCount > 0 && (
          <Alert className="mb-4 border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
            <CheckCircle2 className="h-4 w-4 text-amber-700 dark:text-amber-300" />
            <AlertTitle>Pedidos antigos em aberto</AlertTitle>
            <AlertDescription className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <span>
                Há {staleCount} pedido(s) com mais de {STALE_DAYS} dias em Pendente, Aceito ou
                Preparo. Você pode marcar todos como Concluído.
              </span>
              <Button
                size="sm"
                className="shrink-0 bg-amber-700 hover:bg-amber-800 text-white"
                onClick={() => setStaleDialogOpen(true)}
              >
                Marcar todos como Concluído
              </Button>
            </AlertDescription>
          </Alert>
        )}

        <DataTable
          orders={Array.isArray(orders) ? orders : []}
          onDeleteOrder={handleDeleteOrder}
          onEditOrder={handleEditOrder}
          onViewOrder={handleViewOrder}
          onInvoiceOrder={handleInvoiceOrder}
          onReceiptOrder={handleReceiptOrder}
          onBulkDelete={handleBulkDelete}
          onBulkUpdateStatus={handleBulkUpdateStatus}
        />
      </div>

      <OrderDetailsDialog
        order={selectedOrder as any}
        open={detailsOpen}
        onOpenChange={setDetailsOpen}
      />

      <ReceiptDialog
        order={selectedOrder as any}
        open={receiptOpen}
        onOpenChange={setReceiptOpen}
      />

      <AlertDialog open={staleDialogOpen} onOpenChange={setStaleDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Concluir pedidos antigos</AlertDialogTitle>
            <AlertDialogDescription>
              {staleCount} pedido(s) com mais de {STALE_DAYS} dias em Pendente, Aceito ou Preparo
              serão marcados como Concluído. Deseja continuar?
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={completingStale}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleCompleteStaleOrders}
              disabled={completingStale}
              className="bg-amber-700 hover:bg-amber-800"
            >
              {completingStale ? "Concluindo..." : "Marcar todos como Concluído"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
