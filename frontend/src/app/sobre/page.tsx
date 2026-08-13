import type { Metadata } from 'next'
import Link from 'next/link'
import { SITE_CONTACT, SITE_NAME, SITE_URL } from '@/lib/site-config'
import { AlbaTecLogo } from '@/components/albatec-logo'

export const metadata: Metadata = {
  title: 'Sobre a Alba Tec',
  description:
    'Conheça a Alba Tec: sistema de gestão para restaurantes com PDV, cardápio digital e suporte em português.',
  alternates: { canonical: '/sobre' },
  openGraph: {
    title: `Sobre | ${SITE_NAME}`,
    description: 'Quem somos e como ajudamos restaurantes a vender com mais controle.',
    url: `${SITE_URL}/sobre`,
    type: 'website',
  },
  robots: { index: true, follow: true },
}

export default function SobrePage() {
  const orgLd: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: SITE_NAME,
    url: SITE_URL,
    email: SITE_CONTACT.email,
    telephone: `+${SITE_CONTACT.whatsappE164}`,
    description:
      'Sistema de gestão para restaurantes com PDV, cardápio digital, pedidos e relatórios.',
  }
  if (SITE_CONTACT.cnpj) orgLd.taxID = SITE_CONTACT.cnpj
  if (SITE_CONTACT.address) {
    orgLd.address = {
      '@type': 'PostalAddress',
      streetAddress: SITE_CONTACT.address,
      addressCountry: 'BR',
    }
  }

  return (
    <div className="min-h-screen bg-stone-50 text-zinc-900">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(orgLd) }}
      />

      <header className="border-b border-zinc-200 bg-white">
        <div className="container mx-auto flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
          <AlbaTecLogo href="/" variant="icon" width={80} height={80} className="shrink-0" />
          <nav className="flex items-center gap-4 text-sm font-medium">
            <Link href="/blog" className="text-zinc-600 hover:text-zinc-900">
              Blog
            </Link>
            <Link href="/#contact" className="text-zinc-600 hover:text-zinc-900">
              Contato
            </Link>
            <Link href="/auth/register" className="text-zinc-900 underline-offset-4 hover:underline">
              Teste grátis
            </Link>
          </nav>
        </div>
      </header>

      <main className="container mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <p className="text-[11px] uppercase tracking-[0.18em] text-orange-600 font-medium mb-3">
          Quem somos
        </p>
        <h1 className="text-3xl sm:text-4xl font-bold tracking-tight mb-6">
          Alba Tec — tecnologia para quem vive de restaurante
        </h1>

        <div className="space-y-5 text-zinc-600 leading-relaxed text-lg">
          <p>
            A {SITE_NAME} desenvolve um sistema de gestão para restaurantes focado no dia a dia da
            operação: PDV touch-first, cardápio digital, pedidos em tempo real, financeiro e
            relatórios — em nuvem, com acesso no computador e no celular.
          </p>
          <p>
            Nascemos para substituir planilhas, anotações soltas e ferramentas desconectadas. O
            objetivo é simples: sua equipe vende com menos erro e você decide com dados reais.
          </p>
        </div>

        <section className="mt-12 rounded-xl border border-zinc-200 bg-white p-6">
          <h2 className="text-xl font-semibold mb-4">Contato oficial</h2>
          <ul className="space-y-3 text-sm text-zinc-600">
            <li>
              Comercial:{' '}
              <a className="text-zinc-900 underline" href={`mailto:${SITE_CONTACT.email}`}>
                {SITE_CONTACT.email}
              </a>
            </li>
            <li>
              Atendimento:{' '}
              <a className="text-zinc-900 underline" href={`mailto:${SITE_CONTACT.supportEmail}`}>
                {SITE_CONTACT.supportEmail}
              </a>
            </li>
            <li>
              WhatsApp:{' '}
              <a
                className="text-zinc-900 underline"
                href={SITE_CONTACT.whatsappUrl}
                target="_blank"
                rel="noopener noreferrer"
              >
                {SITE_CONTACT.whatsappDisplay}
              </a>
            </li>
            {SITE_CONTACT.cnpj ? <li>CNPJ: {SITE_CONTACT.cnpj}</li> : null}
            {SITE_CONTACT.address ? <li>Endereço: {SITE_CONTACT.address}</li> : null}
          </ul>
          {!SITE_CONTACT.cnpj && (
            <p className="mt-4 text-xs text-zinc-400">
              CNPJ e endereço comercial podem ser publicados via variáveis de ambiente quando
              confirmados oficialmente.
            </p>
          )}
        </section>

        <section className="mt-10 rounded-xl border border-zinc-200 bg-white p-6">
          <h2 className="text-xl font-semibold mb-3">Parcerias e backlinks</h2>
          <p className="text-sm text-zinc-600 leading-relaxed mb-3">
            Buscamos menções e parcerias com associações de bares e restaurantes, blogs de food
            service, contabilidades especializadas em food e marketplaces de software (GetApp,
            Capterra). Cases, guest posts e materiais gratuitos (checklists) são bem-vindos.
          </p>
          <p className="text-sm text-zinc-600">
            Fale com{' '}
            <a className="text-zinc-900 underline" href={`mailto:${SITE_CONTACT.email}`}>
              {SITE_CONTACT.email}
            </a>{' '}
            com o assunto “Parceria / Conteúdo”.
          </p>
        </section>

        <div className="mt-10 flex flex-wrap gap-3">
          <Link
            href="/auth/register"
            className="inline-flex rounded-md bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-zinc-700"
          >
            Testar o sistema
          </Link>
          <Link
            href="/blog"
            className="inline-flex rounded-md border border-zinc-300 bg-white px-5 py-2.5 text-sm font-medium hover:bg-zinc-50"
          >
            Ler o blog
          </Link>
          <Link
            href="/privacidade"
            className="inline-flex rounded-md border border-zinc-300 bg-white px-5 py-2.5 text-sm font-medium hover:bg-zinc-50"
          >
            Privacidade
          </Link>
        </div>
      </main>
    </div>
  )
}
