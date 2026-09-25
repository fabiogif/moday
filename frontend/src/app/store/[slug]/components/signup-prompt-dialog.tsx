'use client'

import { useState } from 'react'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { ClientRegisterForm } from '@/components/client-register-form'
import type { ClientUser } from '@/contexts/client-auth-context'
import { ChevronLeft, UserPlus } from 'lucide-react'

interface SignupPromptDialogProps {
  open: boolean
  slug: string
  onClose: () => void
  onContinueAsGuest: () => void
  onRegistered: (client: ClientUser) => void
}

/**
 * Pergunta opcional antes do checkout. Fica na própria página do cardápio
 * (não navega) para o carrinho em memória não se perder durante o cadastro.
 */
export function SignupPromptDialog({ open, slug, onClose, onContinueAsGuest, onRegistered }: SignupPromptDialogProps) {
  const [showForm, setShowForm] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  const handleOpenChange = (nextOpen: boolean) => {
    // Não fecha no meio do envio: o cadastro concluiria e avançaria o pedido com a modal já fechada
    if (nextOpen || submitting) return
    setShowForm(false)
    onClose()
  }

  const handleContinueAsGuest = () => {
    setShowForm(false)
    onContinueAsGuest()
  }

  const handleRegistered = (client: ClientUser) => {
    setShowForm(false)
    onRegistered(client)
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="flex max-h-[90dvh] flex-col gap-4 overflow-y-auto sm:max-w-md">
        {showForm ? (
          <>
            <DialogHeader className="text-left">
              <DialogTitle>Criar conta</DialogTitle>
              <DialogDescription>
                Seu carrinho continua salvo. Depois do cadastro, seguimos com o seu pedido.
              </DialogDescription>
            </DialogHeader>
            <ClientRegisterForm
              slug={slug}
              submitLabel="Cadastrar e continuar"
              onSuccess={handleRegistered}
              onSubmittingChange={setSubmitting}
            >
              <div className="flex flex-col gap-2 sm:flex-row">
                <Button
                  type="button"
                  variant="outline"
                  className="h-11 flex-1"
                  onClick={() => setShowForm(false)}
                  disabled={submitting}
                >
                  <ChevronLeft className="mr-1 h-4 w-4" /> Voltar
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  className="h-11 flex-1"
                  onClick={handleContinueAsGuest}
                  disabled={submitting}
                >
                  Continuar sem cadastro
                </Button>
              </div>
            </ClientRegisterForm>
          </>
        ) : (
          <>
            <DialogHeader className="text-left">
              <DialogTitle>Deseja se cadastrar?</DialogTitle>
              <DialogDescription>
                O cadastro é opcional. Cadastre-se para continuar como cliente identificado ou siga com seu pedido sem cadastro — seu carrinho não será perdido.
              </DialogDescription>
            </DialogHeader>
            <div className="flex flex-col gap-2">
              <Button type="button" className="h-12 w-full" onClick={() => setShowForm(true)}>
                <UserPlus className="mr-2 h-4 w-4" /> Sim, quero me cadastrar
              </Button>
              <Button type="button" variant="outline" className="h-12 w-full" onClick={handleContinueAsGuest}>
                Continuar sem cadastro
              </Button>
            </div>
            <p className="text-center text-xs text-muted-foreground">
              Já comprou aqui antes? Continue sem cadastro — usamos seus dados para identificar você.
            </p>
          </>
        )}
      </DialogContent>
    </Dialog>
  )
}
