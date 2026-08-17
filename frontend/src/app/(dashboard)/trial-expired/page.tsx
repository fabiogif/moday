"use client"

import dynamic from "next/dynamic"
import { useEffect } from "react"
import { useRouter } from "next/navigation"
import { useAuth } from "@/contexts/auth-context"
import { TrialHeader } from "./components/trial-header"
import { DataProtectionCard } from "./components/data-protection-card"
import { UserStatistics } from "./components/user-statistics"
import { BenefitsSection } from "./components/benefits-section"
import { LockedFeatures } from "./components/locked-features"
import { SupportCard } from "./components/support-card"

const RecommendedPlanCard = dynamic(
  () =>
    import("./components/recommended-plan-card").then((m) => m.RecommendedPlanCard),
  {
    loading: () => (
      <div className="h-64 animate-pulse rounded-2xl border bg-muted/40" aria-hidden />
    ),
  }
)

const TrialFaqSection = dynamic(
  () => import("./components/faq-section").then((m) => m.TrialFaqSection),
  {
    loading: () => (
      <div className="h-40 animate-pulse rounded-xl border bg-muted/40" aria-hidden />
    ),
  }
)

export default function TrialExpiredPage() {
  const { trialStatus, isAuthenticated, isLoading } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (isLoading) return

    if (!isAuthenticated) {
      router.replace("/auth/login")
      return
    }

    if (trialStatus && trialStatus.is_active && !trialStatus.is_expired) {
      router.replace("/dashboard")
    }
  }, [isAuthenticated, isLoading, trialStatus, router])

  return (
    <div className="px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 lg:gap-10">
        <TrialHeader subscribeHref="/subscribe" />

        <div className="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
          <div className="space-y-8 order-2 lg:order-1">
            <DataProtectionCard />
            <UserStatistics />
            <BenefitsSection />
            <LockedFeatures />
            <TrialFaqSection />
            <SupportCard />
          </div>

          <div className="order-1 lg:order-2 lg:sticky lg:top-20">
            <RecommendedPlanCard subscribeBaseHref="/subscribe" />
          </div>
        </div>
      </div>
    </div>
  )
}
