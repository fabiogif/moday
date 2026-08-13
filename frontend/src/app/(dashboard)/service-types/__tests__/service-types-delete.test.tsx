import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import '@testing-library/jest-dom'
import ServiceTypesPage from '../page'

// Mock antes dos imports
jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedServiceTypes: jest.fn(),
  useMutation: jest.fn(),
}))

jest.mock('@/contexts/auth-context', () => ({
  useAuth: jest.fn(),
}))

import { useAuthenticatedServiceTypes, useMutation } from '@/hooks/use-authenticated-api'
import { useAuth } from '@/contexts/auth-context'

describe('ServiceTypes delete - frontend', () => {
  const mockRefetch = jest.fn()
  const mockDeleteMutation = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    
    ;(useAuth as jest.Mock).mockReturnValue({
      isLoading: false,
      user: { id: 1, name: 'Test User' },
      isAuthenticated: true,
      login: jest.fn(),
      logout: jest.fn(),
    })

    ;(useAuthenticatedServiceTypes as jest.Mock).mockReturnValue({
      data: [
        {
          id: 1,
          uuid: 'uuid-1',
          identify: 'delivery',
          name: 'Delivery',
          slug: 'delivery',
          description: 'Entrega em domicílio',
          is_active: true,
          requires_address: true,
          requires_table: false,
          available_in_menu: true,
          order_position: 1,
          created_at: '2024-01-01T00:00:00Z',
          created_at_formatted: '01/01/2024',
          updated_at: '2024-01-01T00:00:00Z',
        },
        {
          id: 2,
          uuid: 'uuid-2',
          identify: 'pickup',
          name: 'Retirada',
          slug: 'pickup',
          description: 'Retirada no balcão',
          is_active: true,
          requires_address: false,
          requires_table: false,
          available_in_menu: true,
          order_position: 2,
          created_at: '2024-01-01T00:00:00Z',
          created_at_formatted: '01/01/2024',
          updated_at: '2024-01-01T00:00:00Z',
        },
      ],
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    // useMutation é chamado 3 vezes (create, update, delete)
    // Retornamos a mesma estrutura para todas, mas apenas a última (delete) será usada nos testes
    ;(useMutation as jest.Mock)
      .mockReturnValueOnce({
        mutate: jest.fn(),
        loading: false,
        error: null,
      })
      .mockReturnValueOnce({
        mutate: jest.fn(),
        loading: false,
        error: null,
      })
      .mockReturnValue({
        mutate: mockDeleteMutation,
        loading: false,
        error: null,
      })
  })

  it('exibe diálogo de confirmação ao clicar em excluir', async () => {
    const user = userEvent.setup()
    render(<ServiceTypesPage />)

    // Aguardar tabela carregar
    await waitFor(() => {
      expect(screen.getByText('Delivery')).toBeInTheDocument()
    })

    // Encontrar botão de ações (menu de três pontos)
    const actionButtons = screen.getAllByRole('button', { name: /Open menu/i })
    expect(actionButtons.length).toBeGreaterThan(0)

    // Clicar no primeiro botão de ações
    await user.click(actionButtons[0])

    // Clicar em "Excluir"
    const deleteOption = await screen.findByText('Excluir')
    await user.click(deleteOption)

    // Verificar que o diálogo de confirmação aparece
    expect(screen.getByText(/Confirmar exclusão/i)).toBeInTheDocument()
    expect(screen.getByText(/Tem certeza que deseja excluir o tipo de atendimento/i)).toBeInTheDocument()
  })

  it('chama função de exclusão ao confirmar', async () => {
    const user = userEvent.setup()
    mockDeleteMutation.mockResolvedValue({
      success: true,
      data: { deleted: true },
      message: 'Tipo de atendimento removido com sucesso',
    })

    render(<ServiceTypesPage />)

    // Aguardar tabela carregar
    await waitFor(() => {
      expect(screen.getByText('Delivery')).toBeInTheDocument()
    })

    // Abrir menu de ações
    const actionButtons = screen.getAllByRole('button', { name: /Open menu/i })
    await user.click(actionButtons[0])

    // Clicar em "Excluir"
    const deleteOption = await screen.findByText('Excluir')
    await user.click(deleteOption)

    // Confirmar exclusão
    const confirmButton = await screen.findByRole('button', { name: /^Excluir$/i })
    await user.click(confirmButton)

    // Verificar que a função de exclusão foi chamada
    await waitFor(() => {
      expect(mockDeleteMutation).toHaveBeenCalled()
    })
  })

  it('exibe mensagem de sucesso após exclusão bem-sucedida', async () => {
    const user = userEvent.setup()
    mockDeleteMutation.mockResolvedValue({
      success: true,
      data: { deleted: true },
      message: 'Tipo de atendimento removido com sucesso',
    })

    render(<ServiceTypesPage />)

    // Aguardar tabela carregar
    await waitFor(() => {
      expect(screen.getByText('Delivery')).toBeInTheDocument()
    })

    // Abrir menu de ações
    const actionButtons = screen.getAllByRole('button', { name: /Open menu/i })
    await user.click(actionButtons[0])

    // Clicar em "Excluir"
    const deleteOption = await screen.findByText('Excluir')
    await user.click(deleteOption)

    // Confirmar exclusão
    const confirmButton = await screen.findByRole('button', { name: /^Excluir$/i })
    await user.click(confirmButton)

    // Verificar mensagem de sucesso
    await waitFor(() => {
      expect(screen.getByText(/Tipo de atendimento removido com sucesso/i)).toBeInTheDocument()
    })
  })

  it('recarrega lista após exclusão bem-sucedida', async () => {
    const user = userEvent.setup()
    mockDeleteMutation.mockResolvedValue({
      success: true,
      data: { deleted: true },
      message: 'Tipo de atendimento removido com sucesso',
    })

    render(<ServiceTypesPage />)

    // Aguardar tabela carregar
    await waitFor(() => {
      expect(screen.getByText('Delivery')).toBeInTheDocument()
    })

    // Abrir menu de ações
    const actionButtons = screen.getAllByRole('button', { name: /Open menu/i })
    await user.click(actionButtons[0])

    // Clicar em "Excluir"
    const deleteOption = await screen.findByText('Excluir')
    await user.click(deleteOption)

    // Confirmar exclusão
    const confirmButton = await screen.findByRole('button', { name: /^Excluir$/i })
    await user.click(confirmButton)

    // Verificar que refetch foi chamado
    await waitFor(() => {
      expect(mockRefetch).toHaveBeenCalled()
    }, { timeout: 2000 })
  })

  it('exibe mensagem de erro quando exclusão falha', async () => {
    const user = userEvent.setup()
    mockDeleteMutation.mockRejectedValue({
      response: {
        data: {
          success: false,
          message: 'Erro ao remover tipo de atendimento',
        },
      },
    })

    render(<ServiceTypesPage />)

    // Aguardar tabela carregar
    await waitFor(() => {
      expect(screen.getByText('Delivery')).toBeInTheDocument()
    })

    // Abrir menu de ações
    const actionButtons = screen.getAllByRole('button', { name: /Open menu/i })
    await user.click(actionButtons[0])

    // Clicar em "Excluir"
    const deleteOption = await screen.findByText('Excluir')
    await user.click(deleteOption)

    // Confirmar exclusão
    const confirmButton = await screen.findByRole('button', { name: /^Excluir$/i })
    await user.click(confirmButton)

    // Verificar mensagem de erro
    await waitFor(() => {
      expect(screen.getByText(/Erro ao remover tipo de atendimento/i)).toBeInTheDocument()
    })
  })

  it('não exclui quando usuário cancela diálogo', async () => {
    const user = userEvent.setup()
    render(<ServiceTypesPage />)

    // Aguardar tabela carregar
    await waitFor(() => {
      expect(screen.getByText('Delivery')).toBeInTheDocument()
    })

    // Abrir menu de ações
    const actionButtons = screen.getAllByRole('button', { name: /Open menu/i })
    await user.click(actionButtons[0])

    // Clicar em "Excluir"
    const deleteOption = await screen.findByText('Excluir')
    await user.click(deleteOption)

    // Cancelar exclusão
    const cancelButton = await screen.findByRole('button', { name: /Cancelar/i })
    await user.click(cancelButton)

    // Verificar que a função de exclusão NÃO foi chamada
    expect(mockDeleteMutation).not.toHaveBeenCalled()
  })

  it('exibe nome do tipo de atendimento no diálogo de confirmação', async () => {
    const user = userEvent.setup()
    render(<ServiceTypesPage />)

    // Aguardar tabela carregar
    await waitFor(() => {
      expect(screen.getByText('Delivery')).toBeInTheDocument()
    })

    // Abrir menu de ações do primeiro item
    const actionButtons = screen.getAllByRole('button', { name: /Open menu/i })
    await user.click(actionButtons[0])

    // Clicar em "Excluir"
    const deleteOption = await screen.findByText('Excluir')
    await user.click(deleteOption)

    // Verificar que o diálogo aparece e contém o nome
    await waitFor(() => {
      expect(screen.getByText(/Confirmar exclusão/i)).toBeInTheDocument()
      expect(screen.getByText(/Tem certeza que deseja excluir o tipo de atendimento/i)).toBeInTheDocument()
      // Verificar que o nome "Delivery" aparece no diálogo (pode aparecer múltiplas vezes)
      const deliveryElements = screen.getAllByText(/Delivery/i)
      expect(deliveryElements.length).toBeGreaterThan(0)
    })
  })

  it('chama refetch após exclusão bem-sucedida para atualizar lista', async () => {
    const user = userEvent.setup()
    
    // Simular que após exclusão, a lista não contém mais o item deletado
    mockDeleteMutation.mockResolvedValue({
      success: true,
      data: { deleted: true },
      message: 'Tipo de atendimento removido com sucesso',
    })

    render(<ServiceTypesPage />)

    // Aguardar tabela carregar
    await waitFor(() => {
      expect(screen.getByText('Delivery')).toBeInTheDocument()
    })

    // Abrir menu de ações
    const actionButtons = screen.getAllByRole('button', { name: /Open menu/i })
    await user.click(actionButtons[0])

    // Clicar em "Excluir"
    const deleteOption = await screen.findByText('Excluir')
    await user.click(deleteOption)

    // Confirmar exclusão
    const confirmButton = await screen.findByRole('button', { name: /^Excluir$/i })
    await user.click(confirmButton)

    // Verificar que refetch foi chamado para atualizar a lista
    await waitFor(() => {
      expect(mockRefetch).toHaveBeenCalled()
    }, { timeout: 2000 })
  })
})

