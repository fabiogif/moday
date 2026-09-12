import { maskPhone } from '../masks'

describe('maskPhone', () => {
  it('formata um celular local de 11 dígitos', () => {
    expect(maskPhone('11999999999')).toBe('(11) 99999-9999')
  })

  it('formata um fixo local de 10 dígitos', () => {
    expect(maskPhone('1133334444')).toBe('(11) 3333-4444')
  })

  it('remove o código do país (55) de um número de WhatsApp', () => {
    expect(maskPhone('5511999999999')).toBe('(11) 99999-9999')
  })

  it('não corta um DDD 55 legítimo (11 dígitos, sem código de país)', () => {
    expect(maskPhone('55999999999')).toBe('(55) 99999-9999')
  })
})
