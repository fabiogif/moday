import {
  EmailVerificationError,
  maskEmail,
  resendVerificationEmail,
  verifyEmailCode,
} from '@/lib/auth-email-verification'

jest.mock('@/lib/api-config', () => ({
  buildApiUrl: (path: string) => `http://api.test${path}`,
}))

jest.mock('@/lib/auth-storage', () => ({
  getAuthToken: () => 'test-token',
}))

describe('auth-email-verification', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    global.fetch = jest.fn()
  })

  describe('maskEmail', () => {
    it('mascara o local-part mantendo o domínio', () => {
      expect(maskEmail('joao@empresa.com')).toBe('j***@empresa.com')
    })

    it('retorna placeholder para e-mail inválido', () => {
      expect(maskEmail('sem-arroba')).toBe('***')
    })
  })

  describe('verifyEmailCode', () => {
    it('retorna mensagem de sucesso', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => ({
          success: true,
          message: 'E-mail confirmado com sucesso.',
          data: { email_verified: true },
        }),
      })

      await expect(verifyEmailCode('123456')).resolves.toBe('E-mail confirmado com sucesso.')
      expect(global.fetch).toHaveBeenCalledWith(
        'http://api.test/api/auth/email/verify',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ code: '123456' }),
        }),
      )
    })

    it('propaga erro tipado quando o código é inválido', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 422,
        statusText: 'Unprocessable Entity',
        headers: { get: () => 'application/json' },
        json: async () => ({
          success: false,
          message: 'Código incorreto. Verifique o e-mail e tente novamente.',
          error: 'invalid_code',
        }),
      })

      await expect(verifyEmailCode('000000')).rejects.toMatchObject({
        name: 'EmailVerificationError',
        message: 'Código incorreto. Verifique o e-mail e tente novamente.',
        errorCode: 'invalid_code',
      })
    })
  })

  describe('resendVerificationEmail', () => {
    it('retorna mensagem e e-mail mascarado', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => ({
          success: true,
          message: 'Novo código enviado para o seu e-mail.',
          data: { email: 'j***@empresa.com' },
        }),
      })

      await expect(resendVerificationEmail()).resolves.toEqual({
        message: 'Novo código enviado para o seu e-mail.',
        email: 'j***@empresa.com',
      })
    })

    it('inclui retry_after no cooldown', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        status: 429,
        statusText: 'Too Many Requests',
        headers: { get: () => 'application/json' },
        json: async () => ({
          success: false,
          message: 'Aguarde 40s antes de solicitar um novo código.',
          error: 'resend_cooldown',
          retry_after: 40,
        }),
      })

      try {
        await resendVerificationEmail()
        fail('deveria ter lançado')
      } catch (error) {
        expect(error).toBeInstanceOf(EmailVerificationError)
        expect((error as EmailVerificationError).errorCode).toBe('resend_cooldown')
        expect((error as EmailVerificationError).retryAfter).toBe(40)
      }
    })
  })
})
