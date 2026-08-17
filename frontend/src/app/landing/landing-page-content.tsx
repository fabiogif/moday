import dynamic from 'next/dynamic'
import { Suspense } from 'react'
import { LandingNavbar } from './components/navbar'
import { HeroSection } from './components/hero-section'
import { WhatIsSection } from './components/what-is-section'
import {
  AppMobileFlowSection,
  FinanceFlowSection,
  OperationFlowSection,
} from './components/landing-flow-sections'
import { FeaturesSection } from './components/features-section'
import { DemoMenuCTA } from './components/demo-menu-cta'
import { PricingSection } from './components/pricing-section'
import { CTASection } from './components/cta-section'
import { TrustBadges } from './components/trust-badges'
import { FloatingCTABar } from './components/floating-cta-bar'
import { LandingFooter } from './components/footer'
import { LandingLightThemeLock } from './components/landing-light-theme-lock'

const TestimonialsSection = dynamic(
  () => import('./components/testimonials-section').then((m) => ({ default: m.TestimonialsSection })),
  { loading: () => <SectionPlaceholder /> }
)

const FaqSection = dynamic(
  () => import('./components/faq-section').then((m) => ({ default: m.FaqSection })),
  { loading: () => <SectionPlaceholder /> }
)

const ContactSection = dynamic(
  () => import('./components/contact-section').then((m) => ({ default: m.ContactSection })),
  { loading: () => <SectionPlaceholder /> }
)

function SectionPlaceholder() {
  return <div className="py-24" aria-hidden="true" />
}

function HeroFallback() {
  return <div className="min-h-[60vh] pt-32" aria-hidden="true" />
}

export function LandingPageContent() {
  return (
    <LandingLightThemeLock>
      <LandingNavbar />

      <main>
        <Suspense fallback={<HeroFallback />}>
          <HeroSection />
        </Suspense>
        <TrustBadges />
        <WhatIsSection />
        <OperationFlowSection />
        <FinanceFlowSection />
        <AppMobileFlowSection />
        <FeaturesSection />
        <DemoMenuCTA />
        <TestimonialsSection />
        <PricingSection />
        <FaqSection />
        <CTASection />
        <ContactSection />
      </main>

      <LandingFooter />
      <FloatingCTABar />
    </LandingLightThemeLock>
  )
}
