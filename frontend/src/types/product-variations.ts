// Tipos para Variações e Opcionais de Produto

/**
 * Variação de Produto
 * Seleção ÚNICA (ex: Pequeno, Médio, Grande)
 * Cliente escolhe apenas UMA variação
 */
export interface ProductVariation {
  id: string
  name: string
  price: number // Valor adicional ou desconto
}

/**
 * Opcional de Produto  
 * Seleção MÚLTIPLA com QUANTIDADE (ex: Bacon, Queijo, Cebola)
 * Cliente pode escolher VÁRIOS e REPETIR
 */
export interface ProductOptional {
  id: string
  name: string
  price: number // Valor unitário
}

/**
 * Opcional Selecionado pelo Cliente
 * Inclui a quantidade escolhida
 */
export interface SelectedOptional extends ProductOptional {
  quantity: number
}

/**
 * Produto Completo com Variações e Opcionais
 */
export interface ProductWithVariations {
  uuid: string
  name: string
  description: string
  price: number
  promotional_price?: number
  image?: string
  qtd_stock: number
  is_active: boolean
  variations?: ProductVariation[]    // Tamanhos, tipos (escolher 1)
  optionals?: ProductOptional[]      // Adicionais (escolher N)
  categories?: Array<{
    uuid: string
    name: string
  }>
}

