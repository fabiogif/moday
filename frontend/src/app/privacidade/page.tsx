import type { Metadata } from 'next'
import Link from 'next/link'
import { SITE_NAME, SITE_URL } from '@/lib/site-config'
import { COMPANY_EMAILS } from '@/lib/company-emails'
import { AlbaTecLogo } from '@/components/albatec-logo'

export const metadata: Metadata = {
  title: 'Política de Privacidade',
  description: `Como a ${SITE_NAME} trata dados pessoais de clientes e visitantes do site.`,
  alternates: { canonical: '/privacidade' },
  robots: { index: true, follow: true },
}

export default function PrivacidadePage() {
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
        <h1 className="text-3xl font-bold tracking-tight mb-2">Política de Privacidade</h1>
        <p className="text-sm text-zinc-500 mb-10">Última atualização: julho de 2026</p>

        <div className="prose prose-zinc max-w-none space-y-6 text-zinc-600 leading-relaxed">
          <p>
            Esta Política descreve como a {SITE_NAME} ({SITE_URL}) coleta, usa e protege informações
            quando você visita o site, solicita contato ou utiliza o sistema de gestão para
            restaurantes.
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">1. Dados que coletamos</h2>
          <p>
            Podemos coletar dados de identificação e contato (nome, e-mail, telefone, nome do
            estabelecimento), dados de uso da plataforma e informações técnicas necessárias à
            operação do serviço (logs, dispositivo e endereço IP).
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">2. Finalidade</h2>
          <p>
            Usamos os dados para prestar o serviço, suporte, cobrança de planos, comunicação
            comercial quando autorizado, segurança e cumprimento de obrigações legais.
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">3. Compartilhamento</h2>
          <p>
            Não vendemos dados pessoais. Podemos compartilhar com prestadores essenciais (hospedagem,
            e-mail, pagamento) sob contrato e apenas na medida necessária à operação.
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">4. Seus direitos</h2>
          <p>
            Nos termos da LGPD, você pode solicitar acesso, correção, portabilidade ou exclusão dos
            seus dados, quando aplicável, pelo e-mail{' '}
            <a className="text-zinc-900 underline" href={`mailto:${COMPANY_EMAILS.contact}`}>
              {COMPANY_EMAILS.contact}
            </a>
            .
          </p>

          <h2 className="text-xl font-semibold text-zinc-900 pt-4">5. Contato</h2>
          <p>
            Dúvidas sobre privacidade: {COMPANY_EMAILS.contact}. Suporte ao produto:{' '}
            {COMPANY_EMAILS.support}.
          </p>
        </div>
      </main>
    </div>
  )
}
