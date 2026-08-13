/**
 * Serviço para cálculos relacionados a pedidos
 * Centraliza lógica de cálculo de preços, totais, impostos e descontos
 */

export interface CartItem {
  /**
   * Assinatura única usada no PDV para identificar o item no carrinho.
   * Opcional aqui para manter compatibilidade com outros usos.
   */
  signature?: string
  product: {
    uuid?: string
    identify?: string
    price?: number | string | null
    variations?: Array<{
      id?: string
      identify?: string
      name: string
      price?: number | string | null
    }>
    optionals?: Array<{
      id?: string
      identify?: string
      name: string
      price?: number | string | null
      quantity: number
    }>
  }
  quantity: number
  /**
   * Observação do item (usada no PDV).
   */
  observation?: string
  selectedVariation?: {
    id?: string
    identify?: string
    name: string
    price?: number | string | null
  }
  selectedOptionals?: Array<{
    id?: string
    identify?: string
    name: string
    price?: number | string | null
    quantity: number
  }>
}

/**
 * Converte valor para número, tratando strings e null
 */
export function parsePrice(value?: number | string | null): number {
  if (value === null || value === undefined || value === "") return 0
  if (typeof value === "number") return value
  if (typeof value === "string") {
    const cleaned = value.replace(/[^\d,.-]/g, "").replace(",", ".")
    const parsed = parseFloat(cleaned)
    return isNaN(parsed) ? 0 : parsed
  }
  return 0
}

/**
 * Obtém o preço base de um produto
 */
export function getProductPrice(product: CartItem["product"]): number {
  return parsePrice(product.price)
}

/**
 * Calcula o preço unitário de um item do carrinho
 * Considera variações e opcionais
 */
export function getCartItemUnitPrice(item: CartItem): number {
  // Preço base do produto ou variação
  const basePrice = item.selectedVariation
    ? parsePrice(item.selectedVariation.price ?? null) || getProductPrice(item.product)
    : getProductPrice(item.product)

  // Adicionar preços dos opcionais
  const optionalsPrice = (item.selectedOptionals ?? []).reduce((sum, optional) => {
    return sum + parsePrice(optional.price) * optional.quantity
  }, 0)

  return basePrice + optionalsPrice
}

/**
 * Calcula o preço total de um item do carrinho (preço unitário * quantidade)
 */
export function getCartItemTotal(item: CartItem): number {
  return getCartItemUnitPrice(item) * item.quantity
}

/**
 * Calcula o subtotal do carrinho (soma de todos os itens)
 */
export function calculateSubtotal(items: CartItem[]): number {
  return items.reduce((sum, item) => sum + getCartItemTotal(item), 0)
}

/**
 * Calcula impostos sobre o subtotal
 * @param subtotal - Valor do subtotal
 * @param taxRate - Taxa de imposto em porcentagem (ex: 10 para 10%)
 */
export function calculateTaxes(subtotal: number, taxRate: number = 0): number {
  return subtotal * (taxRate / 100)
}

/**
 * Calcula descontos
 * @param subtotal - Valor do subtotal
 * @param discountAmount - Valor do desconto em reais
 * @param discountPercent - Percentual de desconto (ex: 10 para 10%)
 */
export function calculateDiscounts(
  subtotal: number,
  discountAmount: number = 0,
  discountPercent: number = 0
): number {
  const amountDiscount = discountAmount
  const percentDiscount = subtotal * (discountPercent / 100)
  return amountDiscount + percentDiscount
}

/**
 * Calcula o total final do pedido
 * @param items - Itens do carrinho
 * @param taxRate - Taxa de imposto em porcentagem
 * @param discountAmount - Valor do desconto em reais
 * @param discountPercent - Percentual de desconto
 */
export function calculateOrderTotal(
  items: CartItem[],
  taxRate: number = 0,
  discountAmount: number = 0,
  discountPercent: number = 0
): {
  subtotal: number
  taxes: number
  discounts: number
  total: number
} {
  const subtotal = calculateSubtotal(items)
  const taxes = calculateTaxes(subtotal, taxRate)
  const discounts = calculateDiscounts(subtotal, discountAmount, discountPercent)
  const total = subtotal + taxes - discounts

  return {
    subtotal,
    taxes,
    discounts,
    total: Math.max(0, total), // Garantir que o total nunca seja negativo
  }
}

/**
 * Formata valor monetário para exibição
 */
export function formatCurrency(value: number): string {
  return value.toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
  })
}

/**
 * Calcula o troco a ser devolvido
 * @param receivedAmount - Valor recebido
 * @param orderTotal - Total do pedido
 */
export function calculateChange(receivedAmount: number, orderTotal: number): number {
  return Math.max(0, receivedAmount - orderTotal)
}

