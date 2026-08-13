import type { Metadata } from 'next'
import Link from 'next/link'
import { notFound } from 'next/navigation'
import { BLOG_POSTS, getAllBlogSlugs, getBlogPost } from '@/lib/blog-posts'
import { SITE_NAME, SITE_URL } from '@/lib/site-config'
import { AlbaTecLogo } from '@/components/albatec-logo'

type Props = { params: Promise<{ slug: string }> }

export function generateStaticParams() {
  return getAllBlogSlugs().map((slug) => ({ slug }))
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params
  const post = getBlogPost(slug)
  if (!post) return { title: 'Artigo não encontrado' }

  return {
    title: post.title,
    description: post.description,
    keywords: post.keywords,
    alternates: { canonical: `/blog/${post.slug}` },
    openGraph: {
      title: `${post.title} | ${SITE_NAME}`,
      description: post.description,
      url: `${SITE_URL}/blog/${post.slug}`,
      type: 'article',
      publishedTime: post.date,
    },
    robots: { index: true, follow: true },
  }
}

export default async function BlogPostPage({ params }: Props) {
  const { slug } = await params
  const post = getBlogPost(slug)
  if (!post) notFound()

  const related = BLOG_POSTS.filter((p) => p.slug !== post.slug).slice(0, 3)

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: post.title,
    description: post.description,
    datePublished: post.date,
    author: { '@type': 'Organization', name: SITE_NAME, url: SITE_URL },
    publisher: {
      '@type': 'Organization',
      name: SITE_NAME,
      logo: { '@type': 'ImageObject', url: `${SITE_URL}/brand/logo-alba-tec-sem-fundo.png` },
    },
    mainEntityOfPage: `${SITE_URL}/blog/${post.slug}`,
  }

  return (
    <div className="min-h-screen bg-stone-50 text-zinc-900">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />

      <header className="border-b border-zinc-200 bg-white">
        <div className="container mx-auto flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
          <AlbaTecLogo href="/" variant="icon" width={80} height={80} className="shrink-0" />
          <nav className="flex items-center gap-4 text-sm font-medium">
            <Link href="/blog" className="text-zinc-600 hover:text-zinc-900">
              Blog
            </Link>
            <Link href="/" className="text-zinc-600 hover:text-zinc-900">
              Sistema Alba Tec
            </Link>
            <Link href="/auth/register" className="text-zinc-900 underline-offset-4 hover:underline">
              Teste grátis
            </Link>
          </nav>
        </div>
      </header>

      <main className="container mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <p className="text-xs text-zinc-400 mb-4">
          <Link href="/blog" className="hover:text-zinc-700">
            Blog
          </Link>
          {' / '}
          {new Date(post.date).toLocaleDateString('pt-BR')} · {post.readingMinutes} min de leitura
        </p>

        <h1 className="text-3xl sm:text-4xl font-bold tracking-tight mb-6 text-balance">
          {post.title}
        </h1>

        <div className="space-y-5 text-zinc-600 leading-relaxed text-base sm:text-lg">
          {post.content.map((paragraph) => (
            <p key={paragraph.slice(0, 32)}>{paragraph}</p>
          ))}
        </div>

        <aside className="mt-12 rounded-xl border border-orange-200 bg-orange-50/60 p-6">
          <h2 className="text-lg font-semibold text-zinc-900 mb-2">
            Conheça o sistema de gestão para restaurantes Alba Tec
          </h2>
          <p className="text-sm text-zinc-600 mb-4">
            PDV, cardápio digital, pedidos e relatórios em um só lugar. Teste grátis por 7 dias, sem
            cartão.
          </p>
          <div className="flex flex-wrap gap-3">
            <Link
              href="/auth/register"
              className="inline-flex rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700"
            >
              Começar agora
            </Link>
            <Link
              href="/#pricing"
              className="inline-flex rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50"
            >
              Ver planos
            </Link>
            <Link
              href="/demo/menu"
              className="inline-flex rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50"
            >
              Ver demonstração
            </Link>
          </div>
        </aside>

        {related.length > 0 && (
          <section className="mt-14">
            <h2 className="text-xl font-semibold mb-4">Continue lendo</h2>
            <ul className="space-y-3">
              {related.map((item) => (
                <li key={item.slug}>
                  <Link
                    href={`/blog/${item.slug}`}
                    className="text-sm font-medium text-zinc-800 underline-offset-4 hover:underline"
                  >
                    {item.title}
                  </Link>
                </li>
              ))}
            </ul>
          </section>
        )}
      </main>
    </div>
  )
}
