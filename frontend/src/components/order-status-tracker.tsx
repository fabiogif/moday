'use client'

import { Clock, Package, CheckCircle2 } from 'lucide-react'
import { cn } from '@/lib/utils'
import {
  isCancelledOrderStatus,
  resolveOrderStatusStepIndex,
  type OrderTrackerStatus,
} from '@/lib/order-status'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'

type OrderStatus = OrderTrackerStatus

interface Step {
  id: number
  status: OrderStatus
  label: string
  icon: React.ReactNode
  description: string
}

const DELIVERY_STEPS: Step[] = [
  {
    id: 1,
    status: 'Pendente',
    label: 'Pendente',
    icon: <Clock className="h-5 w-5" />,
    description: 'Seu pedido foi recebido e aguarda aceite'
  },
  {
    id: 2,
    status: 'Aceito',
    label: 'Aceito',
    icon: <Package className="h-5 w-5" />,
    description: 'O restaurante aceitou o seu pedido'
  },
  {
    id: 3,
    status: 'Preparo',
    label: 'Preparo',
    icon: <Clock className="h-5 w-5" />,
    description: 'Seu pedido está sendo preparado'
  },
  {
    id: 4,
    status: 'Concluído',
    label: 'Concluído',
    icon: <CheckCircle2 className="h-5 w-5" />,
    description: 'Pedido concluído com sucesso!'
  },
]

interface OrderStatusTrackerProps {
  currentStatus: OrderStatus
  createdAt?: string
  updatedAt?: string
  estimatedDelivery?: string
  className?: string
  vertical?: boolean
  showInfoCards?: boolean
}

export function OrderStatusTracker({ 
  currentStatus, 
  createdAt,
  updatedAt,
  estimatedDelivery,
  className,
  vertical = false,
  showInfoCards = true,
}: OrderStatusTrackerProps) {
  const currentStepIndex = resolveOrderStatusStepIndex(currentStatus)

  // Se pedido cancelado, não mostrar tracker
  if (currentStatus === 'Cancelado' || isCancelledOrderStatus(currentStatus)) {
    return (
      <div className={cn("p-6 text-center space-y-2", className)}>
        <div className="h-16 w-16 rounded-full bg-rose-100 dark:bg-rose-950 flex items-center justify-center mx-auto">
          <span className="text-3xl">❌</span>
        </div>
        <h3 className="text-lg font-semibold text-rose-600 dark:text-rose-400">
          Pedido Cancelado
        </h3>
        <p className="text-sm text-muted-foreground">
          Este pedido foi cancelado
        </p>
      </div>
    )
  }

  const getStepState = (stepIndex: number): 'completed' | 'current' | 'upcoming' => {
    if (stepIndex < currentStepIndex) return 'completed'
    if (stepIndex === currentStepIndex) return 'current'
    return 'upcoming'
  }

  return (
    <TooltipProvider>
      <div className={cn("w-full space-y-6", className)}>
        {/* Versão simplificada para impressão */}
        <div className="hidden print:block space-y-3 rounded-lg border p-4">
          <div className="flex items-center justify-between gap-4 text-sm">
            <span className="font-medium text-muted-foreground">Status atual</span>
            <span className="font-semibold">{currentStatus}</span>
          </div>
          <div className="grid grid-cols-4 gap-1">
            {DELIVERY_STEPS.map((step, index) => {
              const state = getStepState(index)
              return (
                <div key={step.id} className="text-center">
                  <div
                    className={cn(
                      'mx-auto mb-1 h-2 w-full rounded-full',
                      state === 'completed' && 'bg-emerald-500',
                      state === 'current' && 'bg-blue-500',
                      state === 'upcoming' && 'bg-muted'
                    )}
                  />
                  <p className="text-[9px] leading-tight text-muted-foreground">{step.label}</p>
                </div>
              )
            })}
          </div>
          {createdAt && (
            <p className="text-xs text-muted-foreground">
              Pedido criado em{' '}
              {new Date(createdAt).toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
              })}
            </p>
          )}
          {estimatedDelivery && currentStepIndex < 3 && (
            <p className="text-xs">
              <span className="font-medium">Previsão de entrega:</span> {estimatedDelivery}
            </p>
          )}
        </div>

        {/* Timeline */}
        <div className={cn(
          "flex items-center print:hidden",
          vertical 
            ? "flex-col space-y-8" 
            : "flex-col md:flex-row md:justify-between space-y-8 md:space-y-0 md:space-x-2"
        )}>
          {DELIVERY_STEPS.map((step, index) => {
            const state = getStepState(index)
            const isLast = index === DELIVERY_STEPS.length - 1

            return (
              <div
                key={step.id}
                className={cn(
                  "flex items-center w-full md:flex-1",
                  vertical && "w-full"
                )}
                style={{ 
                  animation: `fadeIn 0.5s ease-out ${index * 0.1}s both`
                }}
              >
                {/* Step */}
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="flex flex-col items-center gap-2 relative">
                      {/* Circle Icon */}
                      <div
                        className={cn(
                          "relative h-12 w-12 rounded-full border-2 flex items-center justify-center transition-all duration-300",
                          "hover:scale-110",
                          state === 'completed' && "bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-500/30 animate-in zoom-in-50",
                          state === 'current' && "bg-blue-500 border-blue-500 text-white shadow-lg shadow-blue-500/30 scale-110 animate-pulse",
                          state === 'upcoming' && "bg-muted border-muted-foreground/30 text-muted-foreground"
                        )}
                      >
                        {state === 'completed' ? (
                          <CheckCircle2 className="h-6 w-6" />
                        ) : (
                          step.icon
                        )}
                        
                        {/* Pulse ring for current step */}
                        {state === 'current' && (
                          <span className="absolute inset-0 rounded-full bg-blue-500 animate-ping opacity-30" />
                        )}
                      </div>

                      {/* Label */}
                      <div className={cn(
                        "text-center min-w-[80px] md:min-w-[100px]",
                        vertical && "md:absolute md:left-20 md:top-1/2 md:-translate-y-1/2 md:text-left md:min-w-[140px]"
                      )}>
                        <p className={cn(
                          "text-xs md:text-sm font-semibold transition-colors",
                          state === 'completed' && "text-emerald-600 dark:text-emerald-400",
                          state === 'current' && "text-blue-600 dark:text-blue-400",
                          state === 'upcoming' && "text-muted-foreground"
                        )}>
                          {step.label}
                        </p>
                        {state === 'current' && (
                          <p 
                            className="text-[10px] text-muted-foreground mt-0.5 animate-in fade-in-50 duration-300"
                            style={{ animationDelay: '300ms' }}
                          >
                            Atual
                          </p>
                        )}
                      </div>
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p className="text-xs font-medium">{step.description}</p>
                  </TooltipContent>
                </Tooltip>

                {/* Connecting Line */}
                {!isLast && (
                  <div className={cn(
                    "relative flex-1",
                    vertical 
                      ? "h-16 w-0.5 ml-6 md:ml-6" 
                      : "h-12 md:h-0.5 w-0.5 md:w-auto my-2 md:my-0 mx-auto md:mx-2"
                  )}>
                    <div className={cn(
                      "absolute inset-0 bg-muted-foreground/20",
                      vertical ? "w-full" : "h-full md:h-full w-full md:w-full"
                    )} />
                    <div
                      className={cn(
                        "absolute inset-0 transition-all duration-500 origin-top md:origin-left",
                        vertical ? "w-full" : "h-full md:h-full w-full md:w-full",
                        state === 'completed' ? "bg-emerald-500 scale-100" : "bg-transparent scale-0"
                      )}
                      style={{
                        transitionDelay: `${index * 0.1 + 0.2}s`
                      }}
                    />
                  </div>
                )}
              </div>
            )
          })}
        </div>

        {/* Info Cards */}
        {showInfoCards && (
        <div className="grid gap-3 md:grid-cols-2 print:hidden">
          {createdAt && (
            <div className="p-3 rounded-lg bg-muted/50 border">
              <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1">
                Pedido Criado
              </p>
              <p className="text-sm font-medium">
                {new Date(createdAt).toLocaleString('pt-BR', {
                  day: '2-digit',
                  month: '2-digit',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit'
                })}
              </p>
            </div>
          )}

          {estimatedDelivery && currentStatus !== 'Entregue' && currentStatus !== 'Concluído' && (
            <div className="p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
              <p className="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-1">
                Previsão de Entrega
              </p>
              <p className="text-sm font-medium text-blue-700 dark:text-blue-300">
                {estimatedDelivery}
              </p>
            </div>
          )}

          {(currentStatus === 'Entregue' || currentStatus === 'Concluído') && updatedAt && (
            <div className="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800">
              <p className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-1">
                {currentStatus === 'Concluído' ? 'Concluído Em' : 'Entregue Em'}
              </p>
              <p className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                {new Date(updatedAt).toLocaleString('pt-BR', {
                  day: '2-digit',
                  month: '2-digit',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit'
                })}
              </p>
            </div>
          )}
        </div>
        )}
      </div>
    </TooltipProvider>
  )
}

