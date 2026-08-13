export type FeeType = 'fixed' | 'negotiable' | 'free'

export const FEE_TYPE_OPTIONS: { value: FeeType; label: string }[] = [
  { value: 'fixed', label: 'Valor fixo' },
  { value: 'negotiable', label: 'A combinar' },
  { value: 'free', label: 'Grátis' },
]

/**
 * Texto de exibição para uma taxa já calculada (ex: linha da lista de bairros).
 */
export function formatFeeType(feeType: FeeType, feeValue: number | null): string {
  if (feeType === 'fixed') return `R$ ${Number(feeValue ?? 0).toFixed(2)}`
  if (feeType === 'free') return 'Grátis'
  return 'A combinar'
}
