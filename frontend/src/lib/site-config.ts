import { COMPANY_EMAILS } from '@/lib/company-emails'

export const SITE_NAME = 'Alba Tec'

export const SITE_URL = (
  process.env.NEXT_PUBLIC_SITE_URL?.trim() || 'https://albatec.com.br'
).replace(/\/$/, '')

/** Meta description padrão (≈149 caracteres) — keyword + CTA */
export const SITE_DESCRIPTION =
  'Sistema de gestão para restaurantes com PDV, cardápio digital e relatórios. Teste grátis por 7 dias, sem cartão. Comece agora e organize suas vendas.'

export const SITE_LOCALE = 'pt_BR'

/** Title da landing (≈54 caracteres) — keyword no início */
export const LANDING_TITLE =
  'Sistema de Gestão para Restaurantes com PDV | Alba Tec'

/** Contatos públicos (somente dados confirmados no produto) */
export const SITE_CONTACT = {
  email: COMPANY_EMAILS.contact,
  supportEmail: COMPANY_EMAILS.support,
  pixEmail: COMPANY_EMAILS.pix,
  whatsappDisplay: '+55 71 99198-1871',
  whatsappE164: '5571991981871',
  whatsappUrl: 'https://wa.me/5571991981871',
  /** Opcional: preencher via env quando o cliente fornecer dados oficiais */
  cnpj: process.env.NEXT_PUBLIC_COMPANY_CNPJ?.trim() || '',
  address: process.env.NEXT_PUBLIC_COMPANY_ADDRESS?.trim() || '',
} as const
