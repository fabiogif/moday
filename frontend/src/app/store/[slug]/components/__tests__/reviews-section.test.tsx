import { render, screen, waitFor, fireEvent } from '@testing-library/react'
import { ReviewsSection } from '../reviews-section'
import { apiClient } from '@/lib/api-client'
import '@testing-library/jest-dom'

// Mock do apiClient
jest.mock('@/lib/api-client', () => ({
  apiClient: {
    get: jest.fn(),
  },
  endpoints: {
    reviews: {
      public: {
        list: (slug: string) => `/api/store/${slug}/reviews`,
        stats: (slug: string) => `/api/store/${slug}/reviews/stats`,
      }
    }
  }
}))

describe('ReviewsSection', () => {
  const mockStats = {
    total: 150,
    average_rating: 4.7,
    rating_distribution: {
      '5': 85,
      '4': 40,
      '3': 15,
      '2': 7,
      '1': 3
    },
    rating_percentages: {
      '5': 56.7,
      '4': 26.7,
      '3': 10.0,
      '2': 4.7,
      '1': 2.0
    }
  }

  const mockReviews = [
    {
      uuid: 'review-1',
      rating: 5,
      comment: 'Excelente comida! Muito saborosa.',
      customer_name: 'João Silva',
      is_featured: true,
      created_at: '04/11/2025 10:30',
      created_at_human: 'há 2 horas'
    },
    {
      uuid: 'review-2',
      rating: 4,
      comment: 'Muito bom! Recomendo.',
      customer_name: 'Maria Santos',
      is_featured: false,
      created_at: '04/11/2025 09:15',
      created_at_human: 'há 3 horas'
    }
  ]

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('deve não renderizar quando não houver avaliações', async () => {
    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: { total: 0 }, message: '' })
      .mockResolvedValueOnce({ success: true, data: [], message: '' })

    const { container } = render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(container.firstChild).toBeNull()
    })
  })

  it('deve renderizar estatísticas corretamente', async () => {
    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: mockReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(screen.getByText('4.7')).toBeInTheDocument()
      expect(screen.getByText(/150 avaliações/)).toBeInTheDocument()
    })
  })

  it('deve renderizar título da seção', async () => {
    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: mockReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(screen.getByText('O que nossos clientes dizem')).toBeInTheDocument()
    })
  })

  it('deve listar avaliações recebidas', async () => {
    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: mockReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(screen.getByText('João Silva')).toBeInTheDocument()
      expect(screen.getByText('Maria Santos')).toBeInTheDocument()
      expect(screen.getByText(/Excelente comida! Muito saborosa/)).toBeInTheDocument()
    })
  })

  it('deve destacar avaliações featured', async () => {
    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: mockReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(screen.getAllByText('Destaque').length).toBeGreaterThan(0)
    })
  })

  it('deve mostrar distribuição de estrelas', async () => {
    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: mockReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      // Verificar se os números de distribuição estão presentes
      expect(screen.getByText('85')).toBeInTheDocument() // 5 estrelas
      expect(screen.getByText('40')).toBeInTheDocument() // 4 estrelas
    })
  })

  it('deve calcular percentual de avaliações positivas', async () => {
    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: mockReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      // 56.7% + 26.7% = 83% (arredondado)
      const positivePercentage = Math.round(56.7 + 26.7)
      expect(screen.getByText(`${positivePercentage}%`)).toBeInTheDocument()
    })
  })

  it('deve mostrar botão "Ver mais" quando houver mais de 6 avaliações', async () => {
    const manyReviews = Array.from({ length: 10 }, (_, i) => ({
      uuid: `review-${i}`,
      rating: 5,
      comment: `Comentário ${i}`,
      customer_name: `Cliente ${i}`,
      is_featured: false,
      created_at: '04/11/2025',
      created_at_human: 'há 1 hora'
    }))

    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: manyReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(screen.getByText(/Ver mais avaliações \(4 restantes\)/)).toBeInTheDocument()
    })
  })

  it('deve expandir lista ao clicar em "Ver mais"', async () => {
    const manyReviews = Array.from({ length: 10 }, (_, i) => ({
      uuid: `review-${i}`,
      rating: 5,
      comment: `Comentário ${i}`,
      customer_name: `Cliente ${i}`,
      is_featured: false,
      created_at: '04/11/2025',
      created_at_human: 'há 1 hora'
    }))

    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: manyReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(screen.getByText(/Ver mais avaliações/)).toBeInTheDocument()
    })

    // Inicialmente deve mostrar 6
    expect(screen.queryAllByText(/Cliente/).length).toBe(6)

    // Clicar em "Ver mais"
    const verMaisButton = screen.getByText(/Ver mais avaliações/)
    fireEvent.click(verMaisButton)

    // Agora deve mostrar todos (10)
    await waitFor(() => {
      expect(screen.queryAllByText(/Cliente/).length).toBe(10)
    })
  })

  it('deve recolher lista ao clicar em "Ver menos"', async () => {
    const manyReviews = Array.from({ length: 10 }, (_, i) => ({
      uuid: `review-${i}`,
      rating: 5,
      comment: `Comentário ${i}`,
      customer_name: `Cliente ${i}`,
      is_featured: false,
      created_at: '04/11/2025',
      created_at_human: 'há 1 hora'
    }))

    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: manyReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(screen.getByText(/Ver mais avaliações/)).toBeInTheDocument()
    })

    // Expandir
    fireEvent.click(screen.getByText(/Ver mais avaliações/))

    await waitFor(() => {
      expect(screen.getByText('Ver menos')).toBeInTheDocument()
    })

    // Recolher
    fireEvent.click(screen.getByText('Ver menos'))

    await waitFor(() => {
      expect(screen.queryAllByText(/Cliente/).length).toBe(6)
    })
  })

  it('deve priorizar avaliações featured', async () => {
    const reviewsWithFeatured = [
      { ...mockReviews[1], is_featured: false }, // Regular
      { ...mockReviews[0], is_featured: true },  // Featured
    ]

    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: reviewsWithFeatured, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      const cards = screen.getAllByText(/Silva|Santos/)
      // Featured (João Silva) deve aparecer primeiro
      expect(cards[0]).toHaveTextContent('João Silva')
    })
  })

  it('deve mostrar ícone de troféu para featured', async () => {
    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: mockStats, message: '' })
      .mockResolvedValueOnce({ success: true, data: mockReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      // Deve ter pelo menos um ícone de Award (troféu)
      const awards = document.querySelectorAll('svg.lucide-award')
      expect(awards.length).toBeGreaterThan(0)
    })
  })

  it('deve lidar com erro silenciosamente em produção', async () => {
    // Simular ambiente de produção
    const originalEnv = process.env.NODE_ENV
    process.env.NODE_ENV = 'production'

    ;(apiClient.get as jest.Mock).mockRejectedValue(new Error('Erro'))

    const { container } = render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      // Não deve renderizar nada em caso de erro
      expect(container.firstChild).toBeNull()
    })

    process.env.NODE_ENV = originalEnv
  })

  it('deve mostrar contagem de avaliações em destaque', async () => {
    const statsWithFeatured = {
      ...mockStats,
      featured_count: 5
    }

    ;(apiClient.get as jest.Mock)
      .mockResolvedValueOnce({ success: true, data: statsWithFeatured, message: '' })
      .mockResolvedValueOnce({ success: true, data: mockReviews, message: '' })

    render(<ReviewsSection tenantSlug="test-slug" />)

    await waitFor(() => {
      expect(screen.getByText(/1 avaliação em destaque/)).toBeInTheDocument()
    })
  })
})

