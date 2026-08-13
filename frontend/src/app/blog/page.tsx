import type { Metadata } from 'next'
import Link from 'next/link'
import { BLOG_POSTS } from '@/lib/blog-posts'
import { SITE_NAME, SITE_URL } from '@/lib/site-config'
import { AlbaTecLogo } from '@/components/albatec-logo'

export const metadata: Metadata = {
  title: 'Blog — Gestão para Restaurantes',
  description:
    'Artigos sobre sistema de gestão para restaurantes, PDV, cardápio digital, estoque e financeiro. Conteúdo prático da Alba Tec.',
  alternates: { canonical: '/blog' },
  openGraph: {
    title: `Blog | ${SITE_NAME}`,
    description: 'Guias práticos de gestão para restaurantes.',
    url: `${SITE_URL}/blog`,
    type: 'website',
  },
  robots: { index: true, follow: true },
}

export default function BlogIndexPage() {
  return (
    <div className="min-h-screen bg-stone-50 text-zinc-900">
      <header className="border-b border-zinc-200 bg-white">
        <div className="container mx-auto flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
          <AlbaTecLogo href="/" variant="icon" width={80} height={80} className="shrink-0" />
          <nav className="flex items-center gap-4 text-sm font-medium">
            <Link href="/sobre" className="text-zinc-600 hover:text-zinc-900">
              Sobre
            </Link>
            <Link href="/#pricing" className="text-zinc-600 hover:text-zinc-900">
              Planos
            </Link>
            <Link href="/auth/register" className="text-zinc-900 underline-offset-4 hover:underline">
              Teste grátis
            </Link>
          </nav>
        </div>
      </header>

      <main className="container mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <p className="text-[11px] uppercase tracking-[0.18em] text-orange-600 font-medium mb-3">
          Blog Alba Tec
        </p>
        <h1 className="text-3xl sm:text-4xl font-bold tracking-tight mb-3">
          Gestão para restaurantes na prática
        </h1>
        <p className="text-lg text-zinc-500 mb-12 max-w-2xl">
          Conteúdo sobre sistema de gestão para restaurantes, PDV, cardápio digital, estoque e
          financeiro — sempre com caminho claro para testar o Alba Tec.
        </p>

        <ul className="space-y-6">
          {BLOG_POSTS.map((post) => (
            <li key={post.slug} className="rounded-xl border border-zinc-200 bg-white p-6">
              <p className="text-xs text-zinc-400 mb-2">
                {new Date(post.date).toLocaleDateString('pt-BR')} · {post.readingMinutes} min
              </p>
              <h2 className="text-xl font-semibold mb-2">
                <Link href={`/blog/${post.slug}`} className="hover:text-orange-700 transition-colors">
                  {post.title}
                </Link>
              </h2>
              <p className="text-zinc-500 text-sm leading-relaxed mb-4">{post.description}</p>
              <Link
                href={`/blog/${post.slug}`}
                className="text-sm font-medium text-zinc-900 underline-offset-4 hover:underline"
              >
                Ler artigo
              </Link>
            </li>
          ))}
        </ul>

        <div className="mt-14 rounded-xl border border-zinc-200 bg-white p-6 text-center">
          <p className="text-zinc-600 mb-4">
            Pronto para organizar pedidos, cardápio e vendas em um só lugar?
          </p>
          <Link
            href="/auth/register"
            className="inline-flex rounded-md bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-zinc-700"
          >
            Começar teste grátis
          </Link>
        </div>
      </main>
    </div>
  )
}
