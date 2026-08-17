import { requestPasswordReset, resetPassword } from '../auth-password'

jest.mock('../api-config', () => ({
  buildApiUrl: (path: string) => `http://api.test${path}`,
}))

describe('auth-password', () => {
  beforeEach(() => {
    global.fetch = jest.fn()
  })

  afterEach(() => {
    jest.resetAllMocks()
  })

  describe('requestPasswordReset', () => {
    it('envia solicitação para o endpoint tenant', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValue({
        ok: true,
        json: async () => ({ success: true, message: 'Link enviado' }),
      })

      const message = await requestPasswordReset('user@example.com')

      expect(global.fetch).toHaveBeenCalledWith(
        'http://api.test/api/auth/forgot-password',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ email: 'user@example.com' }),
        }),
      )
      expect(message).toBe('Link enviado')
    })

    it('envia solicitação para o endpoint admin', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValue({
        ok: true,
        json: async () => ({ success: true, message: 'Link admin enviado' }),
      })

      await requestPasswordReset('admin@example.com', 'admin')

      expect(global.fetch).toHaveBeenCalledWith(
        'http://api.test/api/admin/auth/forgot-password',
        expect.any(Object),
      )
    })

    it('lança erro quando a API falha', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValue({
        ok: false,
        headers: { get: () => 'application/json' },
        json: async () => ({ message: 'Usuário não encontrado' }),
      })

      await expect(requestPasswordReset('missing@example.com')).rejects.toThrow(
        'Usuário não encontrado',
      )
    })
  })

  describe('resetPassword', () => {
    it('envia token e senha para o endpoint tenant', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValue({
        ok: true,
        json: async () => ({ success: true, message: 'Senha resetada' }),
      })

      const payload = {
        token: 'abc123',
        email: 'user@example.com',
        password: 'newpassword',
        password_confirmation: 'newpassword',
      }

      const message = await resetPassword(payload)

      expect(global.fetch).toHaveBeenCalledWith(
        'http://api.test/api/auth/reset-password',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(payload),
        }),
      )
      expect(message).toBe('Senha resetada')
    })

    it('envia para o endpoint admin quando scope é admin', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValue({
        ok: true,
        json: async () => ({ success: true, message: 'Senha admin resetada' }),
      })

      await resetPassword(
        {
          token: 'token',
          email: 'admin@example.com',
          password: 'adminpass1',
          password_confirmation: 'adminpass1',
        },
        'admin',
      )

      expect(global.fetch).toHaveBeenCalledWith(
        'http://api.test/api/admin/auth/reset-password',
        expect.any(Object),
      )
    })
  })
})
