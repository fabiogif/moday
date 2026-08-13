'use client'

import { useState } from 'react'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import { Star, Send, X } from 'lucide-react'
import { toast } from 'sonner'

interface ReviewModalProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: ReviewData) => Promise<void>
  orderData: {
    id: number
    identify: string
  }
  tenantId: number
  customerData?: {
    name?: string
    email?: string
  }
}

export interface ReviewData {
  rating: number
  comment: string
  terms_accepted: boolean
  customer_name?: string
  customer_email?: string
}

export function ReviewModal({ 
  isOpen, 
  onClose, 
  onSubmit, 
  orderData, 
  tenantId,
  customerData 
}: ReviewModalProps) {
  const [rating, setRating] = useState<number>(0)
  const [hoverRating, setHoverRating] = useState<number>(0)
  const [comment, setComment] = useState<string>('')
  const [termsAccepted, setTermsAccepted] = useState<boolean>(false)
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false)

  const maxCommentLength = 1000
  const commentLength = comment.length

  const handleSubmit = async () => {
    // Validações
    if (rating === 0) {
      toast.error('Selecione uma avaliação de 1 a 5 estrelas')
      return
    }

    if (!termsAccepted) {
      toast.error('Você precisa aceitar os termos para continuar')
      return
    }

    try {
      setIsSubmitting(true)

      const data: ReviewData = {
        rating,
        comment: comment.trim(),
        terms_accepted: termsAccepted,
      }

      // Adicionar dados do cliente se não estiver logado
      if (customerData) {
        data.customer_name = customerData.name
        data.customer_email = customerData.email
      }

      await onSubmit(data)

      // Reset form
      setRating(0)
      setComment('')
      setTermsAccepted(false)
      
      toast.success('Avaliação enviada com sucesso! Será publicada após aprovação.')
      onClose()
    } catch (error: any) {
      toast.error(error.message || 'Erro ao enviar avaliação')
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleSkip = () => {
    setRating(0)
    setComment('')
    setTermsAccepted(false)
    onClose()
  }

  const getRatingText = (stars: number): string => {
    const texts = {
      1: 'Péssimo',
      2: 'Ruim',
      3: 'Regular',
      4: 'Bom',
      5: 'Excelente'
    }
    return texts[stars as keyof typeof texts] || ''
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[500px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-2xl font-bold">Avalie seu Pedido</DialogTitle>
          <DialogDescription>
            Pedido <span className="font-semibold">#{orderData.identify}</span>
            <br />
            Sua opinião é muito importante para nós!
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6 py-4">
          {/* Rating com Estrelas */}
          <div className="space-y-3">
            <Label className="text-base font-semibold">Como você avalia seu pedido?</Label>
            <div className="flex items-center justify-center gap-2">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  key={star}
                  type="button"
                  onClick={() => setRating(star)}
                  onMouseEnter={() => setHoverRating(star)}
                  onMouseLeave={() => setHoverRating(0)}
                  className="transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-primary rounded-full p-1"
                  disabled={isSubmitting}
                >
                  <Star
                    className={`h-10 w-10 ${
                      star <= (hoverRating || rating)
                        ? 'fill-yellow-400 text-yellow-400'
                        : 'text-gray-300'
                    }`}
                  />
                </button>
              ))}
            </div>
            {rating > 0 && (
              <p className="text-center text-sm font-medium text-primary">
                {getRatingText(rating)}
              </p>
            )}
          </div>

          {/* Comentário */}
          <div className="space-y-3">
            <Label htmlFor="comment" className="text-base font-semibold">
              Comentário (opcional)
            </Label>
            <Textarea
              id="comment"
              placeholder="Conte-nos sobre sua experiência..."
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              maxLength={maxCommentLength}
              rows={4}
              disabled={isSubmitting}
              className="resize-none"
            />
            <p className="text-xs text-muted-foreground text-right">
              {commentLength}/{maxCommentLength} caracteres
            </p>
          </div>

          {/* Termo de Aceite */}
          <div className="flex items-start space-x-3 rounded-lg border p-4 bg-muted/50">
            <Checkbox
              id="terms"
              checked={termsAccepted}
              onCheckedChange={(checked) => setTermsAccepted(checked as boolean)}
              disabled={isSubmitting}
            />
            <div className="space-y-1">
              <Label
                htmlFor="terms"
                className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
              >
                Aceito os termos de uso
              </Label>
              <p className="text-xs text-muted-foreground">
                Concordo que minha avaliação seja publicada após aprovação da administração.
                Avaliações com conteúdo inadequado serão rejeitadas.
              </p>
            </div>
          </div>
        </div>

        <DialogFooter className="flex-col sm:flex-row gap-2">
          <Button
            type="button"
            variant="outline"
            onClick={handleSkip}
            disabled={isSubmitting}
            className="w-full sm:w-auto"
          >
            <X className="h-4 w-4 mr-2" />
            Pular
          </Button>
          <Button
            type="button"
            onClick={handleSubmit}
            disabled={isSubmitting || rating === 0 || !termsAccepted}
            className="w-full sm:w-auto"
          >
            <Send className="h-4 w-4 mr-2" />
            {isSubmitting ? 'Enviando...' : 'Enviar Avaliação'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

