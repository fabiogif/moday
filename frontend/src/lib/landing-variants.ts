export type LandingVariant = 'a' | 'b'

export type LandingHeadlineVariant = {
  id: LandingVariant
  title: string
  subtitle: string
}

export const LANDING_HEADLINE_VARIANTS: Record<LandingVariant, LandingHeadlineVariant> = {
  a: {
    id: 'a',
    title: 'Venda mais e cometa menos erros no seu restaurante',
    subtitle:
      'Sistema de gestão para restaurantes com PDV, cardápio e vendas em um só lugar. Sua equipe trabalha mais rápido e você decide com dados reais — não com planilhas.',
  },
  b: {
    id: 'b',
    title: 'O sistema que restaurantes usam para vender mais todo dia',
    subtitle:
      'PDV rápido, cardápio digital e relatórios em tempo real no sistema de gestão para restaurantes Alba Tec. Menos erros na cozinha, mais vendas no salão e no delivery.',
  },
}

const VARIANT_COOKIE = 'landing_variant'
const VARIANT_COOKIE_MAX_AGE = 60 * 60 * 24 * 30

export function resolveLandingVariant(searchParams?: URLSearchParams | null): LandingHeadlineVariant {
  const param = searchParams?.get('v')?.toLowerCase()
  if (param === 'b') return LANDING_HEADLINE_VARIANTS.b
  if (param === 'a') return LANDING_HEADLINE_VARIANTS.a

  if (typeof document !== 'undefined') {
    const cookieMatch = document.cookie.match(new RegExp(`${VARIANT_COOKIE}=([ab])`))
    if (cookieMatch?.[1] === 'b') return LANDING_HEADLINE_VARIANTS.b
    if (cookieMatch?.[1] === 'a') return LANDING_HEADLINE_VARIANTS.a
  }

  return LANDING_HEADLINE_VARIANTS.a
}

export function persistLandingVariant(variant: LandingVariant) {
  if (typeof document === 'undefined') return
  document.cookie = `${VARIANT_COOKIE}=${variant}; path=/; max-age=${VARIANT_COOKIE_MAX_AGE}; SameSite=Lax`
}

export function getVariantFromQuery(searchParams?: URLSearchParams | null): LandingVariant | null {
  const param = searchParams?.get('v')?.toLowerCase()
  if (param === 'a' || param === 'b') return param
  return null
}
