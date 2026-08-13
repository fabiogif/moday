import type { MetadataRoute } from 'next'
import { SITE_URL } from '@/lib/site-config'

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: '*',
        allow: ['/', '/demo/menu', '/blog', '/sobre', '/privacidade', '/termos', '/subscribe', '/auth/'],
        disallow: [
          '/dashboard/',
          '/admin/',
          '/api/',
          '/store/',
          '/pdv/',
          '/orders/',
          '/products/',
          '/categories/',
          '/users/',
          '/profiles/',
          '/permissions/',
          '/settings/',
          '/integrations/',
          '/reports/',
          '/financial/',
          '/contas-bancarias/',
        ],
      },
    ],
    sitemap: `${SITE_URL}/sitemap.xml`,
    host: SITE_URL,
  }
}
