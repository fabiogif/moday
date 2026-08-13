'use client'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Star, MessageSquare, ArrowRight, Loader2, AlertCircle } from 'lucide-react'
import Link from 'next/link'
import { useAuthenticatedRecentReviews } from '@/hooks/use-authenticated-api'

interface Review {
  uuid: string
  rating: number
  comment: string | null
  customer_name: string
  status: 'pending' | 'approved' | 'rejected'
  created_at: string
  created_at_human: string
  order?: {
    id: number
    identify: string
  }
}

interface RecentReviewsCardProps {
  limit?: number
}

export function RecentReviewsCard({ limit = 5 }: RecentReviewsCardProps) {
  const { data, loading, error, refetch } = useAuthenticatedRecentReviews(limit)

  const reviews = Array.isArray(data) ? data : []

  const renderStars = (rating: number) => {
    return (
      <div className="flex gap-0.5">
        {[1, 2, 3, 4, 5].map((star) => (
          <Star
            key={star}
            className={`h-4 w-4 ${
              star <= rating
                ? 'fill-yellow-400 text-yellow-400'
                : 'text-gray-300'
            }`}
          />
        ))}
      </div>
    )
  }

  const truncateText = (text: string | null, maxLength: number) => {
    if (!text) return null
    if (text.length <= maxLength) return text
    return text.substring(0, maxLength) + '...'
  }

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MessageSquare className="h-5 w-5" />
            Avaliações Recentes
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-center py-8">
            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
          </div>
        </CardContent>
      </Card>
    )
  }

  if (error) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MessageSquare className="h-5 w-5" />
            Avaliações Recentes
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-col items-center justify-center py-8 text-center">
            <AlertCircle className="h-8 w-8 text-destructive mb-2" />
            <p className="text-sm text-muted-foreground">{error}</p>
            <Button
              variant="outline"
              size="sm"
              onClick={refetch}
              className="mt-4"
            >
              Tentar Novamente
            </Button>
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="flex items-center gap-2 text-xl">
              <MessageSquare className="h-5 w-5" />
              Avaliações Recentes
            </CardTitle>
            <CardDescription>Últimas avaliações dos seus clientes</CardDescription>
          </div>
          <Link href="/reviews">
            <Button variant="ghost" size="sm">
              Ver todas
              <ArrowRight className="h-4 w-4 ml-2" />
            </Button>
          </Link>
        </div>
      </CardHeader>
      <CardContent>
        {reviews.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-8 text-center">
            <MessageSquare className="h-12 w-12 text-muted-foreground/50 mb-3" />
            <p className="text-sm text-muted-foreground">
              Nenhuma avaliação ainda.
            </p>
            <p className="text-xs text-muted-foreground mt-1">
              As avaliações dos clientes aparecerão aqui.
            </p>
          </div>
        ) : (
          <div className="space-y-4">
            {reviews.map((review) => (
              <div
                key={review.uuid}
                className="flex flex-col gap-2 p-4 rounded-lg border bg-card hover:bg-accent/50 transition-colors"
              >
                {/* Header */}
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-3">
                    <div className="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 text-primary font-semibold">
                      {review.customer_name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                      <p className="font-semibold text-sm">{review.customer_name}</p>
                      <p className="text-xs text-muted-foreground">
                        {review.created_at_human}
                      </p>
                    </div>
                  </div>
                  {renderStars(review.rating)}
                </div>

                {/* Comentário */}
                {review.comment && (
                  <p className="text-sm text-muted-foreground pl-[52px]">
                    {truncateText(review.comment, 100)}
                  </p>
                )}

                {/* Footer */}
                <div className="flex items-center justify-between pl-[52px]">
                  {review.order && (
                    <p className="text-xs text-muted-foreground">
                      Pedido <span className="font-medium">#{review.order.identify}</span>
                    </p>
                  )}
                  <Badge
                    variant={
                      review.status === 'approved'
                        ? 'default'
                        : review.status === 'pending'
                        ? 'secondary'
                        : 'destructive'
                    }
                    className="text-xs"
                  >
                    {review.status === 'approved'
                      ? 'Aprovada'
                      : review.status === 'pending'
                      ? 'Pendente'
                      : 'Rejeitada'}
                  </Badge>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
