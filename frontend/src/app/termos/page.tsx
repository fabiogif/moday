import type { Metadata } from 'next'
import Link from 'next/link'
import { SITE_NAME, SITE_URL } from '@/lib/site-config'
import { COMPANY_EMAILS } from '@/lib/company-emails'
import { AlbaTecLogo } from '@/components/albatec-logo'

export const metadata: Metadata = {
  title: 'Termos de Uso',
  description: `Termos de uso do site e do sistema de gestão para restaurantes ${SITE_NAME}.`,
  alternates: { canonical: '/termos' },
  robots: { index: true, follow: true },
}

export default function TermosPage() {
  return (
    <div className="min-h-screen bg-stone-50 text-zinc-900">
      <header className="border-b border-zinc-200 bg-white">
        <div className="container mx-auto flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
          <AlbaTecLogo href="/" variant="icon" width={80} height={80} className="shrink-0" />
          <Link href="/" className="text-sm font-medium text-zinc-600 hover:text-zinc-900">
            Voltar ao início
          </Link>
        </div>
      </header>

      <main className="container mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 className="text-3xl font-bold tracking-tight mb-2">Termos de Uso</h1>
        <p className="text-sm text-zinc-500 mb-10">Última atualização: julho de 2026</p>

        <div className="space-y-6 text-zinc-600 leading-relaxed">
          <p>
            Ao acessar {SITE_URL} ou utilizar o software {SITE_NAME}, você concorda com estes Termos.
            Se não concordar, não utilize o serviço.
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">1. Serviço</h2>
          <p>
            A {SITE_NAME} oferece um sistema de gestão para restaurantes (PDV, cardápio digital,
            pedidos, financeiro e relatórios) sob planos gratuitos ou pagos, conforme a oferta
            vigente no site.
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">2. Conta e responsabilidades</h2>
          <p>
            Você é responsável pela veracidade dos dados cadastrais, pela confidencialidade das
            credenciais e pelo uso adequado da plataforma em conformidade com a legislação brasileira.
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">3. Planos e pagamento</h2>
          <p>
            Recursos e limites variam por plano. Testes gratuitos e preços seguem a página de planos
            e o fluxo de assinatura. Atraso ou inadimplência pode restringir o acesso a recursos
            pagos.
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">4. Propriedade intelectual</h2>
          <p>
            Marca, software, textos e elementos visuais da {SITE_NAME} são protegidos. É vedada a
            cópia, engenharia reversa ou uso não autorizado fora do escopo contratado.
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">5. Contato</h2>
          <p>
            Contato comercial: {COMPANY_EMAILS.contact}. Atendimento:{' '}
            {COMPANY_EMAILS.support}.
          </p>
        </div>
      </main>
    </div>
  )
}
