export type PlanModuleOptionKey =
  | 'has_marketing'
  | 'has_order_completion_email'
  | 'has_reports'

export interface PlanModuleOption {
  key: PlanModuleOptionKey
  label: string
  description: string
}

export interface PlanModuleGroup {
  id: string
  label: string
  description?: string
  options: PlanModuleOption[]
}

export const PLAN_MODULE_GROUPS: PlanModuleGroup[] = [
  {
    id: 'marketing',
    label: 'Marketing',
    description: 'Funcionalidades de comunicação e promoção',
    options: [
      {
        key: 'has_marketing',
        label: 'Cupons e campanhas',
        description: 'Permite acesso ao módulo de cupons e promoções',
      },
      {
        key: 'has_order_completion_email',
        label: 'E-mail de confirmação de pedido',
        description: 'Envia e-mail automático ao cliente quando o pedido é concluído na loja',
      },
    ],
  },
  {
    id: 'reports',
    label: 'Relatórios',
    description: 'Análises e exportações do negócio',
    options: [
      {
        key: 'has_reports',
        label: 'Acesso a relatórios',
        description: 'Permite acesso ao módulo de relatórios e dashboards',
      },
    ],
  },
]

export const PLAN_MODULE_OPTION_LABELS: Record<PlanModuleOptionKey, string> = {
  has_marketing: 'Cupons',
  has_order_completion_email: 'E-mail pedido',
  has_reports: 'Relatórios',
}
