import { PASSWORD_MIN_LENGTH, passwordSchema } from '../password-policy'

describe('password-policy', () => {
  it('define comprimento mínimo de 8 caracteres', () => {
    expect(PASSWORD_MIN_LENGTH).toBe(8)
  })

  it('rejeita senhas curtas', () => {
    expect(passwordSchema.safeParse('1234567').success).toBe(false)
    expect(passwordSchema.safeParse('12345678').success).toBe(true)
  })
})
