import { buildLandingJsonLd } from '@/lib/landing-seo'

type LandingStructuredDataProps = {
  canonicalPath?: string
}

export function LandingStructuredData({
  canonicalPath = '/',
}: LandingStructuredDataProps) {
  const schemas = buildLandingJsonLd(canonicalPath)

  return (
    <>
      {schemas.map((schema, index) => (
        <script
          key={`landing-schema-${index}`}
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
        />
      ))}
    </>
  )
}
