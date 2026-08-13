import api, { endpoints } from '@/lib/api-client'

// Mock global fetch
global.fetch = jest.fn()

describe('API Client - Bank Accounts', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(global.fetch as jest.Mock).mockResolvedValue({
      ok: true,
      json: async () => ({ success: true, data: [] }),
    })
  })

  describe('bankAccounts.list', () => {
    it('has correct endpoint', () => {
      expect(endpoints.bankAccounts.list).toBe('/api/bank-accounts')
    })

    it('calls GET /api/bank-accounts', async () => {
      await api.get(endpoints.bankAccounts.list)
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts'),
        expect.objectContaining({
          method: 'GET',
        })
      )
    })
  })

  describe('bankAccounts.create', () => {
    it('has correct endpoint', () => {
      expect(endpoints.bankAccounts.create).toBe('/api/bank-accounts')
    })

    it('calls POST /api/bank-accounts', async () => {
      const data = {
        account_type: 'checking',
        bank_code: '001',
        agency: '1234',
        account_number: '12345678',
        account_digit: '9',
        account_holder_name: 'Test Account',
        account_holder_document: '12345678901',
        account_holder_type: 'individual',
      }

      await api.post(endpoints.bankAccounts.create, data)
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts'),
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(data),
        })
      )
    })
  })

  describe('bankAccounts.show', () => {
    it('generates correct endpoint', () => {
      const uuid = '123-456-789'
      expect(endpoints.bankAccounts.show(uuid)).toBe('/api/bank-accounts/123-456-789')
    })

    it('calls GET /api/bank-accounts/{uuid}', async () => {
      await api.get(endpoints.bankAccounts.show('abc-123'))
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts/abc-123'),
        expect.objectContaining({
          method: 'GET',
        })
      )
    })
  })

  describe('bankAccounts.update', () => {
    it('generates correct endpoint', () => {
      const uuid = '123-456-789'
      expect(endpoints.bankAccounts.update(uuid)).toBe('/api/bank-accounts/123-456-789')
    })

    it('calls PUT /api/bank-accounts/{uuid}', async () => {
      const data = { pix_key: 'new@email.com' }
      await api.put(endpoints.bankAccounts.update('abc-123'), data)
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts/abc-123'),
        expect.objectContaining({
          method: 'PUT',
          body: JSON.stringify(data),
        })
      )
    })
  })

  describe('bankAccounts.delete', () => {
    it('generates correct endpoint', () => {
      const uuid = '123-456-789'
      expect(endpoints.bankAccounts.delete(uuid)).toBe('/api/bank-accounts/123-456-789')
    })

    it('calls DELETE /api/bank-accounts/{uuid}', async () => {
      await api.delete(endpoints.bankAccounts.delete('abc-123'))
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts/abc-123'),
        expect.objectContaining({
          method: 'DELETE',
        })
      )
    })
  })

  describe('bankAccounts.setPrimary', () => {
    it('generates correct endpoint', () => {
      const uuid = '123-456-789'
      expect(endpoints.bankAccounts.setPrimary(uuid)).toBe('/api/bank-accounts/123-456-789/set-primary')
    })

    it('calls POST /api/bank-accounts/{uuid}/set-primary', async () => {
      await api.post(endpoints.bankAccounts.setPrimary('abc-123'), {})
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts/abc-123/set-primary'),
        expect.objectContaining({
          method: 'POST',
        })
      )
    })
  })

  describe('bankAccounts.verify', () => {
    it('generates correct endpoint', () => {
      const uuid = '123-456-789'
      expect(endpoints.bankAccounts.verify(uuid)).toBe('/api/bank-accounts/123-456-789/verify')
    })

    it('calls POST /api/bank-accounts/{uuid}/verify', async () => {
      const data = { verification_method: 'manual' }
      await api.post(endpoints.bankAccounts.verify('abc-123'), data)
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts/abc-123/verify'),
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(data),
        })
      )
    })
  })

  describe('bankAccounts.logs', () => {
    it('generates correct endpoint', () => {
      const uuid = '123-456-789'
      expect(endpoints.bankAccounts.logs(uuid)).toBe('/api/bank-accounts/123-456-789/logs')
    })

    it('calls GET /api/bank-accounts/{uuid}/logs', async () => {
      await api.get(endpoints.bankAccounts.logs('abc-123'))
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts/abc-123/logs'),
        expect.objectContaining({
          method: 'GET',
        })
      )
    })
  })

  describe('bankAccounts.banks', () => {
    it('has correct endpoint', () => {
      expect(endpoints.bankAccounts.banks).toBe('/api/bank-accounts/banks')
    })

    it('calls GET /api/bank-accounts/banks', async () => {
      await api.get(endpoints.bankAccounts.banks)
      
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/bank-accounts/banks'),
        expect.objectContaining({
          method: 'GET',
        })
      )
    })
  })
})

