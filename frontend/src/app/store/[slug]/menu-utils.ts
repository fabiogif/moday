import { toast } from "sonner"

export interface ProductVariation {
  id: string
  name: string
  price: number
}

export interface ProductOptional {
  id: string
  name: string
  price: number
}

export interface Product {
  uuid: string
  name: string
  description: string
  price: number | string
  promotional_price?: number | string
  image: string
  qtd_stock: number
  brand: string
  categories: Array<{ uuid: string; name: string }>
  variations?: ProductVariation[]   // Seleção única (tamanhos) — preço somado ao do produto
  optionals?: ProductOptional[]     // Múltipla escolha com quantidade
  sold_qty?: number
}

export function getNumericPrice(price: number | string | null | undefined): number {
  if (price === null || price === undefined) return 0
  return typeof price === "string" ? parseFloat(price) || 0 : price
}

export function formatPrice(price: number | string): string {
  const numPrice = typeof price === "string" ? parseFloat(price) : price
  if (isNaN(numPrice)) {
    return "0,00"
  }
  return numPrice.toLocaleString("pt-BR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

export interface DisplayPrice {
  /** Preço cobrado (promocional quando houver), já somado à variação mais barata quando `isFrom` */
  price: number
  /** Preço "de" riscado — só quando há desconto real */
  originalPrice: number | null
  discountPercent: number
  /** Produto com variações: exibir "a partir de" */
  isFrom: boolean
}

/** Regra única de preço exibido no cardápio (linha, ofertas, preferidos). */
export function getDisplayPrice(product: Pick<Product, "price" | "promotional_price" | "variations">): DisplayPrice {
  const original = getNumericPrice(product.price)
  const promo = getNumericPrice(product.promotional_price)
  // Mesma regra do carrinho: promocional, quando preenchido, é o preço cobrado
  const base = promo > 0 ? promo : original
  const hasDiscount = promo > 0 && promo < original
  const cheapestVariation = product.variations?.length
    ? Math.min(...product.variations.map((v) => getNumericPrice(v.price)))
    : 0

  return {
    price: base + cheapestVariation,
    originalPrice: hasDiscount ? original + cheapestVariation : null,
    discountPercent: hasDiscount ? Math.round((1 - promo / original) * 100) : 0,
    isFrom: !!product.variations?.length,
  }
}

export async function copyText(text: string, successMessage: string) {
  try {
    await navigator.clipboard.writeText(text)
    toast.success(successMessage)
  } catch {
    toast.error("Não foi possível copiar")
  }
}
