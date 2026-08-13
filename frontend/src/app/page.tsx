import { LandingPageContent } from '@/app/landing/landing-page-content'
import { LandingStructuredData } from '@/components/landing-structured-data'
import { buildLandingMetadata } from '@/lib/landing-seo'

export const metadata = buildLandingMetadata('/')

export default function HomePage() {
  return (
    <>
      <LandingStructuredData canonicalPath="/" />
      <LandingPageContent />
    </>
  )
}
