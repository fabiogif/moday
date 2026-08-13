"use client"

import { useState } from "react"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { AlertTriangle, CheckCircle2, Loader2 } from "lucide-react"
import { useSubscription } from "@/hooks/use-subscription"
import type { CancellationReason } from "@/types/subscription"

const CANCEL_REASONS: { value: CancellationReason; label: string }[] = [
  { value: "too_expensive", label: "Está caro demais" },
  { value: "missing_features", label: "Faltam recursos que preciso" },
  { value: "competitor", label: "Prefiro outra solução" },
  { value: "not_needed", label: "Não preciso mais no momento" },
  { value: "temporary", label: "Pausa temporária" },
  { value: "other", label: "Outro motivo" },
]

interface CancelDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentPeriodEnd: string | null
  planName?: string | null
  onSuccess: (accessUntil: string | null) => void
}

export function CancelDialog({
  open,
  onOpenChange,
  currentPeriodEnd,
  planName,
  onSuccess,
}: CancelDialogProps) {
  const { cancelSubscription, loading, error, clearError } = useSubscription()
  const [reason, setReason] = useState<CancellationReason | "">("")
  const [reasonDetail, setReasonDetail] = useState("")
  const [confirmedAccessUntil, setConfirmedAccessUntil] = useState<string | null>(null)
  const [done, setDone] = useState(false)

  function resetLocalState() {
    setReason("")
    setReasonDetail("")
    setConfirmedAccessUntil(null)
    setDone(false)
    clearError()
  }

  function handleOpenChange(next: boolean) {
    if (!next) {
      resetLocalState()
    }
    onOpenChange(next)
  }

  async function handleConfirm() {
    clearError()
    const result = await cancelSubscription({
      reason: reason || undefined,
      reason_detail: reasonDetail.trim() || undefined,
    })
    if (!result) return

    const accessUntil = result.access_until ?? currentPeriodEnd
    setConfirmedAccessUntil(accessUntil)
    setDone(true)
    onSuccess(accessUntil)
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="max-w-md">
        {done ? (
          <>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2 text-foreground">
                <CheckCircle2 className="size-5 shrink-0 text-emerald-600" aria-hidden />
                Cancelamento confirmado
              </DialogTitle>
              <DialogDescription>
                Sua assinatura foi cancelada com sucesso.
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-3 py-2 text-sm text-muted-foreground">
              <p>
                {confirmedAccessUntil ? (
                  <>
                    Você continua com acesso completo até{" "}
                    <strong className="text-foreground">{confirmedAccessUntil}</strong>.
                  </>
                ) : (
                  <>Seu acesso foi encerrado. Seus dados permanecem preservados.</>
                )}
              </p>
              <p>
                Se mudar de ideia, você pode reativar a assinatura em Assinatura e Cobrança
                antes do fim do período.
              </p>
            </div>
            <DialogFooter>
              <Button onClick={() => handleOpenChange(false)}>Voltar à cobrança</Button>
            </DialogFooter>
          </>
        ) : (
          <>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2 text-destructive">
                <AlertTriangle className="size-5 shrink-0" aria-hidden />
                Cancelar assinatura
              </DialogTitle>
              <DialogDescription>
                Tem certeza que deseja cancelar
                {planName ? ` o plano ${planName}` : " sua assinatura"}?
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 py-2 text-sm">
              <div className="rounded-lg border bg-muted/40 p-3 text-muted-foreground space-y-2">
                <p>
                  Ao cancelar, <strong className="text-foreground">você mantém o acesso</strong>
                  {currentPeriodEnd ? (
                    <>
                      {" "}
                      até <strong className="text-foreground">{currentPeriodEnd}</strong>
                    </>
                  ) : (
                    " até o fim do ciclo atual"
                  )}
                  . Não haverá novas cobranças.
                </p>
                <p>
                  Pedidos, cardápio, PDV e relatórios do
                  {planName ? ` ${planName}` : " plano"} continuam disponíveis até essa data.
                  Depois disso, os dados ficam preservados por 90 dias.
                </p>
              </div>

              <div className="space-y-3">
                <Label className="text-foreground">
                  Por que está cancelando? <span className="text-muted-foreground font-normal">(opcional)</span>
                </Label>
                <RadioGroup
                  value={reason}
                  onValueChange={(value) => setReason(value as CancellationReason)}
                  className="gap-2"
                >
                  {CANCEL_REASONS.map((item) => (
                    <div key={item.value} className="flex items-center gap-2">
                      <RadioGroupItem value={item.value} id={`cancel-reason-${item.value}`} />
                      <Label
                        htmlFor={`cancel-reason-${item.value}`}
                        className="font-normal cursor-pointer"
                      >
                        {item.label}
                      </Label>
                    </div>
                  ))}
                </RadioGroup>
                {reason === "other" && (
                  <Textarea
                    value={reasonDetail}
                    onChange={(e) => setReasonDetail(e.target.value)}
                    placeholder="Conte um pouco mais (opcional)"
                    maxLength={500}
                    rows={3}
                  />
                )}
              </div>

              {error && <p className="text-destructive">{error}</p>}
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => handleOpenChange(false)} disabled={loading}>
                Manter assinatura
              </Button>
              <Button variant="destructive" onClick={handleConfirm} disabled={loading}>
                {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Confirmar cancelamento
              </Button>
            </DialogFooter>
          </>
        )}
      </DialogContent>
    </Dialog>
  )
}
