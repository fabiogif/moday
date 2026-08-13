import type { Metadata } from 'next'
import { SITE_NAME, SITE_URL } from '@/lib/site-config'

export const metadata: Metadata = {
  title: 'Demonstração do Cardápio Digital',
  description:
    'Experimente o cardápio digital do sistema de gestão para restaurantes Alba Tec: variações, opcionais e pedidos em tempo real.',
  alternates: { canonical: '/demo/menu' },
  openGraph: {
    title: `Demonstração do Cardápio Digital | ${SITE_NAME}`,
    description:
      'Veja na prática como o cardápio digital funciona no Alba Tec — sistema de gestão para restaurantes.',
    url: `${SITE_URL}/demo/menu`,
    type: 'website',
  },
  robots: { index: true, follow: true },
}

export default function DemoMenuLayout({ children }: { children: React.ReactNode }) {
  return children
}
