/**
 * Serviço para validação de pedidos
 * Centraliza regras de validação e validações de negócio
 */

import { CartItem } from "./order-calculator"
import { isFinalStatus } from "@/lib/order-status"

export interface ValidationError {
  field?: string
  message: string
  type: "error" | "warning" | "info"
}

export interface OrderValidationContext {
  cart: CartItem[]
  selectedTable?: string | null
  isDelivery: boolean
  selectedPaymentMethod?: string | null
  useSplitPayment?: boolean
  splitPaymentItems?: Array<{ method: { uuid: string }; amount: number | null }>
  orderTotal: number
  orderStatus?: string | null
  editingOrder?: boolean
}

/**
 * Valida se o carrinho tem itens
 */
export function validateCartNotEmpty(cart: CartItem[]): ValidationError | null {
  if (cart.length === 0) {
    return {
      message: "Adicione pelo menos um item ao pedido",
      type: "error",
    }
  }
  return null
}

/**
 * Valida se uma mesa foi selecionada (para pedidos não delivery)
 */
export function validateTableSelected(
  isDelivery: boolean,
  selectedTable: string | null | undefined
): ValidationError | null {
  if (!isDelivery && !selectedTable) {
    return {
      field: "table",
      message: "Selecione uma mesa para o pedido",
      type: "error",
    }
  }
  return null
}

/**
 * Valida se um método de pagamento foi selecionado
 */
export function validatePaymentMethod(
  selectedPaymentMethod: string | null | undefined,
  useSplitPayment: boolean = false,
  splitPaymentItems: Array<{ method: { uuid: string }; amount: number | null }> = []
): ValidationError | null {
  if (!useSplitPayment && !selectedPaymentMethod) {
    return {
      field: "payment",
      message: "Selecione uma forma de pagamento",
      type: "error",
    }
  }

  if (useSplitPayment && splitPaymentItems.length === 0) {
    return {
      field: "payment",
      message: "Adicione pelo menos um método de pagamento",
      type: "error",
    }
  }

  return null
}

/**
 * Valida se o pagamento está completo (para split payment)
 */
export function validatePaymentComplete(
  orderTotal: number,
  useSplitPayment: boolean,
  splitPaymentItems: Array<{ method: { uuid: string }; amount: number | null }>
): ValidationError | null {
  if (!useSplitPayment) {
    return null
  }

  const totalPaid = splitPaymentItems.reduce(
    (sum, item) => sum + (item.amount || 0),
    0
  )

  if (totalPaid < orderTotal) {
    return {
      field: "payment",
      message: `O valor pago (${totalPaid.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      })}) é menor que o total do pedido (${orderTotal.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      })})`,
      type: "error",
    }
  }

  return null
}

/**
 * Valida se o pedido pode ser editado baseado no status
 */
export function validateOrderEditable(orderStatus?: string | null): ValidationError | null {
  if (orderStatus && isFinalStatus(orderStatus)) {
    return {
      message: `Este pedido possui status final (${orderStatus}) e não pode ser editado`,
      type: "error",
    }
  }
  return null
}

/**
 * Valida se o pedido pode ser finalizado baseado no status
 */
export function validateOrderCanBeFinalized(orderStatus?: string | null): ValidationError | null {
  if (!orderStatus) {
    return {
      message: "Status do pedido não identificado",
      type: "error",
    }
  }

  if (isFinalStatus(orderStatus)) {
    return {
      message: `Pedido já está finalizado (${orderStatus})`,
      type: "error",
    }
  }

  if (orderStatus !== "Preparo") {
    return {
      message: `Pedido deve estar em "Preparo" para ser finalizado. Status atual: ${orderStatus}`,
      type: "error",
    }
  }

  return null
}

/**
 * Valida se o valor recebido é suficiente (para pagamento em dinheiro)
 */
export function validateReceivedAmount(
  receivedAmount: number | null,
  orderTotal: number,
  needsChange: boolean
): ValidationError | null {
  if (!needsChange) {
    return null
  }

  if (receivedAmount === null || receivedAmount === undefined) {
    return {
      field: "receivedAmount",
      message: "Informe o valor recebido",
      type: "error",
    }
  }

  if (receivedAmount <= 0) {
    return {
      field: "receivedAmount",
      message: "O valor recebido deve ser maior que zero",
      type: "error",
    }
  }

  if (receivedAmount < orderTotal) {
    return {
      field: "receivedAmount",
      message: `O valor recebido deve ser maior ou igual ao total (${orderTotal.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      })})`,
      type: "error",
    }
  }

  return null
}

/**
 * Valida um pedido completo antes de iniciar
 */
export function validateOrderBeforeStart(context: OrderValidationContext): ValidationError[] {
  const errors: ValidationError[] = []

  const cartError = validateCartNotEmpty(context.cart)
  if (cartError) errors.push(cartError)

  const tableError = validateTableSelected(context.isDelivery, context.selectedTable)
  if (tableError) errors.push(tableError)

  const paymentError = validatePaymentMethod(
    context.selectedPaymentMethod,
    context.useSplitPayment,
    context.splitPaymentItems
  )
  if (paymentError) errors.push(paymentError)

  if (context.useSplitPayment && context.splitPaymentItems) {
    const paymentCompleteError = validatePaymentComplete(
      context.orderTotal,
      context.useSplitPayment,
      context.splitPaymentItems
    )
    if (paymentCompleteError) errors.push(paymentCompleteError)
  }

  return errors
}

/**
 * Valida um pedido antes de atualizar
 */
export function validateOrderBeforeUpdate(context: OrderValidationContext): ValidationError[] {
  const errors: ValidationError[] = []

  const editableError = validateOrderEditable(context.orderStatus)
  if (editableError) errors.push(editableError)

  const cartError = validateCartNotEmpty(context.cart)
  if (cartError) errors.push(cartError)

  return errors
}

/**
 * Valida um pedido antes de finalizar
 */
export function validateOrderBeforeFinalize(context: OrderValidationContext): ValidationError[] {
  const errors: ValidationError[] = []

  const editableError = validateOrderEditable(context.orderStatus)
  if (editableError) errors.push(editableError)

  const finalizeError = validateOrderCanBeFinalized(context.orderStatus)
  if (finalizeError) errors.push(finalizeError)

  const cartError = validateCartNotEmpty(context.cart)
  if (cartError) errors.push(cartError)

  const paymentError = validatePaymentMethod(
    context.selectedPaymentMethod,
    context.useSplitPayment,
    context.splitPaymentItems
  )
  if (paymentError) errors.push(paymentError)

  if (context.useSplitPayment && context.splitPaymentItems) {
    const paymentCompleteError = validatePaymentComplete(
      context.orderTotal,
      context.useSplitPayment,
      context.splitPaymentItems
    )
    if (paymentCompleteError) errors.push(paymentCompleteError)
  }

  return errors
}

