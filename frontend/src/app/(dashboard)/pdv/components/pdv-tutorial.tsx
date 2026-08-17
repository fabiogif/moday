"use client"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Progress } from "@/components/ui/progress"
import { Badge } from "@/components/ui/badge"
import { 
  ArrowRight, 
  ArrowLeft, 
  X, 
  HelpCircle, 
  ShoppingCart, 
  Package, 
  CreditCard,
  Utensils,
  CheckCircle2
} from "lucide-react"
import { cn } from "@/lib/utils"

interface TutorialStep {
  id: string
  title: string
  description: string
  target?: string // ID do elemento a destacar
  position?: 'top' | 'bottom' | 'left' | 'right' | 'center'
  action?: () => void // Ação a executar antes de mostrar o step
}

interface PDVTutorialProps {
  onComplete?: () => void
  onSkip?: () => void
}

/**
 * Tutorial Interativo do PDV
 * 
 * Fase 4: Tutorial inicial interativo com guia passo a passo
 */
export function PDVTutorial({ onComplete, onSkip }: PDVTutorialProps) {
  const [currentStep, setCurrentStep] = useState(0)
  const [isOpen, setIsOpen] = useState(false)
  const [highlightedElement, setHighlightedElement] = useState<string | null>(null)

  const steps: TutorialStep[] = [
    {
      id: 'welcome',
      title: 'Bem-vindo ao PDV!',
      description: 'Este tutorial vai te ajudar a usar o sistema de forma eficiente. Vamos começar!',
      position: 'center',
    },
    {
      id: 'categories',
      title: 'Selecione uma Categoria',
      description: 'As categorias estão no topo. Toque em uma categoria para ver os produtos disponíveis. Você também pode usar os números 1-9 do teclado para seleção rápida.',
      target: 'categories-section',
      position: 'bottom',
    },
    {
      id: 'products',
      title: 'Adicione Produtos',
      description: 'Toque em um produto para adicioná-lo ao pedido. Se o produto tiver variações ou opcionais, uma janela será aberta para você escolher.',
      target: 'products-section',
      position: 'top',
    },
    {
      id: 'cart',
      title: 'Gerencie o Carrinho',
      description: 'No carrinho você pode ajustar quantidades, adicionar observações e remover itens. Use os botões + e - para alterar quantidades.',
      target: 'order-summary',
      position: 'left',
    },
    {
      id: 'client',
      title: 'Selecione o Cliente',
      description: 'Digite o nome do cliente ou selecione de uma lista. O sistema mostrará o histórico de pedidos do cliente automaticamente.',
      target: 'client-section',
      position: 'bottom',
    },
    {
      id: 'table',
      title: 'Escolha a Mesa ou Delivery',
      description: 'Para pedidos no local, selecione uma mesa. Para delivery, ative o modo delivery e preencha o endereço.',
      target: 'table-section',
      position: 'bottom',
    },
    {
      id: 'payment',
      title: 'Selecione o Método de Pagamento',
      description: 'Escolha a forma de pagamento. Se for PIX, um QR Code será gerado automaticamente após finalizar o pedido.',
      target: 'payment-section',
      position: 'bottom',
    },
    {
      id: 'finalize',
      title: 'Finalize o Pedido',
      description: 'Clique no botão verde "Finalizar Pedido" para concluir. Você também pode usar Ctrl+Enter como atalho.',
      target: 'finalize-button',
      position: 'top',
    },
    {
      id: 'shortcuts',
      title: 'Atalhos de Teclado',
      description: 'Use Ctrl+N para novo pedido, Ctrl+F para buscar pedidos, Ctrl+Enter para finalizar e Escape para fechar modais.',
      position: 'center',
    },
    {
      id: 'complete',
      title: 'Tutorial Concluído!',
      description: 'Agora você está pronto para usar o PDV com eficiência. Boas vendas!',
      position: 'center',
    },
  ]

  useEffect(() => {
    // Verificar se o usuário já completou o tutorial
    const hasCompletedTutorial = localStorage.getItem('pdv_tutorial_completed')
    if (!hasCompletedTutorial) {
      // Pequeno delay para garantir que a página carregou completamente
      const timer = setTimeout(() => {
        setIsOpen(true)
      }, 1000)
      return () => clearTimeout(timer)
    }
  }, [])

  const handleNext = () => {
    if (currentStep < steps.length - 1) {
      const nextStep = currentStep + 1
      const step = steps[nextStep]
      
      // Executar ação se houver
      if (step.action) {
        step.action()
      }
      
      // Scroll para o elemento alvo se houver
      if (step.target) {
        setTimeout(() => {
          const element = document.getElementById(step.target!)
          if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' })
            setHighlightedElement(step.target!)
          }
        }, 300)
      } else {
        setHighlightedElement(null)
      }
      
      setCurrentStep(nextStep)
    } else {
      handleComplete()
    }
  }

  const handlePrevious = () => {
    if (currentStep > 0) {
      const prevStep = currentStep - 1
      const step = steps[prevStep]
      
      if (step.target) {
        setTimeout(() => {
          const element = document.getElementById(step.target!)
          if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' })
            setHighlightedElement(step.target!)
          }
        }, 300)
      } else {
        setHighlightedElement(null)
      }
      
      setCurrentStep(prevStep)
    }
  }

  const handleComplete = () => {
    localStorage.setItem('pdv_tutorial_completed', 'true')
    setIsOpen(false)
    setHighlightedElement(null)
    onComplete?.()
  }

  const handleSkip = () => {
    localStorage.setItem('pdv_tutorial_completed', 'true')
    setIsOpen(false)
    setHighlightedElement(null)
    onSkip?.()
  }

  const currentStepData = steps[currentStep]
  const progress = ((currentStep + 1) / steps.length) * 100
  const isFirstStep = currentStep === 0
  const isLastStep = currentStep === steps.length - 1

  // Limpar highlight quando o tutorial for fechado
  useEffect(() => {
    if (!isOpen && highlightedElement) {
      const element = document.getElementById(highlightedElement)
      if (element) {
        element.style.zIndex = ''
        element.style.position = ''
        element.classList.remove('ring-4', 'ring-primary', 'ring-offset-2', 'rounded-lg')
      }
      setHighlightedElement(null)
    }
  }, [isOpen, highlightedElement])

  // Efeito de highlight no elemento alvo
  useEffect(() => {
    if (highlightedElement && isOpen) {
      const element = document.getElementById(highlightedElement)
      if (element) {
        element.style.transition = 'all 0.3s ease'
        element.style.zIndex = '1000'
        element.style.position = 'relative'
        element.classList.add('ring-4', 'ring-primary', 'ring-offset-2', 'rounded-lg')
        
        return () => {
          element.style.zIndex = ''
          element.style.position = ''
          element.classList.remove('ring-4', 'ring-primary', 'ring-offset-2', 'rounded-lg')
        }
      }
    }
  }, [highlightedElement, isOpen])

  return (
    <Dialog open={isOpen} onOpenChange={(open) => {
      if (!open) {
        handleSkip()
      } else {
        setIsOpen(open)
      }
    }}>
        <DialogContent 
          className={cn(
            "sm:max-w-[500px] max-h-[90vh] overflow-y-auto z-[9999]",
            currentStepData.position === 'center' && "mx-auto",
            currentStepData.position === 'left' && "ml-4",
            currentStepData.position === 'right' && "mr-4",
            currentStepData.position === 'top' && "mt-4",
            currentStepData.position === 'bottom' && "mb-4"
          )}
          showCloseButton={false}
        >
          <DialogHeader>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <HelpCircle className="h-5 w-5 text-primary" />
                <DialogTitle>{currentStepData.title}</DialogTitle>
              </div>
              <Badge variant="secondary" className="text-xs">
                {currentStep + 1} / {steps.length}
              </Badge>
            </div>
            <DialogDescription className="pt-2">
              {currentStepData.description}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            {/* Progress bar */}
            <div className="space-y-2">
              <Progress value={progress} className="h-2" />
              <p className="text-xs text-muted-foreground text-center">
                {Math.round(progress)}% concluído
              </p>
            </div>

            {/* Ícone ilustrativo baseado no step */}
            <div className="flex justify-center py-4">
              {currentStepData.id === 'welcome' && <HelpCircle className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'categories' && <Package className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'products' && <Package className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'cart' && <ShoppingCart className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'client' && <Utensils className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'table' && <Utensils className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'payment' && <CreditCard className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'finalize' && <CheckCircle2 className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'shortcuts' && <HelpCircle className="h-16 w-16 text-primary/50" />}
              {currentStepData.id === 'complete' && <CheckCircle2 className="h-16 w-16 text-green-500" />}
            </div>
          </div>

          <DialogFooter className="flex-col sm:flex-row gap-2">
            <Button
              variant="ghost"
              onClick={handleSkip}
              className="w-full sm:w-auto"
            >
              <X className="mr-2 h-4 w-4" />
              Pular tutorial
            </Button>
            <div className="flex gap-2 w-full sm:w-auto">
              {!isFirstStep && (
                <Button
                  variant="outline"
                  onClick={handlePrevious}
                  className="flex-1 sm:flex-initial"
                >
                  <ArrowLeft className="mr-2 h-4 w-4" />
                  Anterior
                </Button>
              )}
              <Button
                onClick={handleNext}
                className={cn(
                  "flex-1 sm:flex-initial",
                  isLastStep && "bg-green-600 hover:bg-green-700"
                )}
              >
                {isLastStep ? (
                  <>
                    <CheckCircle2 className="mr-2 h-4 w-4" />
                    Concluir
                  </>
                ) : (
                  <>
                    Próximo
                    <ArrowRight className="ml-2 h-4 w-4" />
                  </>
                )}
              </Button>
            </div>
          </DialogFooter>
        </DialogContent>
      </Dialog>
  )
}

