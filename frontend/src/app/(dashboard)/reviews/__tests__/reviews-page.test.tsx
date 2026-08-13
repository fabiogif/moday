import { render, screen, waitFor, fireEvent } from '@testing-library/react'
import ReviewsPage from '../page'
import {
  useAuthenticatedReviews,
  useAuthenticatedReviewStats,
  useMutation,
} from '@/hooks/use-authenticated-api'
import { toast } from 'sonner'
import '@testing-library/jest-dom'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: () => ({
    token: 'mock-token',
    isAuthenticated: true,
    isLoading: false,
    user: { name: 'Test User', email: 'test@test.com' },
  }),
}))

jest.mock('@/hooks/use-authenticated-api')

jest.mock('sonner', () => ({
  toast: {
    error: jest.fn(),
    success: jest.fn(),
  },
}))

describe('ReviewsPage', () => {
  const mockStats = {
    total: 150,
    average_rating: 4.7,
    pending_count: 5,
    approved_count: 140,
    rejected_count: 5,
    featured_count: 8
  }

  const mockReviews = [
    {
      uuid: 'review-1',
      rating: 5,
      comment: 'Excelente!',
      customer_name: 'João Silva',
      status: 'pending',
      is_featured: false,
      created_at: '04/11/2025 10:30',
      created_at_human: 'há 2 horas',
      order: {
        id: 1,
        identify: 'ABC123'
      }
    },
    {
      uuid: 'review-2',
      rating: 4,
      comment: 'Muito bom!',
      customer_name: 'Maria Santos',
      status: 'approved',
      is_featured: false,
      created_at: '04/11/2025 09:15',
      created_at_human: 'há 3 horas',
      order: {
        id: 2,
        identify: 'DEF456'
      }
    }
  ]

  const mockRefetch = jest.fn()
  const mockMutate = jest.fn().mockResolvedValue({})

  beforeEach(() => {
    jest.clearAllMocks()

    ;(useAuthenticatedReviews as jest.Mock).mockImplementation((status?: string) => ({
      data: status === 'pending' ? [mockReviews[0]] : mockReviews,
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    }))

    ;(useAuthenticatedReviewStats as jest.Mock).mockReturnValue({
      data: mockStats,
      loading: false,
      error: null,
      refetch: jest.fn(),
      isAuthenticated: true,
    })

    ;(useMutation as jest.Mock).mockReturnValue({
      mutate: mockMutate,
      loading: false,
    })
  })

  it('deve renderizar a página corretamente', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('Total de Avaliações')).toBeInTheDocument()
    })
  })

  it('deve exibir estatísticas nos cards', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('150')).toBeInTheDocument()
      expect(screen.getByText('140')).toBeInTheDocument()
      expect(screen.getByText('8')).toBeInTheDocument()
      expect(screen.getAllByText('5').length).toBeGreaterThan(0)
    })
  })

  it('deve listar avaliações na tabela', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
      expect(screen.getByText('Maria Santos')).toBeInTheDocument()
    })
  })

  it('deve aprovar avaliação pendente', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    const approveButtons = screen.getAllByTitle('Aprovar')
    fireEvent.click(approveButtons[0])

    await waitFor(() => {
      expect(screen.getAllByText('Aprovar Avaliação').length).toBeGreaterThan(0)
    })

    fireEvent.click(screen.getByRole('button', { name: 'Aprovar Avaliação' }))

    await waitFor(() => {
      expect(toast.success).toHaveBeenCalledWith('Avaliação aprovada!')
    })

    expect(mockMutate).toHaveBeenCalledWith('/api/reviews/review-1/approve', 'POST', {})
  })

  it('deve abrir modal ao rejeitar avaliação', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    const rejectButtons = screen.getAllByTitle('Rejeitar')
    fireEvent.click(rejectButtons[0])

    await waitFor(() => {
      expect(screen.getAllByText('Rejeitar Avaliação').length).toBeGreaterThan(0)
      expect(screen.getByText(/Informe o motivo da rejeição/i)).toBeInTheDocument()
    })
  })

  it('deve rejeitar avaliação com motivo', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    const rejectButtons = screen.getAllByTitle('Rejeitar')
    fireEvent.click(rejectButtons[0])

    await waitFor(() => {
      expect(screen.getAllByText('Rejeitar Avaliação').length).toBeGreaterThan(0)
    })

    const textarea = screen.getByPlaceholderText(/Ex: Conteúdo inadequado/)
    fireEvent.change(textarea, { target: { value: 'Linguagem ofensiva' } })

    fireEvent.click(screen.getByRole('button', { name: 'Rejeitar Avaliação' }))

    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledWith('/api/reviews/review-1/reject', 'POST', {
        reason: 'Linguagem ofensiva'
      })
    })

    await waitFor(() => {
      expect(toast.success).toHaveBeenCalledWith('Avaliação rejeitada')
    })
  })

  it('deve toggle featured em avaliação aprovada', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('Maria Santos')).toBeInTheDocument()
    })

    const featuredButtons = screen.getAllByTitle(/Destacar|Remover destaque/)
    fireEvent.click(featuredButtons[0])

    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledWith('/api/reviews/review-2/toggle-featured', 'POST', {})
    })
  })

  it('deve deletar avaliação após confirmação', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    const deleteButtons = screen.getAllByTitle('Deletar')
    fireEvent.click(deleteButtons[0])

    await waitFor(() => {
      expect(screen.getAllByText('Excluir Avaliação').length).toBeGreaterThan(0)
    })

    fireEvent.click(screen.getByRole('button', { name: 'Excluir Avaliação' }))

    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledWith('/api/reviews/review-1', 'DELETE')
    })

    await waitFor(() => {
      expect(toast.success).toHaveBeenCalledWith('Avaliação removida')
    })
  })

  it('deve filtrar por status', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    const statusSelect = screen.getAllByRole('combobox')[0]
    fireEvent.click(statusSelect)

    const pendingOptions = screen.getAllByText('Pendentes')
    fireEvent.click(pendingOptions[pendingOptions.length - 1])

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
      expect(screen.queryByText('Maria Santos')).not.toBeInTheDocument()
    })
  })

  it('deve buscar por texto', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    const searchInput = screen.getByPlaceholderText('Buscar por cliente, comentário...')
    fireEvent.change(searchInput, { target: { value: 'João' } })

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
      expect(screen.queryByText('Maria Santos')).not.toBeInTheDocument()
    })
  })

  it('deve filtrar por rating', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    const comboboxes = screen.getAllByRole('combobox')
    const ratingSelect = comboboxes[1]

    fireEvent.click(ratingSelect)

    const option = screen.getByText('5 estrelas')
    fireEvent.click(option)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
      expect(screen.queryByText('Maria Santos')).not.toBeInTheDocument()
    })
  })

  it('deve mostrar mensagem quando não houver avaliações', async () => {
    ;(useAuthenticatedReviews as jest.Mock).mockReturnValue({
      data: [],
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('Nenhuma avaliação encontrada.')).toBeInTheDocument()
    })
  })

  it('deve renderizar estrelas corretamente na tabela', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      const stars = document.querySelectorAll('svg')
      expect(stars.length).toBeGreaterThan(0)
    })
  })

  it('deve mostrar ícone de troféu para featured na tabela', async () => {
    const reviewWithFeatured = [
      { ...mockReviews[1], is_featured: true }
    ]

    ;(useAuthenticatedReviews as jest.Mock).mockReturnValue({
      data: reviewWithFeatured,
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    render(<ReviewsPage />)

    await waitFor(() => {
      const awards = document.querySelectorAll('svg.lucide-award')
      expect(awards.length).toBeGreaterThan(0)
    })
  })

  it('deve mostrar botões corretos conforme status', async () => {
    render(<ReviewsPage />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    expect(screen.getByTitle('Aprovar')).toBeInTheDocument()
    expect(screen.getByTitle('Rejeitar')).toBeInTheDocument()

    const featuredButtons = screen.queryAllByTitle(/Destacar|Remover destaque/)
    expect(featuredButtons.length).toBeGreaterThan(0)

    const deleteButtons = screen.getAllByTitle('Deletar')
    expect(deleteButtons.length).toBe(2)
  })
})
