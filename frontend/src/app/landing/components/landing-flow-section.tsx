"use client"

import Link from 'next/link'
import {
  ArrowRight,
  BarChart3,
  Bell,
  CheckCircle2,
  FileText,
  Mail,
  ShoppingCart,
  Smartphone,
  UserPlus,
  Wallet,
  type LucideIcon,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { useLandingCTAClick } from '@/hooks/use-landing-cta-click'
import type { LandingCTAEvent } from '@/lib/landing-analytics'

const FLOW_FEATURE_ICONS = {
  'user-plus': UserPlus,
  'shopping-cart': ShoppingCart,
  mail: Mail,
  wallet: Wallet,
  'file-text': FileText,
  'bar-chart-3': BarChart3,
  smartphone: Smartphone,
  bell: Bell,
  'check-circle': CheckCircle2,
} as const satisfies Record<string, LucideIcon>

export type FlowFeatureIcon = keyof typeof FLOW_FEATURE_ICONS

export type FlowFeature = {
  icon: FlowFeatureIcon
  title: string
  description: string
}

type LandingFlowSectionProps = {
  eyebrow: string
  badge?: string
  title: string
  description: string
  ctaLabel: string
  ctaHref: string
  ctaEvent: LandingCTAEvent
  animation: React.ReactNode
  features: FlowFeature[]
  reverse?: boolean
  id?: string
}

function FlowFeatureCard({ icon, title, description }: FlowFeature) {
  const Icon = FLOW_FEATURE_ICONS[icon]

  return (
    <div className="flex gap-4">
      <div className="shrink-0 mt-0.5">
        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100">
          <Icon className="h-4 w-4 text-zinc-600" aria-hidden="true" />
        </div>
      </div>
      <div className="min-w-0">
        <h3 className="text-sm font-semibold text-zinc-900 mb-1.5">{title}</h3>
        <p className="text-sm leading-relaxed text-zinc-500">{description}</p>
      </div>
    </div>
  )
}

export function LandingFlowSection({
  eyebrow,
  badge,
  title,
  description,
  ctaLabel,
  ctaHref,
  ctaEvent,
  animation,
  features,
  reverse = false,
  id,
}: LandingFlowSectionProps) {
  const trackCTA = useLandingCTAClick(ctaEvent)

  return (
    <section
      id={id}
      className="py-20 sm:py-28 bg-white border-t border-zinc-200"
    >
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div
          className={cn(
            'grid items-center gap-12 lg:grid-cols-2 lg:gap-16 xl:gap-20',
            reverse && 'lg:[&>*:first-child]:order-2',
          )}
        >
          <div className="flow-reveal-up">{animation}</div>

          <div className="flow-reveal-up [animation-delay:150ms]">
            <div className="mb-4 flex flex-wrap items-center gap-2">
              <p className="text-[11px] uppercase tracking-[0.22em] text-orange-600 font-medium">
                {eyebrow}
              </p>
              {badge ? (
                <span className="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-orange-700">
                  {badge}
                </span>
              ) : null}
            </div>

            <h2 className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-900 text-balance leading-[1.1] mb-5">
              {title}
            </h2>

            <p className="max-w-lg text-base sm:text-lg leading-relaxed text-zinc-500 text-balance mb-8">
              {description}
            </p>

            <Button
              size="lg"
              className="bg-zinc-900 text-white hover:bg-zinc-700 rounded-md h-11 px-6 text-sm font-medium transition-colors"
              asChild
            >
              <Link href={ctaHref} onClick={() => trackCTA(ctaHref)}>
                {ctaLabel}
                <ArrowRight className="ml-2 h-4 w-4" />
              </Link>
            </Button>
          </div>
        </div>

        <div className="mt-16 sm:mt-20 grid gap-8 sm:grid-cols-2 lg:grid-cols-3 lg:gap-10">
          {features.map((feature) => (
            <FlowFeatureCard key={feature.title} {...feature} />
          ))}
        </div>
      </div>
    </section>
  )
}
