import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ClientFormDialog } from '../components/client-form-dialog'
import { apiClient } from '@/lib/api-client'

// Usa os hooks reais de useViaCEP/useCitiesByState (ao contrário de
// client-form-dialog.test.tsx) para reproduzir a corrida real entre o
// preenchimento do CEP e o carregamento assíncrono da lista de cidades.
jest.mock('@/lib/api-client', () => {
  const actual = jest.requireActual('@/lib/api-client')
  return {
    ...actual,
    apiClient: {
      post: jest.fn(),
      get: jest.fn(),
    },
  }
})

jest.mock('@/components/ui/error-toast', () => ({
  showErrorToast: jest.fn(),
  showSuccessToast: jest.fn(),
}))

const mockApiPost = apiClient.post as jest.MockedFunction<typeof apiClient.post>
const mockApiGet = apiClient.get as jest.MockedFunction<typeof apiClient.get>

describe('ClientFormDialog - preenchimento de Cidade pelo CEP', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockApiPost.mockResolvedValue({ success: true, data: { valid: true, step: 0 } } as any)

    mockApiGet.mockImplementation(async (url: string) => {
      if (url === '/api/cep/01001000') {
        return {
          success: true,
          data: {
            address: 'Praça da Sé',
            neighborhood: 'Sé',
            complement: '',
            zip_code: '01001-000',
            state: { id: 26, uf: 'SP', name: 'São Paulo', ibge_code: '35' },
            city: {
              id: 999,
              name: 'São Paulo',
              ibge_code: '3550308',
              state: { id: 26, uf: 'SP' },
            },
          },
        } as any
      }
      if (url === '/api/states') {
        return { success: true, data: [{ id: 26, uf: 'SP', name: 'São Paulo' }] } as any
      }
      if (url === '/api/states/SP/cities') {
        // A lista de cidades só chega depois que o CEP já preencheu
        // estado+cidade no formulário — é essa janela que expõe o bug.
        await new Promise((resolve) => setTimeout(resolve, 50))
        return {
          success: true,
          data: {
            state: { id: 26, uf: 'SP', name: 'São Paulo' },
            cities: [{ id: 999, name: 'São Paulo', is_capital: true }],
          },
        } as any
      }
      throw new Error(`GET inesperado: ${url}`)
    })
  })

  test('cidade permanece preenchida mesmo depois que a lista de cidades termina de carregar', async () => {
    const user = userEvent.setup()
    render(
      <ClientFormDialog
        onAddClient={jest.fn()}
        onEditClient={jest.fn()}
        editingClient={null}
        open={true}
        onOpenChange={jest.fn()}
        hideTrigger
      />
    )

    await user.type(screen.getByLabelText(/Nome Completo/i), 'Maria Souza')
    await user.type(screen.getByLabelText(/^CPF \*/i), '52998224725')
    await user.type(screen.getByLabelText(/^Email$/i), 'maria@example.com')
    await user.type(screen.getByLabelText(/^Telefone \*/i), '11987654321')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/Cliente Ativo/i)).toBeInTheDocument()

    await user.type(screen.getByPlaceholderText('01234-567'), '01001000')
    await user.tab()

    await waitFor(() => {
      expect(screen.getByPlaceholderText('Rua das Flores, Av. Paulista')).toHaveValue('Praça da Sé')
    })

    const cityCombobox = screen.getByRole('combobox', { name: /Cidade/i })

    // Espera a lista de cidades (que demora 50ms no mock) terminar de
    // carregar e confirma que a cidade preenchida pelo CEP não some.
    await waitFor(
      () => {
        expect(cityCombobox).toHaveTextContent('São Paulo')
      },
      { timeout: 2000 }
    )
  })
})
