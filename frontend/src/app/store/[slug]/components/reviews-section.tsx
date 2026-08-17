'use client'

import { useEffect, useState, useCallback } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { Star, MessageSquare, TrendingUp, Award } from 'lucide-react'
import { apiClient, endpoints } from '@/lib/api-client'

interface Review {
  uuid: string
  rating: number
  comment: string | null
  customer_name: string
  is_featured: boolean
  created_at: string
  created_at_human: string
}

interface ReviewStats {
  total: number
  average_rating: number
  rating_distribution: {
    '5': number
    '4': number
    '3': number
    '2': number
    '1': number
  }
  rating_percentages: {
    '5': number
    '4': number
    '3': number
    '2': number
    '1': number
  }
}

interface ReviewsSectionProps {
  tenantSlug: string
}

export function ReviewsSection({ tenantSlug }: ReviewsSectionProps) {
  const [reviews, setReviews] = useState<Review[]>([])
  const [stats, setStats] = useState<ReviewStats | null>(null)
  const [loading, setLoading] = useState(true)
  const [showAll, setShowAll] = useState(false)

  const fetchData = useCallback(async () => {
    try {
      setLoading(true)

      // Buscar estatísticas e avaliações em paralelo
      const [statsResponse, reviewsResponse] = await Promise.all([
        apiClient.get<ReviewStats>(endpoints.reviews.public.stats(tenantSlug)),
        apiClient.get<Review[]>(endpoints.reviews.public.list(tenantSlug))
      ])

      if (statsResponse.success && statsResponse.data) {
        setStats(statsResponse.data)
      }

      if (reviewsResponse.success && reviewsResponse.data) {
        setReviews(Array.isArray(reviewsResponse.data) ? reviewsResponse.data : [])
      }
    } catch (error: any) {
      // Erro silencioso - seção não aparece se houver erro
      if (process.env.NODE_ENV === 'development') {

      }
    } finally {
      setLoading(false)
    }
  }, [tenantSlug])

  useEffect(() => {
    fetchData()
  }, [fetchData])

  const renderStars = (rating: number, size: 'sm' | 'md' | 'lg' = 'md') => {
    const sizeClasses = {
      sm: 'h-3 w-3',
      md: 'h-4 w-4',
      lg: 'h-6 w-6'
    }

    return (
      <div className="flex gap-0.5">
        {[1, 2, 3, 4, 5].map((star) => (
          <Star
            key={star}
            className={`${sizeClasses[size]} ${
              star <= rating
                ? 'fill-yellow-400 text-yellow-400'
                : 'text-gray-300'
            }`}
          />
        ))}
      </div>
    )
  }

  const getRatingLabel = (stars: number): string => {
    const labels = {
      5: 'Excelente',
      4: 'Bom',
      3: 'Regular',
      2: 'Ruim',
      1: 'Péssimo'
    }
    return labels[stars as keyof typeof labels] || ''
  }

  if (loading) {
    return (
      <section className="py-8 bg-muted/30">
        <div className="w-full max-w-6xl mx-auto px-4 md:px-6 lg:px-8">
          <div className="flex items-center justify-center py-12">
            <div className="animate-pulse space-y-4 w-full max-w-4xl mx-auto">
              <div className="h-8 bg-gray-200 rounded w-1/3"></div>
              <div className="h-32 bg-gray-200 rounded"></div>
              <div className="space-y-3">
                {[1, 2, 3].map((i) => (
                  <div key={i} className="h-24 bg-gray-200 rounded"></div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>
    )
  }

  // Não mostrar seção se não houver avaliações
  if (!stats || stats.total === 0) {
    return null
  }

  // Separar avaliações em destaque e normais
  const featuredReviews = reviews.filter(r => r.is_featured)
  const regularReviews = reviews.filter(r => !r.is_featured)
  
  // Exibir no máximo 6 avaliações inicialmente
  const displayedReviews = showAll 
    ? [...featuredReviews, ...regularReviews]
    : [...featuredReviews, ...regularReviews].slice(0, 6)

  return (
    <section className="py-12 bg-muted/30">
      <div className="w-full max-w-6xl mx-auto px-4 md:px-6 lg:px-8">
        {/* Header */}
        <div className="text-center mb-10">
          <h2 className="text-3xl font-bold mb-2 flex items-center justify-center gap-2">
            <MessageSquare className="h-8 w-8 text-primary" />
            O que nossos clientes dizem
          </h2>
          <p className="text-muted-foreground">
            Confira as avaliações de quem já experimentou
          </p>
        </div>

        {/* Estatísticas */}
        <div className="grid md:grid-cols-2 gap-6 mb-10 max-w-4xl mx-auto">
          {/* Rating Geral */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Avaliação Geral</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-6">
                <div className="text-center">
                  <div className="text-5xl font-bold text-primary mb-2">
                    {stats.average_rating.toFixed(1)}
                  </div>
                  {renderStars(Math.round(stats.average_rating), 'lg')}
                  <p className="text-sm text-muted-foreground mt-2">
                    {stats.total} {stats.total === 1 ? 'avaliação' : 'avaliações'}
                  </p>
                </div>

                <div className="flex-1 space-y-2">
                  {[5, 4, 3, 2, 1].map((rating) => (
                    <div key={rating} className="flex items-center gap-2">
                      <span className="text-sm font-medium w-12">
                        {rating} {renderStars(1, 'sm')}
                      </span>
                      <Progress 
                        value={stats.rating_percentages[rating.toString() as keyof typeof stats.rating_percentages]} 
                        className="h-2"
                      />
                      <span className="text-xs text-muted-foreground w-12 text-right">
                        {stats.rating_distribution[rating.toString() as keyof typeof stats.rating_distribution]}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Destaques */}
          <Card className="bg-primary/5 border-primary/20">
            <CardHeader>
              <CardTitle className="text-lg flex items-center gap-2">
                <TrendingUp className="h-5 w-5 text-primary" />
                Destaques
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center gap-4">
                  <div className="flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100">
                    <Star className="h-8 w-8 fill-yellow-400 text-yellow-400" />
                  </div>
                  <div>
                    <div className="text-2xl font-bold text-primary">
                      {Math.round((stats.rating_percentages['5'] + stats.rating_percentages['4']))}%
                    </div>
                    <p className="text-sm text-muted-foreground">
                      Avaliações positivas (4-5 estrelas)
                    </p>
                  </div>
                </div>

                {featuredReviews.length > 0 && (
                  <div className="flex items-center gap-2 pt-2 border-t">
                    <Award className="h-5 w-5 text-amber-500" />
                    <span className="text-sm font-medium">
                      {featuredReviews.length} {featuredReviews.length === 1 ? 'avaliação em destaque' : 'avaliações em destaque'}
                    </span>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Lista de Avaliações */}
        <div className="space-y-4 max-w-4xl mx-auto">
          {displayedReviews.map((review) => (
            <Card 
              key={review.uuid} 
              className={review.is_featured ? 'border-amber-300 bg-amber-50/50' : ''}
            >
              <CardContent className="p-6">
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 text-primary font-semibold text-lg">
                      {review.customer_name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <p className="font-semibold">{review.customer_name}</p>
                        {review.is_featured && (
                          <Award className="h-4 w-4 text-amber-500" />
                        )}
                      </div>
                      <div className="flex items-center gap-2 mt-1">
                        {renderStars(review.rating, 'sm')}
                        <span className="text-xs text-muted-foreground">
                          • {review.created_at_human}
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  {review.is_featured && (
                    <div className="flex items-center gap-1 text-xs font-medium text-amber-600 bg-amber-100 px-2 py-1 rounded-full">
                      <Award className="h-3 w-3" />
                      Destaque
                    </div>
                  )}
                </div>

                {review.comment && (
                  <p className="text-muted-foreground pl-[60px]">
                    "{review.comment}"
                  </p>
                )}
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Botão Ver Mais */}
        {reviews.length > 6 && !showAll && (
          <div className="text-center mt-8">
            <Button
              variant="outline"
              size="lg"
              onClick={() => setShowAll(true)}
              className="min-w-[200px]"
            >
              Ver mais avaliações ({reviews.length - 6} restantes)
            </Button>
          </div>
        )}

        {showAll && reviews.length > 6 && (
          <div className="text-center mt-8">
            <Button
              variant="outline"
              size="lg"
              onClick={() => setShowAll(false)}
              className="min-w-[200px]"
            >
              Ver menos
            </Button>
          </div>
        )}
      </div>
    </section>
  )
}

