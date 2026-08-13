"use client"

import { useEffect } from "react"
import { usePathname, useRouter } from "next/navigation"
import { useAuth } from "@/contexts/auth-context"
import { getLoginRedirectUrl } from "@/lib/auth-routes"
import { useTrialGuard } from "@/hooks/use-trial-guard"

export function AuthGuard({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading, user } = useAuth()
  const router = useRouter()
  const pathname = usePathname()

  useTrialGuard()

  const needsEmailVerification = user?.email_verified === false

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      const search = typeof window !== "undefined" ? window.location.search : ""
      router.replace(getLoginRedirectUrl(pathname, search))
      return
    }

    if (!isLoading && isAuthenticated && needsEmailVerification) {
      router.replace("/auth/verify-email")
    }
  }, [isLoading, isAuthenticated, needsEmailVerification, pathname, router])

  if (isLoading) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-b-2 border-primary" />
      </div>
    )
  }

  if (!isAuthenticated) {
    return null
  }

  if (needsEmailVerification) {
    return null
  }

  return <>{children}</>
}
