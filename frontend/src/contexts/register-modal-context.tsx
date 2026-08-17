"use client"

import React, { createContext, useContext, useState } from 'react'

interface RegisterModalContextType {
  open: boolean
  preSelectedPlanId?: string
  openModal: (planId?: string) => void
  closeModal: () => void
}

const RegisterModalContext = createContext<RegisterModalContextType | undefined>(undefined)

export function RegisterModalProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useState(false)
  const [preSelectedPlanId, setPreSelectedPlanId] = useState<string | undefined>(undefined)

  const openModal = (planId?: string) => {
    setPreSelectedPlanId(planId)
    setOpen(true)
  }

  const closeModal = () => {
    setOpen(false)
    setPreSelectedPlanId(undefined)
  }

  return (
    <RegisterModalContext.Provider value={{ open, preSelectedPlanId, openModal, closeModal }}>
      {children}
    </RegisterModalContext.Provider>
  )
}

export function useRegisterModal() {
  const context = useContext(RegisterModalContext)
  if (context === undefined) {
    throw new Error('useRegisterModal must be used within a RegisterModalProvider')
  }
  return context
}

