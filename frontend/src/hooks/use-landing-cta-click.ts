'use client'

import { useCallback } from 'react'
import { trackLandingCTAClick, type LandingCTAEvent } from '@/lib/landing-analytics'

export function useLandingCTAClick(event: LandingCTAEvent, variant?: string) {
  return useCallback(
    (href?: string) => {
      trackLandingCTAClick(event, href, variant)
    },
    [event, variant]
  )
}
