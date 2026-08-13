/**
 * Serviço para processamento de pagamentos
 * Centraliza lógica de preparação e validação de dados de pagamento
 */

import type { PaymentMethod } from "../components/payment/payment-method-card"
import type { SplitPaymentItem } from "../components/payment/split-payment-form"
import type { PaymentConfirmationItem } from "../components/payment/payment-confirmation-dialog"

export interface PaymentData {
  payment_method_id?: string | null
  precisa_troco?: boolean
  valor_recebido?: number | null
  split_payments?: Array<{
    payment_method_id: string
    amount: number
    needs_change?: boolean
  }>
}

/**
 * Verifica se um método de pagamento é dinheiro
 */
export function isCashPayment(method: PaymentMethod): boolean {
  const name = method.name.toLowerCase()
  return (
    name.includes("dinheiro") ||
    name.includes("money") ||
    name.includes("cash")
  )
}

/**
 * Prepara dados de pagamento para confirmação
 */
export function preparePaymentData(
  useSplitPayment: boolean,
  selectedPaymentMethod: string | null | undefined,
  splitPaymentItems: SplitPaymentItem[],
  paymentMethods: PaymentMethod[],
  orderTotal: number,
  receivedAmount: number | null,
  needsChange: boolean
): PaymentConfirmationItem[] {
  if (useSplitPayment) {
    return splitPaymentItems
      .filter((item) => item.amount !== null && item.amount > 0)
      .map((item) => ({
        method: item.method,
        amount: item.amount!,
      }))
  } else if (selectedPaymentMethod) {
    const method = paymentMethods.find((m) => m.uuid === selectedPaymentMethod)
    if (method) {
      const amount =
        receivedAmount && needsChange ? receivedAmount : orderTotal
      return [
        {
          method: {
            uuid: method.uuid,
            name: method.name,
            description: method.description || null,
          },
          amount,
        },
      ]
    }
  }
  return []
}

/**
 * Prepara payload de pagamento para API
 */
export function preparePaymentPayload(
  useSplitPayment: boolean,
  selectedPaymentMethod: string | null | undefined,
  splitPaymentItems: SplitPaymentItem[],
  needsChange: boolean,
  receivedAmount: number | null
): PaymentData {
  const payload: PaymentData = {}

  if (!useSplitPayment && selectedPaymentMethod) {
    payload.payment_method_id = selectedPaymentMethod
    payload.precisa_troco = needsChange
    payload.valor_recebido = needsChange && receivedAmount ? receivedAmount : null
  } else if (useSplitPayment && splitPaymentItems.length > 0) {
    payload.split_payments = splitPaymentItems
      .filter((item) => item.amount !== null && item.amount > 0)
      .map((item) => ({
        payment_method_id: item.method.uuid,
        amount: item.amount!,
        needs_change: isCashPayment(item.method),
      }))
  }

  return payload
}

/**
 * Calcula o total pago em um split payment
 */
export function calculateTotalPaid(
  splitPaymentItems: SplitPaymentItem[]
): number {
  return splitPaymentItems.reduce(
    (sum, item) => sum + (item.amount || 0),
    0
  )
}

/**
 * Verifica se o pagamento está completo
 */
export function isPaymentComplete(
  orderTotal: number,
  useSplitPayment: boolean,
  splitPaymentItems: SplitPaymentItem[],
  selectedPaymentMethod: string | null | undefined
): boolean {
  if (useSplitPayment) {
    const totalPaid = calculateTotalPaid(splitPaymentItems)
    return totalPaid >= orderTotal
  }
  return !!selectedPaymentMethod
}

/**
 * Calcula o troco para split payment
 */
export function calculateChangeForSplitPayment(
  orderTotal: number,
  splitPaymentItems: SplitPaymentItem[]
): number {
  const totalPaid = calculateTotalPaid(splitPaymentItems)
  return Math.max(0, totalPaid - orderTotal)
}

