"use client"

import { useState } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"
import { useAuthenticatedPaymentMethods, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"
import { toast } from "sonner"

interface PaymentMethod {
  id?: number
  uuid: string
  name: string
  description?: string
  is_active: boolean
  tenant_id?: number
  created_at: string
  updated_at: string
}

interface PaymentMethodFormValues {
  name: string
  description?: string
  is_active?: boolean
}

export default function PaymentMethodsPage() {
  const { data: paymentMethods, loading, error, refetch, isAuthenticated } = useAuthenticatedPaymentMethods()
  const { mutate: createPaymentMethod, loading: creating } = useMutation()
  const { mutate: updatePaymentMethod, loading: updating } = useMutation()
  const { mutate: deletePaymentMethod, loading: deleting } = useMutation()

  // Extrair array de paymentMethods do objeto retornado pela API
  const paymentMethodsArray: PaymentMethod[] = Array.isArray(paymentMethods) 
    ? paymentMethods 
    : paymentMethods && typeof paymentMethods === 'object' && 'paymentMethods' in paymentMethods
      ? (paymentMethods as any).paymentMethods || []
      : []

  const handleAddPaymentMethod = async (paymentMethodData: PaymentMethodFormValues) => {
    try {
      const result = await createPaymentMethod(
        endpoints.paymentMethods.create,
        'POST',
        paymentMethodData
      )
      
      if (result) {
        toast.success('Forma de pagamento criada com sucesso!')
        await refetch()
      }
    } catch (error: any) {

      toast.error(error.message || 'Erro ao criar forma de pagamento')
    }
  }

  const handleEditPaymentMethod = async (uuid: string, paymentMethodData: PaymentMethodFormValues) => {
    try {
      const result = await updatePaymentMethod(
        endpoints.paymentMethods.update(uuid),
        'PUT',
        paymentMethodData
      )
      
      if (result) {
        toast.success('Forma de pagamento atualizada com sucesso!')
        await refetch()
      }
    } catch (error: any) {

      toast.error(error.message || 'Erro ao atualizar forma de pagamento')
    }
  }

  const handleDeletePaymentMethod = async (uuid: string) => {
    try {
      const result = await deletePaymentMethod(
        endpoints.paymentMethods.delete(uuid),
        'DELETE'
      )
      
      // Para exclusão, o backend retorna success: true mesmo com data vazia
      // Verificar se não houve erro (result !== null)
      if (result !== null) {
        toast.success('Forma de pagamento excluída com sucesso!')
        await refetch()
      }
    } catch (error: any) {

      if (error?.status === 409) {
        toast.error(error?.message || 'Forma de pagamento não pode ser excluída, existe um pedido ativo ou não arquivado vinculado.')
        return
      }
      toast.error(error?.message || 'Erro ao excluir forma de pagamento')
    }
  }

  if (!isAuthenticated) {
    return <PageLoading />
  }

  if (loading) {
    return <PageLoading />
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Erro ao carregar formas de pagamento
          </h3>
          <p className="text-gray-600 mb-4">{error}</p>
          <button 
            onClick={() => refetch()}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Tentar novamente
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-6 py-2 px-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Formas de Pagamento</h1>
          <p className="text-muted-foreground">
            Gerencie as formas de pagamento aceitas em seu estabelecimento
          </p>
        </div>
      </div>

      <StatCards paymentMethods={paymentMethodsArray} />

      <DataTable
        paymentMethods={paymentMethodsArray}
        onAdd={handleAddPaymentMethod}
        onEdit={handleEditPaymentMethod}
        onDelete={handleDeletePaymentMethod}
        loading={creating || updating || deleting}
      />
    </div>
  )
}