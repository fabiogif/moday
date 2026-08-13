"use client"

import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog'
import { RegisterForm } from '@/app/auth/register/components/register-form'
import { useRegisterModal } from '@/contexts/register-modal-context'

export function RegisterModal() {
  const { open, preSelectedPlanId, closeModal } = useRegisterModal()

  const handleSuccess = () => {
    closeModal()
    // O RegisterForm já faz o redirecionamento internamente
  }

  return (
    <Dialog open={open} onOpenChange={closeModal}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto p-0">
        <DialogTitle className="sr-only">Criar sua conta</DialogTitle>
        <div className="p-6">
          <RegisterForm 
            preSelectedPlanId={preSelectedPlanId} 
            onSuccess={handleSuccess}
          />
        </div>
      </DialogContent>
    </Dialog>
  )
}

