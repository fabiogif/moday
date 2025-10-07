"use client"

import { useState, useEffect } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"
import { OrderDetailsDialog } from "./components/order-details-dialog"
import { ReceiptDialog } from "./components/receipt-dialog"
import { useOrders, useMutation } from "@/hooks/use-api"
import { useOrderRefresh } from "@/hooks/use-order-refresh"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"
import { Order } from "./types"
import { Button } from "@/components/ui/button"
import { Plus } from "lucide-react"
import { useRouter, useSearchParams } from "next/navigation"
import { toast } from "sonner"

export default function OrdersPage() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const { data: orders, loading, error, refetch } = useOrders()
  
  // Debug: Log dos pedidos recebidos
  useEffect(() => {
    if (orders) {
      console.log('OrdersPage - Total de pedidos:', Array.isArray(orders) ? orders.length : 'não é array')
      console.log('OrdersPage - Pedidos:', orders)
      if (Array.isArray(orders) && orders.length > 0) {
        console.log('OrdersPage - Primeiro pedido completo:', orders[0])
        console.log('OrdersPage - Cliente do primeiro pedido:', orders[0].client)
      }
    }
  }, [orders])
  const { mutate: createOrder, loading: creating } = useMutation()
  const { mutate: deleteOrder, loading: deleting } = useMutation()
  const { mutate: invoiceOrder, loading: invoicing } = useMutation()
  
  // Hook para detectar quando precisa atualizar
  const { shouldRefresh, resetRefresh } = useOrderRefresh()

  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)
  const [detailsOpen, setDetailsOpen] = useState(false)
  const [receiptOpen, setReceiptOpen] = useState(false)

  // Atualizar lista quando shouldRefresh for true
  useEffect(() => {
    if (shouldRefresh) {
      refetch()
      resetRefresh()
    }
  }, [shouldRefresh, refetch, resetRefresh])

  // Handle auto-opening order details from URL parameter
  useEffect(() => {
    const viewOrderId = searchParams.get('view')
    if (viewOrderId && orders && Array.isArray(orders)) {
      const orderToView = orders.find((order: Order) => order.identify === viewOrderId)
      if (orderToView) {
        setSelectedOrder(orderToView)
        setDetailsOpen(true)
        // Clean up URL parameter
        router.replace('/orders')
      }
    }
  }, [searchParams, orders, router])

  const handleDeleteOrder = async (id: number) => {
    try {
      const result = await deleteOrder(
        endpoints.orders.delete(id.toString()),
        'DELETE'
      )

      if (result) {
        toast.success('Pedido excluído com sucesso!')
        // Recarregar dados após exclusão
        await refetch()
      }
    } catch (error: any) {
      console.error('Erro ao excluir pedido:', error)
      toast.error(error.message || 'Erro ao excluir pedido')
    }
  }

  const handleEditOrder = (order: Order) => {
    // Usar identify em vez de id que pode ser undefined
    const orderId = order.identify || order.id?.toString()
    if (!orderId) {
      console.error('ID do pedido não encontrado')
      return
    }

    // Navegar para página de edição
    router.push(`/orders/edit/${orderId}`)
  }

  const handleViewOrder = (order: Order) => {
    setSelectedOrder(order)
    setDetailsOpen(true)
  }

  const handleInvoiceOrder = async (order: Order) => {
    try {
      // Usar identify em vez de id que pode ser undefined
      const orderId = order.identify || order.id?.toString()
      if (!orderId) {
        console.error('ID do pedido não encontrado')
        return
      }

      const result = await invoiceOrder(
        endpoints.orders.invoice(orderId),
        'POST'
      )

      if (result) {
        toast.success('Pedido faturado com sucesso!')
        // Recarregar dados após faturamento
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao faturar pedido:', error)
      toast.error('Erro ao faturar pedido')
    }
  }

  const handleReceiptOrder = (order: Order) => {
    setSelectedOrder(order)
    setReceiptOpen(true)
  }

  if (loading) {
    return (
      <PageLoading
        isLoading={loading}
        message="Carregando pedidos..."
      />
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar pedidos:</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <StatCards />
      </div>

      <div className="@container/main px-4 lg:px-6">
        <div className="flex justify-between items-center mb-4">
          <div>
            <h2 className="text-2xl font-bold">Pedidos</h2>
            <p className="text-muted-foreground">Gerencie todos os pedidos</p>
          </div>
          <Button onClick={() => router.push('/orders/new')}>
            <Plus className="mr-2 h-4 w-4" />
            Novo Pedido
          </Button>
        </div>

        <DataTable
          orders={Array.isArray(orders) ? orders : []}
          onDeleteOrder={handleDeleteOrder}
          onEditOrder={handleEditOrder}
          onViewOrder={handleViewOrder}
          onInvoiceOrder={handleInvoiceOrder}
          onReceiptOrder={handleReceiptOrder}
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
    </div>
  )
}

