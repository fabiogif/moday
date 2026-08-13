"use client"

import { useEffect, useState, useCallback } from "react"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Loader2, Copy, CheckCircle2, XCircle, Smartphone } from "lucide-react"
import { toast } from "sonner"
import { cn } from "@/lib/utils"

interface PixQrCodeDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  orderId: string
  orderTotal: number
  qrCode?: string
  qrCodeText?: string
  onPaymentConfirmed?: () => void
  pollingInterval?: number // em milissegundos
  maxPollingTime?: number // tempo máximo de polling em milissegundos
}

/**
 * Componente para exibir QR Code PIX e fazer polling automático do status do pagamento
 * 
 * Fase 3: QR Code PIX com polling automático
 */
export function PixQrCodeDialog({
  open,
  onOpenChange,
  orderId,
  orderTotal,
  qrCode,
  qrCodeText,
  onPaymentConfirmed,
  pollingInterval = 3000, // 3 segundos
  maxPollingTime = 300000, // 5 minutos
}: PixQrCodeDialogProps) {
  const [paymentStatus, setPaymentStatus] = useState<'pending' | 'paid' | 'expired' | 'error'>('pending')
  const [isPolling, setIsPolling] = useState(false)
  const [pollingStartTime, setPollingStartTime] = useState<number | null>(null)
  const [copied, setCopied] = useState(false)

  // Função para verificar status do pagamento
  const checkPaymentStatus = useCallback(async () => {
    try {
      // TODO: Implementar chamada à API para verificar status do pagamento
      // const response = await apiClient.get(`/orders/${orderId}/payment-status`)
      // const status = response.data.status
      
      // Por enquanto, simular verificação
      // Em produção, isso deve chamar a API real
      // Mock: sempre retorna 'pending' até que a integração real seja implementada
      type PaymentStatus = 'pending' | 'paid' | 'expired' | 'error'
      
      // Helper function para garantir que o TypeScript entenda o tipo union
      // Quando a API real for implementada, substituir por: const status = response.data.status as PaymentStatus
      const getMockStatus = (): PaymentStatus => 'pending'
      const status = getMockStatus()
      
      // Lógica de tratamento de status (será ativada quando a API real retornar outros valores)
      // TypeScript: usando switch para garantir type narrowing adequado
      if (status === 'paid') {
        setPaymentStatus('paid')
        setIsPolling(false)
        onPaymentConfirmed?.()
        toast.success('Pagamento confirmado!')
      } else if (status === 'expired') {
        setPaymentStatus('expired')
        setIsPolling(false)
        toast.error('QR Code expirado. Gere um novo código.')
      } else if (status === 'error') {
        setPaymentStatus('error')
        setIsPolling(false)
        toast.error('Erro ao verificar pagamento. Tente novamente.')
      }
      // Se status for 'pending', continua aguardando (não precisa fazer nada)
    } catch (error) {

      setPaymentStatus('error')
      setIsPolling(false)
    }
  }, [orderId, onPaymentConfirmed])

  // Iniciar polling quando o dialog abrir
  useEffect(() => {
    if (open && paymentStatus === 'pending') {
      setIsPolling(true)
      setPollingStartTime(Date.now())
      
      // Primeira verificação imediata
      checkPaymentStatus()
      
      // Configurar intervalo de polling
      const interval = setInterval(() => {
        const elapsed = Date.now() - (pollingStartTime || Date.now())
        
        // Verificar se excedeu o tempo máximo
        if (elapsed >= maxPollingTime) {
          clearInterval(interval)
          setIsPolling(false)
          setPaymentStatus('expired')
          toast.warning('Tempo de espera expirado. Gere um novo QR Code.')
          return
        }
        
        checkPaymentStatus()
      }, pollingInterval)

      return () => {
        clearInterval(interval)
        setIsPolling(false)
      }
    }
  }, [open, paymentStatus, pollingInterval, maxPollingTime, checkPaymentStatus, pollingStartTime])

  // Resetar estado quando o dialog fechar
  useEffect(() => {
    if (!open) {
      setPaymentStatus('pending')
      setIsPolling(false)
      setPollingStartTime(null)
      setCopied(false)
    }
  }, [open])

  // Copiar código PIX para área de transferência
  const handleCopyPixCode = async () => {
    if (!qrCodeText) {
      toast.error('Código PIX não disponível')
      return
    }

    try {
      await navigator.clipboard.writeText(qrCodeText)
      setCopied(true)
      toast.success('Código PIX copiado!')
      
      setTimeout(() => {
        setCopied(false)
      }, 2000)
    } catch (error) {
      toast.error('Erro ao copiar código PIX')
    }
  }

  // Formatar valor para exibição
  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value)
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Smartphone className="h-5 w-5 text-primary" />
            Pagamento via PIX
          </DialogTitle>
          <DialogDescription>
            Escaneie o QR Code ou copie o código PIX para realizar o pagamento
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Valor do pedido */}
          <div className="rounded-xl border-2 border-primary bg-primary/5 p-4 text-center">
            <p className="text-sm text-muted-foreground mb-1">Valor a pagar</p>
            <p className="text-3xl font-bold text-primary">{formatCurrency(orderTotal)}</p>
          </div>

          {/* QR Code */}
          <div className="flex flex-col items-center gap-4">
            {qrCode ? (
              <div className="relative p-4 bg-white rounded-xl border-2 border-primary/20">
                {/* TODO: Substituir por componente de QR Code real quando biblioteca for instalada */}
                {/* Exemplo: <QRCodeSVG value={qrCode} size={256} /> */}
                <div className="w-64 h-64 bg-muted rounded-lg flex items-center justify-center border-2 border-dashed border-primary/30">
                  <div className="text-center space-y-2">
                    <Smartphone className="h-12 w-12 mx-auto text-primary/50" />
                    <p className="text-xs text-muted-foreground">
                      QR Code será exibido aqui
                    </p>
                    <p className="text-xs text-muted-foreground">
                      (Instale react-qr-code ou qrcode.react)
                    </p>
                  </div>
                </div>
                
                {/* Indicador de polling */}
                {isPolling && paymentStatus === 'pending' && (
                  <div className="absolute top-2 right-2 flex items-center gap-2 bg-primary/90 text-primary-foreground px-3 py-1 rounded-full text-xs">
                    <Loader2 className="h-3 w-3 animate-spin" />
                    <span>Verificando pagamento...</span>
                  </div>
                )}
              </div>
            ) : (
              <div className="w-64 h-64 bg-muted rounded-lg flex items-center justify-center border-2 border-dashed">
                <div className="text-center space-y-2">
                  <Loader2 className="h-12 w-12 mx-auto text-muted-foreground animate-spin" />
                  <p className="text-xs text-muted-foreground">
                    Gerando QR Code...
                  </p>
                </div>
              </div>
            )}

            {/* Status do pagamento */}
            {paymentStatus === 'paid' && (
              <div className="flex items-center gap-2 text-green-600 dark:text-green-400">
                <CheckCircle2 className="h-5 w-5" />
                <span className="font-semibold">Pagamento confirmado!</span>
              </div>
            )}

            {paymentStatus === 'expired' && (
              <div className="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
                <XCircle className="h-5 w-5" />
                <span className="font-semibold">QR Code expirado</span>
              </div>
            )}

            {paymentStatus === 'error' && (
              <div className="flex items-center gap-2 text-red-600 dark:text-red-400">
                <XCircle className="h-5 w-5" />
                <span className="font-semibold">Erro ao processar pagamento</span>
              </div>
            )}
          </div>

          {/* Código PIX (copiar) */}
          {qrCodeText && (
            <div className="space-y-2">
              <p className="text-sm font-medium">Código PIX (Copiar e Colar)</p>
              <div className="flex gap-2">
                <div className="flex-1 rounded-lg border bg-muted p-3 font-mono text-xs break-all">
                  {qrCodeText}
                </div>
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  onClick={handleCopyPixCode}
                  className={cn(
                    "flex-shrink-0",
                    copied && "bg-green-50 border-green-300 dark:bg-green-950/20 dark:border-green-800"
                  )}
                >
                  {copied ? (
                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                  ) : (
                    <Copy className="h-4 w-4" />
                  )}
                </Button>
              </div>
            </div>
          )}

          {/* Instruções */}
          <div className="rounded-lg border bg-muted/50 p-4 space-y-2">
            <p className="text-sm font-semibold">Como pagar:</p>
            <ol className="text-sm text-muted-foreground space-y-1 list-decimal list-inside">
              <li>Abra o app do seu banco</li>
              <li>Escaneie o QR Code ou cole o código PIX</li>
              <li>Confirme o pagamento</li>
              <li>O pagamento será confirmado automaticamente</li>
            </ol>
          </div>

          {/* Botões de ação */}
          <div className="flex flex-col gap-2">
            {paymentStatus === 'paid' ? (
              <Button
                onClick={() => onOpenChange(false)}
                className="w-full bg-green-600 hover:bg-green-700"
              >
                <CheckCircle2 className="mr-2 h-4 w-4" />
                Fechar
              </Button>
            ) : (
              <>
                <Button
                  variant="outline"
                  onClick={() => onOpenChange(false)}
                  className="w-full"
                >
                  Fechar (pagamento continuará sendo verificado)
                </Button>
                {paymentStatus === 'expired' && (
                  <Button
                    onClick={() => {
                      setPaymentStatus('pending')
                      setIsPolling(true)
                      setPollingStartTime(Date.now())
                      checkPaymentStatus()
                    }}
                    className="w-full"
                  >
                    Gerar novo QR Code
                  </Button>
                )}
              </>
            )}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  )
}

