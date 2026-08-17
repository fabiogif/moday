'use client'

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Building2 } from 'lucide-react'

interface CnpjAutofillConfirmDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  companyName: string
  onConfirm: () => void
}

export function CnpjAutofillConfirmDialog({
  open,
  onOpenChange,
  companyName,
  onConfirm,
}: CnpjAutofillConfirmDialogProps) {
  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle className="flex items-center gap-2">
            <Building2 className="h-5 w-5 shrink-0" />
            Empresa encontrada: {companyName}
          </AlertDialogTitle>
          <AlertDialogDescription className="text-left">
            Deseja preencher os dados automaticamente?
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Não</AlertDialogCancel>
          <AlertDialogAction
            onClick={(event) => {
              event.preventDefault()
              onConfirm()
            }}
          >
            Sim, preencher
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  )
}
