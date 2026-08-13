"use client"

import React, { createContext, useContext, useState, ReactNode } from "react"

interface POSHeaderContextType {
  onTodayOrdersClick: (() => void) | null
  todayOrdersCount: number
  setTodayOrdersClick: (callback: (() => void) | null) => void
  setTodayOrdersCount: (count: number) => void
}

const POSHeaderContext = createContext<POSHeaderContextType | undefined>(undefined)

export function POSHeaderProvider({ children }: { children: ReactNode }) {
  const [onTodayOrdersClick, setOnTodayOrdersClick] = useState<(() => void) | null>(null)
  const [todayOrdersCount, setTodayOrdersCount] = useState(0)

  return (
    <POSHeaderContext.Provider
      value={{
        onTodayOrdersClick,
        todayOrdersCount,
        setTodayOrdersClick: setOnTodayOrdersClick,
        setTodayOrdersCount,
      }}
    >
      {children}
    </POSHeaderContext.Provider>
  )
}

export function usePOSHeader() {
  const context = useContext(POSHeaderContext)
  if (context === undefined) {
    throw new Error("usePOSHeader must be used within a POSHeaderProvider")
  }
  return context
}

