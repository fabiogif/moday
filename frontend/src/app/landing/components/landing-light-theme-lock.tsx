"use client"

import { useEffect } from 'react'
import { useTheme } from '@/hooks/use-theme'

/**
 * Mantém a landing page sempre no tema claro, independente da preferência global.
 */
export function LandingLightThemeLock({ children }: { children: React.ReactNode }) {
  const { setTheme } = useTheme()

  useEffect(() => {
    setTheme('light')
  }, [setTheme])

  return (
    <div className="light min-h-screen bg-stone-50 text-zinc-900" data-landing-theme="light">
      {children}
    </div>
  )
}
