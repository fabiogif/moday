"use client"

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"

interface SuccessAlertProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  title: string
  message: string
}

export function SuccessAlert({ open, onOpenChange, title, message }: SuccessAlertProps) {
  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle className="text-green-600">{title}</AlertDialogTitle>
          <AlertDialogDescription className="text-base">
            {message}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogAction 
            onClick={() => onOpenChange(false)}
            className="bg-green-600 hover:bg-green-700"
          >
            OK
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  )
}
