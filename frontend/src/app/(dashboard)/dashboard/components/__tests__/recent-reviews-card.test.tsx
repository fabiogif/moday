import { render, screen, waitFor, fireEvent } from '@testing-library/react'
import { RecentReviewsCard } from '../recent-reviews-card'
import { useAuthenticatedRecentReviews } from '@/hooks/use-authenticated-api'
import '@testing-library/jest-dom'

jest.mock('@/hooks/use-authenticated-api', () => ({
  useAuthenticatedRecentReviews: jest.fn(),
}))

jest.mock('next/link', () => {
  return ({ children, href }: any) => <a href={href}>{children}</a>
})

const mockUseAuthenticatedRecentReviews =
  useAuthenticatedRecentReviews as jest.MockedFunction<typeof useAuthenticatedRecentReviews>

describe('RecentReviewsCard', () => {
  const mockRefetch = jest.fn()

  const mockReviews = [
    {
      uuid: 'review-1',
      rating: 5,
      comment: 'Excelente serviço!',
      customer_name: 'João Silva',
      status: 'approved',
      created_at: '04/11/2025 10:30',
      created_at_human: 'há 2 horas',
      order: {
        id: 1,
        identify: 'ABC123',
      },
    },
    {
      uuid: 'review-2',
      rating: 4,
      comment: 'Muito bom!',
      customer_name: 'Maria Santos',
      status: 'pending',
      created_at: '04/11/2025 09:15',
      created_at_human: 'há 3 horas',
      order: {
        id: 2,
        identify: 'DEF456',
      },
    },
  ]

  const defaultHookState = {
    data: mockReviews,
    loading: false,
    error: null,
    refetch: mockRefetch,
    isAuthenticated: true,
  }

  beforeEach(() => {
    jest.clearAllMocks()
    mockUseAuthenticatedRecentReviews.mockReturnValue(defaultHookState)
  })

  it('deve renderizar o card corretamente', async () => {
    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('Avaliações Recentes')).toBeInTheDocument()
    })

    expect(screen.getByText('Últimas avaliações dos seus clientes')).toBeInTheDocument()
  })

  it('deve mostrar loading durante carregamento', () => {
    mockUseAuthenticatedRecentReviews.mockReturnValue({
      ...defaultHookState,
      data: null,
      loading: true,
    })

    render(<RecentReviewsCard limit={5} />)

    expect(screen.getByText('Avaliações Recentes')).toBeInTheDocument()
    const spinner = document.querySelector('.animate-spin')
    expect(spinner).toBeInTheDocument()
  })

  it('deve listar as avaliações recebidas', async () => {
    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
      expect(screen.getByText('Maria Santos')).toBeInTheDocument()
    })

    expect(screen.getByText('Excelente serviço!')).toBeInTheDocument()
    expect(screen.getByText('Muito bom!')).toBeInTheDocument()
  })

  it('deve renderizar estrelas corretamente', async () => {
    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })

    const stars = document.querySelectorAll('svg')
    expect(stars.length).toBeGreaterThan(0)
  })

  it('deve mostrar badge de status correto', async () => {
    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('Aprovada')).toBeInTheDocument()
      expect(screen.getByText('Pendente')).toBeInTheDocument()
    })
  })

  it('deve mostrar informações do pedido', async () => {
    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText(/ABC123/)).toBeInTheDocument()
      expect(screen.getByText(/DEF456/)).toBeInTheDocument()
    })
  })

  it('deve mostrar mensagem quando não houver avaliações', async () => {
    mockUseAuthenticatedRecentReviews.mockReturnValue({
      ...defaultHookState,
      data: [],
    })

    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('Nenhuma avaliação ainda.')).toBeInTheDocument()
      expect(screen.getByText('As avaliações dos clientes aparecerão aqui.')).toBeInTheDocument()
    })
  })

  it('deve mostrar erro ao falhar no carregamento', async () => {
    mockUseAuthenticatedRecentReviews.mockReturnValue({
      ...defaultHookState,
      data: null,
      error: 'Erro ao carregar avaliações',
    })

    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('Erro ao carregar avaliações')).toBeInTheDocument()
    })

    expect(screen.getByText('Tentar Novamente')).toBeInTheDocument()
  })

  it('deve permitir tentar novamente após erro', async () => {
    mockUseAuthenticatedRecentReviews.mockReturnValue({
      ...defaultHookState,
      data: null,
      error: 'Erro ao carregar avaliações',
    })

    const { rerender } = render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('Tentar Novamente')).toBeInTheDocument()
    })

    fireEvent.click(screen.getByText('Tentar Novamente'))
    expect(mockRefetch).toHaveBeenCalled()

    mockUseAuthenticatedRecentReviews.mockReturnValue(defaultHookState)
    rerender(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
    })
  })

  it('deve truncar comentários longos', async () => {
    const longCommentReview = {
      ...mockReviews[0],
      comment:
        'Este é um comentário muito longo que deveria ser truncado para caber no card sem quebrar o layout e manter a interface limpa e organizada.',
    }

    mockUseAuthenticatedRecentReviews.mockReturnValue({
      ...defaultHookState,
      data: [longCommentReview],
    })

    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      const comment = screen.getByText(/Este é um comentário muito longo/)
      expect(comment.textContent?.length).toBeLessThanOrEqual(103)
    })
  })

  it('deve chamar hook com limite correto', () => {
    render(<RecentReviewsCard limit={10} />)

    expect(mockUseAuthenticatedRecentReviews).toHaveBeenCalledWith(10)
  })

  it('deve ter link para página de avaliações', async () => {
    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      const link = screen.getByText('Ver todas').closest('a')
      expect(link).toHaveAttribute('href', '/reviews')
    })
  })

  it('deve mostrar avatar com inicial do cliente', async () => {
    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('J')).toBeInTheDocument()
      expect(screen.getByText('M')).toBeInTheDocument()
    })
  })

  it('deve mostrar data relativa', async () => {
    render(<RecentReviewsCard limit={5} />)

    await waitFor(() => {
      expect(screen.getByText('há 2 horas')).toBeInTheDocument()
      expect(screen.getByText('há 3 horas')).toBeInTheDocument()
    })
  })
})
