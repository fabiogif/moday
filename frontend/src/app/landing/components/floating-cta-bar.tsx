"use client"

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { ArrowRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { TRIAL_CTA_LABEL } from '@/lib/landing-copy'
import { useLandingCTAClick } from '@/hooks/use-landing-cta-click'

const SCROLL_THRESHOLD = 300

export function FloatingCTABar() {
  const [visible, setVisible] = useState(false)
  const trackCTA = useLandingCTAClick('cta_floating_click')

  useEffect(() => {
    const handleScroll = () => {
      setVisible(window.scrollY > SCROLL_THRESHOLD)
    }
    handleScroll()
    window.addEventListener('scroll', handleScroll, { passive: true })
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  if (!visible) return null

  return (
    <div
      className="fixed bottom-0 left-0 right-0 z-50 md:hidden border-t border-zinc-200 bg-white/95 backdrop-blur-xl p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]"
      role="region"
      aria-label="Ação rápida de cadastro"
    >
      <div className="flex items-center gap-3">
        <Button
          size="lg"
          className="flex-1 bg-zinc-900 text-white hover:bg-zinc-700 rounded-md"
          asChild
        >
          <Link href="/auth/register" onClick={() => trackCTA('/auth/register')}>
            {TRIAL_CTA_LABEL}
            <ArrowRight className="ml-2 h-4 w-4" />
          </Link>
        </Button>
        <Link
          href="#pricing"
          className="text-xs font-medium text-zinc-500 hover:text-zinc-900 whitespace-nowrap px-1 transition-colors"
        >
          Ver planos
        </Link>
      </div>
    </div>
  )
}
