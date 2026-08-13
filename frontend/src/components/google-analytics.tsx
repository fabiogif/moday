export const GA_MEASUREMENT_ID =
  process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID ?? "G-8JLWCRYC80"

const GA_INLINE_SCRIPT = `
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '${GA_MEASUREMENT_ID}');
`

/** Tags literais no <head> — exigidas pelo verificador do Google Analytics. */
export function GoogleAnalyticsHead() {
  if (!GA_MEASUREMENT_ID) return null

  return (
    <>
      <script
        async
        src={`https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`}
      />
      <script
        dangerouslySetInnerHTML={{
          __html: GA_INLINE_SCRIPT,
        }}
      />
    </>
  )
}
