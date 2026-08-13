"use client"

import { useEffect } from "react"
import { usePathname, useRouter } from "next/navigation"
import { useAuth } from "@/contexts/auth-context"

/** Rotas liberadas com trial expirado (assinatura, suporte e a própria tela). */
const EXEMPT_PATHS = [
  "/trial-expired",
  "/subscribe",
  "/subscription",
  "/contact",
  "/plans",
]

function isExemptPath(pathname: string): boolean {
  return EXEMPT_PATHS.some(
    (path) => pathname === path || pathname.startsWith(`${path}/`)
  )
}

export function useTrialGuard() {
  const { trialStatus, isAuthenticated, isLoading } = useAuth()
  const router = useRouter()
  const pathname = usePathname()

  useEffect(() => {
    if (isLoading || !isAuthenticated || !trialStatus) return
    if (isExemptPath(pathname)) return

    if (trialStatus.is_expired || (trialStatus.needs_payment && !trialStatus.is_active)) {
      router.replace("/trial-expired")
    }
  }, [isLoading, isAuthenticated, trialStatus, pathname, router])
}
