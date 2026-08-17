import { PLAN_MODULE_GROUPS, type PlanModuleOptionKey } from '@/lib/plan-modules'

export interface PublicPlanFeatures {
  max_users?: number | null
  max_products?: number | null
  max_orders_per_month?: number | null
  has_marketing?: boolean
  has_order_completion_email?: boolean
  has_reports?: boolean
  details?: Array<{ id?: number; name: string; plan_id?: number }>
}

export interface PlanLimitItem {
  label: string
  value: string
}

export interface PlanModuleFeatureItem {
  key: PlanModuleOptionKey
  label: string
  description: string
  included: boolean
}

export interface PlanModuleFeatureGroup {
  id: string
  label: string
  options: PlanModuleFeatureItem[]
}

export function formatPlanLimitValue(limit: number | null | undefined): string {
  if (limit === null || limit === undefined || limit >= 999999) {
    return 'Ilimitado'
  }
  return limit.toLocaleString('pt-BR')
}

export function buildPlanLimitItems(plan: PublicPlanFeatures): PlanLimitItem[] {
  const users = plan.max_users
  const products = plan.max_products
  const orders = plan.max_orders_per_month

  return [
    {
      label: 'Usuários',
      value:
        users === null || users === undefined || users >= 999999
          ? 'Ilimitados'
          : users === 1
            ? '1 usuário'
            : `Até ${users.toLocaleString('pt-BR')} usuários`,
    },
    {
      label: 'Produtos',
      value:
        products === null || products === undefined || products >= 999999
          ? 'Ilimitados'
          : `Até ${products.toLocaleString('pt-BR')} produtos`,
    },
    {
      label: 'Pedidos/mês',
      value:
        orders === null || orders === undefined || orders >= 999999
          ? 'Ilimitados'
          : `Até ${orders.toLocaleString('pt-BR')} pedidos`,
    },
  ]
}

export function buildPlanModuleGroups(plan: PublicPlanFeatures): PlanModuleFeatureGroup[] {
  return PLAN_MODULE_GROUPS.map((module) => ({
    id: module.id,
    label: module.label,
    options: module.options.map((option) => ({
      key: option.key,
      label: option.label,
      description: option.description,
      included: Boolean(plan[option.key]),
    })),
  }))
}

export function buildPlanDetailItems(plan: PublicPlanFeatures): string[] {
  return (plan.details ?? []).map((detail) => detail.name).filter(Boolean)
}
