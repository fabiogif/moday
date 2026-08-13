import {
  LANDING_HEADLINE_VARIANTS,
  getVariantFromQuery,
  resolveLandingVariant,
} from '../landing-variants'

describe('landing-variants', () => {
  it('deve retornar variante A por padrão', () => {
    expect(resolveLandingVariant(null).id).toBe('a')
    expect(resolveLandingVariant(new URLSearchParams()).title).toBe(
      LANDING_HEADLINE_VARIANTS.a.title
    )
  })

  it('deve retornar variante B via query param', () => {
    const params = new URLSearchParams('v=b')
    expect(resolveLandingVariant(params).id).toBe('b')
    expect(getVariantFromQuery(params)).toBe('b')
  })

  it('deve retornar variante A via query param', () => {
    const params = new URLSearchParams('v=a')
    expect(resolveLandingVariant(params).id).toBe('a')
    expect(getVariantFromQuery(params)).toBe('a')
  })
})
