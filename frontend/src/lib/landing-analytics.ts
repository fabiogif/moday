export type LandingCTAEvent =
  | 'cta_hero_click'
  | 'cta_navbar_click'
  | 'cta_floating_click'
  | 'cta_pricing_click'
  | 'cta_final_click'
  | 'cta_features_click'
  | 'cta_operacao_flow_click'
  | 'cta_financeiro_flow_click'
  | 'cta_app_mobile_flow_click'
  | 'cta_what_is_click'

type LandingAnalyticsPayload = {
  event: LandingCTAEvent
  href?: string
  variant?: string
  timestamp: number
}

function sendToGA(event: LandingCTAEvent, href?: string) {
  const gaId = process.env.NEXT_PUBLIC_GA_ID
  if (!gaId || typeof window === 'undefined') return

  const gtag = (window as Window & { gtag?: (...args: unknown[]) => void }).gtag
  if (typeof gtag === 'function') {
    gtag('event', event, {
      event_category: 'landing',
      event_label: href,
    })
  }
}

export function trackLandingCTAClick(event: LandingCTAEvent, href?: string, variant?: string) {
  if (typeof window === 'undefined') return

  const payload: LandingAnalyticsPayload = {
    event,
    href,
    variant,
    timestamp: Date.now(),
  }

  sendToGA(event, href)

  const body = JSON.stringify(payload)
  const sent = navigator.sendBeacon?.('/api/landing-analytics', body)

  if (!sent) {
    fetch('/api/landing-analytics', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body,
      keepalive: true,
    }).catch(() => {
      // Analytics must not block navigation
    })
  }
}
