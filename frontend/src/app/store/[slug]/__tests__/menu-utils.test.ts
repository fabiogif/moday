import { getDisplayPrice, getNumericPrice } from '../menu-utils'

describe('getDisplayPrice', () => {
  it('sem desconto usa o preço cheio e não tem preço riscado', () => {
    expect(getDisplayPrice({ price: 20 })).toEqual({
      price: 20,
      originalPrice: null,
      discountPercent: 0,
      isFrom: false,
    })
  })

  it('com promoção aceita string e number e calcula o percentual', () => {
    expect(getDisplayPrice({ price: '10.00', promotional_price: '5.00' })).toEqual({
      price: 5,
      originalPrice: 10,
      discountPercent: 50,
      isFrom: false,
    })
    expect(getDisplayPrice({ price: 30, promotional_price: 25 }).discountPercent).toBe(17)
  })

  it('promocional maior ou igual ao preço não conta como desconto', () => {
    const result = getDisplayPrice({ price: 10, promotional_price: 12 })
    expect(result.originalPrice).toBeNull()
    expect(result.discountPercent).toBe(0)
  })

  it('com variações exibe "a partir de" somando a variação mais barata', () => {
    const result = getDisplayPrice({
      price: 30,
      variations: [
        { id: 'g', name: 'Grande', price: 10 },
        { id: 'm', name: 'Média', price: 5 },
      ],
    })
    expect(result.isFrom).toBe(true)
    expect(result.price).toBe(35)
  })

  it('variação soma também no preço riscado', () => {
    const result = getDisplayPrice({
      price: 40,
      promotional_price: 30,
      variations: [{ id: 'p', name: 'P', price: 5 }],
    })
    expect(result.price).toBe(35)
    expect(result.originalPrice).toBe(45)
  })
})

describe('getNumericPrice', () => {
  it('converte strings, numbers e vazios', () => {
    expect(getNumericPrice(10)).toBe(10)
    expect(getNumericPrice('10.50')).toBe(10.5)
    expect(getNumericPrice('invalid')).toBe(0)
    expect(getNumericPrice(null)).toBe(0)
  })
})
