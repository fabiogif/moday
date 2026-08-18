import React from 'react'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import SecuritySettingsPage from '../page'
import { apiClient } from '@/lib/api-client'

jest.mock('@/lib/api-client', () => ({
  apiClient: {
    get: jest.fn(),
    put: jest.fn(),
  },
}))

jest.mock('@/hooks/use-toast', () => ({
  useToast: () => ({
    toast: jest.fn(),
  }),
}))

const mockUserData = {
  success: true,
  data: {
    tenant: {
      uuid: 'test-uuid-123',
      settings: {
        require_email_verification: true,
      },
    },
  },
}

describe('SecuritySettingsPage - Configurações de Segurança', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(apiClient.get as jest.Mock).mockResolvedValue(mockUserData)
  })

  test('carrega o valor atual do toggle a partir do tenant', async () => {
    render(<SecuritySettingsPage />)

    await waitFor(() => {
      expect(screen.getByText('Exigir confirmação de e-mail')).toBeInTheDocument()
    })

    expect(apiClient.get).toHaveBeenCalledWith('/api/auth/me')
  })

  test('salva o novo valor do toggle via PUT /api/tenant/{uuid}', async () => {
    ;(apiClient.put as jest.Mock).mockResolvedValue({ success: true })

    render(<SecuritySettingsPage />)

    const toggle = await screen.findByRole('switch')
    fireEvent.click(toggle)

    const saveButton = await screen.findByRole('button', { name: /salvar alterações/i })
    fireEvent.click(saveButton)

    await waitFor(() => {
      expect(apiClient.put).toHaveBeenCalledWith('/api/tenant/test-uuid-123', {
        settings: { require_email_verification: false },
      })
    })
  })

  test('botão salvar fica desabilitado sem alterações', async () => {
    render(<SecuritySettingsPage />)

    const saveButton = await screen.findByRole('button', { name: /salvar alterações/i })
    expect(saveButton).toBeDisabled()
  })
})
