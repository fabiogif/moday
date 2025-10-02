"use client"

import { useState } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"
import { useOrders, useMutation } from "@/hooks/use-api"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"

interface Order {
  id: number
  orderNumber: string
  customerName: string
  customerEmail: string
  status: string
  total: number
  items: number
  orderDate: string
  deliveryDate: string
}

interface OrderFormValues {
  clientId: string
  products: {
    productId: string
    quantity: number
    price: number
  }[]
  status: string
  isDelivery: boolean
  tableId?: string
  total: number
}

export default function OrdersPage() {
  const { data: orders, loading, error, refetch } = useOrders()
  const { mutate: createOrder, loading: creating } = useMutation()
  const { mutate: deleteOrder, loading: deleting } = useMutation()

  const handleAddOrder = async (orderData: OrderFormValues) => {
    try {
      const result = await createOrder(
        endpoints.orders.create,
        'POST',
        orderData
      )
      
      if (result) {
        // Recarregar dados após criação
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao criar pedido:', error)
    }
  }

  const handleDeleteOrder = async (id: number) => {
    try {
      const result = await deleteOrder(
        endpoints.orders.delete(id.toString()),
        'DELETE'
      )
      
      if (result) {
        // Recarregar dados após exclusão
        await refetch()
      }
    } catch (error) {
      console.error('Erro ao excluir pedido:', error)
    }
  }

  const handleEditOrder = (order: Order) => {
    console.log("Edit order:", order)
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
        <div className="text-destructive">Erro ao carregar pedidos: {error}</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <StatCards />
      </div>
      
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <DataTable 
          orders={Array.isArray(orders) ? orders : []}
          onDeleteOrder={handleDeleteOrder}
          onEditOrder={handleEditOrder}
          onAddOrder={handleAddOrder}
        />
      </div>
    </div>
  )
}
