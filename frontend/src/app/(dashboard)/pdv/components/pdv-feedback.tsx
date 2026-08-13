"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Badge } from "@/components/ui/badge"
import { 
  MessageSquare, 
  Smile, 
  Frown, 
  Meh, 
  Star,
  Bug,
  Lightbulb,
  Heart,
  X
} from "lucide-react"
import { cn } from "@/lib/utils"
import { toast } from "sonner"
import { apiClient, endpoints } from "@/lib/api-client"

interface PDVFeedbackProps {
  open: boolean
  onOpenChange: (open: boolean) => void
}

type FeedbackType = 'suggestion' | 'bug' | 'praise' | 'other'
type FeedbackRating = 1 | 2 | 3 | 4 | 5

/**
 * Sistema de Coleta de Feedback do PDV
 * 
 * Fase 4: Permite que operadores enviem feedback sobre o sistema
 */
export function PDVFeedback({ open, onOpenChange }: PDVFeedbackProps) {
  const [feedbackType, setFeedbackType] = useState<FeedbackType>('suggestion')
  const [rating, setRating] = useState<FeedbackRating | null>(null)
  const [message, setMessage] = useState("")
  const [quickEmoji, setQuickEmoji] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  const handleQuickFeedback = async (emoji: string) => {
    setQuickEmoji(emoji)
    setSubmitting(true)

    try {
      const emojiMap: Record<string, { type: FeedbackType; message: string }> = {
        '😊': { type: 'praise', message: 'Feedback positivo' },
        '😐': { type: 'other', message: 'Feedback neutro' },
        '😞': { type: 'bug', message: 'Problema encontrado' },
      }

      const feedback = emojiMap[emoji] || { type: 'other', message: 'Feedback rápido' }

      const response = await apiClient.post(endpoints.pdvFeedback.create, {
        type: feedback.type,
        quick_emoji: emoji,
        message: feedback.message,
      })

      if (response.success) {
        toast.success("Feedback enviado! Obrigado pela sua contribuição.")
        handleClose()
      }
    } catch (error: any) {
      toast.error(error?.message || "Erro ao enviar feedback. Tente novamente.")
    } finally {
      setSubmitting(false)
    }
  }

  const handleSubmit = async () => {
    if (!message.trim() && !rating) {
      toast.error("Por favor, forneça um feedback ou uma avaliação.")
      return
    }

    setSubmitting(true)

    try {
      const response = await apiClient.post(endpoints.pdvFeedback.create, {
        type: feedbackType,
        rating,
        message: message.trim() || null,
        quick_emoji: quickEmoji,
        metadata: {
          browser: typeof window !== 'undefined' ? navigator.userAgent : null,
          timestamp: new Date().toISOString(),
        },
      })

      if (response.success) {
        toast.success("Feedback enviado com sucesso! Obrigado pela sua contribuição.")
        handleClose()
      }
    } catch (error: any) {
      toast.error(error?.message || "Erro ao enviar feedback. Tente novamente.")
    } finally {
      setSubmitting(false)
    }
  }

  const handleClose = () => {
    setFeedbackType('suggestion')
    setRating(null)
    setMessage("")
    setQuickEmoji(null)
    onOpenChange(false)
  }

  const getTypeIcon = (type: FeedbackType) => {
    switch (type) {
      case 'suggestion':
        return <Lightbulb className="h-4 w-4" />
      case 'bug':
        return <Bug className="h-4 w-4" />
      case 'praise':
        return <Heart className="h-4 w-4" />
      default:
        return <MessageSquare className="h-4 w-4" />
    }
  }

  const getTypeLabel = (type: FeedbackType) => {
    switch (type) {
      case 'suggestion':
        return 'Sugestão'
      case 'bug':
        return 'Problema/Bug'
      case 'praise':
        return 'Elogio'
      default:
        return 'Outro'
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <MessageSquare className="h-5 w-5 text-primary" />
            Envie seu Feedback
          </DialogTitle>
          <DialogDescription>
            Sua opinião é importante para melhorarmos o PDV. Compartilhe suas sugestões, problemas ou elogios.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Feedback Rápido (Emoji) */}
          <div className="space-y-3">
            <Label>Feedback Rápido</Label>
            <div className="flex gap-3 justify-center">
              <Button
                variant={quickEmoji === '😊' ? 'default' : 'outline'}
                size="lg"
                className={cn(
                  "h-16 w-16 text-3xl rounded-full",
                  quickEmoji === '😊' && "bg-green-100 border-green-300 hover:bg-green-200"
                )}
                onClick={() => handleQuickFeedback('😊')}
                disabled={submitting}
              >
                😊
              </Button>
              <Button
                variant={quickEmoji === '😐' ? 'default' : 'outline'}
                size="lg"
                className={cn(
                  "h-16 w-16 text-3xl rounded-full",
                  quickEmoji === '😐' && "bg-yellow-100 border-yellow-300 hover:bg-yellow-200"
                )}
                onClick={() => handleQuickFeedback('😐')}
                disabled={submitting}
              >
                😐
              </Button>
              <Button
                variant={quickEmoji === '😞' ? 'default' : 'outline'}
                size="lg"
                className={cn(
                  "h-16 w-16 text-3xl rounded-full",
                  quickEmoji === '😞' && "bg-red-100 border-red-300 hover:bg-red-200"
                )}
                onClick={() => handleQuickFeedback('😞')}
                disabled={submitting}
              >
                😞
              </Button>
            </div>
            <p className="text-xs text-center text-muted-foreground">
              Toque em um emoji para enviar feedback rápido
            </p>
          </div>

          <div className="relative">
            <div className="absolute inset-0 flex items-center">
              <span className="w-full border-t" />
            </div>
            <div className="relative flex justify-center text-xs uppercase">
              <span className="bg-background px-2 text-muted-foreground">ou</span>
            </div>
          </div>

          {/* Tipo de Feedback */}
          <div className="space-y-3">
            <Label>Tipo de Feedback</Label>
            <RadioGroup value={feedbackType} onValueChange={(value) => setFeedbackType(value as FeedbackType)}>
              <div className="grid grid-cols-2 gap-3">
                {(['suggestion', 'bug', 'praise', 'other'] as FeedbackType[]).map((type) => (
                  <div key={type}>
                    <RadioGroupItem value={type} id={type} className="peer sr-only" />
                    <Label
                      htmlFor={type}
                      className={cn(
                        "flex flex-col items-center justify-center rounded-lg border-2 p-4 cursor-pointer transition-all",
                        "hover:bg-accent hover:text-accent-foreground",
                        feedbackType === type && "border-primary bg-primary/10"
                      )}
                    >
                      {getTypeIcon(type)}
                      <span className="mt-2 text-sm font-medium">{getTypeLabel(type)}</span>
                    </Label>
                  </div>
                ))}
              </div>
            </RadioGroup>
          </div>

          {/* Avaliação por Estrelas */}
          <div className="space-y-3">
            <Label>Avaliação</Label>
            <div className="flex gap-2 justify-center">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  key={star}
                  type="button"
                  onClick={() => setRating(star as FeedbackRating)}
                  className={cn(
                    "transition-all hover:scale-110",
                    rating && rating >= star ? "text-yellow-400" : "text-muted-foreground"
                  )}
                  disabled={submitting}
                >
                  <Star
                    className={cn(
                      "h-8 w-8",
                      rating && rating >= star ? "fill-current" : "fill-none"
                    )}
                  />
                </button>
              ))}
            </div>
            {rating && (
              <p className="text-xs text-center text-muted-foreground">
                {rating === 5 && "Excelente!"}
                {rating === 4 && "Muito bom!"}
                {rating === 3 && "Bom"}
                {rating === 2 && "Regular"}
                {rating === 1 && "Precisa melhorar"}
              </p>
            )}
          </div>

          {/* Mensagem */}
          <div className="space-y-2">
            <Label htmlFor="feedback-message">Mensagem (opcional)</Label>
            <Textarea
              id="feedback-message"
              placeholder="Descreva seu feedback em detalhes..."
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className="min-h-[100px]"
              disabled={submitting}
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={handleClose} disabled={submitting}>
            <X className="mr-2 h-4 w-4" />
            Cancelar
          </Button>
          <Button onClick={handleSubmit} disabled={submitting || (!message.trim() && !rating && !quickEmoji)}>
            {submitting ? (
              <>
                <span className="mr-2">Enviando...</span>
              </>
            ) : (
              <>
                <MessageSquare className="mr-2 h-4 w-4" />
                Enviar Feedback
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

