'use client'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { useExpenseStats } from '@/hooks/use-expenses'
import { useAccountPayableStats } from '@/hooks/use-accounts-payable'
import { useAccountReceivableStats } from '@/hooks/use-accounts-receivable'
import {
  Wallet,
  TrendingDown,
  TrendingUp,
  DollarSign,
  AlertTriangle,
  CheckCircle2,
  Clock,
  FileText,
  ArrowRight,
  CircleDollarSign,
  Truck,
  Tag,
} from 'lucide-react'
import { useRouter } from 'next/navigation'
import {
  computeFinancialDashboardMetrics,
  formatFinancialCurrency,
  parseFinancialAmount,
} from '@/lib/financial-dashboard-metrics'
import { cn } from '@/lib/utils'

export default function FinancialDashboardPage() {
  const router = useRouter()
  const { data: expenseStats } = useExpenseStats()
  const { data: payableStats } = useAccountPayableStats()
  const { data: receivableStats } = useAccountReceivableStats()

  const fmt = formatFinancialCurrency
  const {
    totalReceivable,
    totalReceived,
    totalExpenses,
    totalOverdue,
    payableTotal,
    balance,
    receiveProgress,
    overduePercent,
  } = computeFinancialDashboardMetrics(receivableStats, payableStats, expenseStats)

  const isHealthy = balance >= 0
  const overdueRisk = totalOverdue > 0

  return (
    <div className="flex flex-col gap-6 px-4 lg:px-6 pb-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Wallet className="h-4 w-4" />
            </div>
            Painel de Controle Financeiro
          </h1>
          <p className="text-muted-foreground text-sm mt-1">Visão geral das finanças do mês atual</p>
        </div>
        <Badge
          variant="outline"
          className={cn(
            "flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium",
            isHealthy && !overdueRisk
              ? "border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-400"
              : overdueRisk
              ? "border-rose-200 bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-400"
              : "border-amber-200 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-400"
          )}
        >
          {isHealthy && !overdueRisk ? (
            <CheckCircle2 className="h-4 w-4" />
          ) : (
            <AlertTriangle className="h-4 w-4" />
          )}
          {isHealthy && !overdueRisk
            ? "Finanças saudáveis"
            : overdueRisk
            ? "Atenção: contas vencidas"
            : "Saldo negativo"}
        </Badge>
      </div>

      {/* KPI Cards */}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card className="border-l-4 border-l-emerald-500">
          <CardHeader className="pb-2">
            <CardDescription className="flex items-center justify-between">
              <span>Total a Receber</span>
              <TrendingUp className="h-4 w-4 text-emerald-500" />
            </CardDescription>
            <CardTitle className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
              {fmt(totalReceivable)}
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0 space-y-2">
            <div className="flex justify-between text-xs text-muted-foreground">
              <span>Recebido: {fmt(totalReceived)}</span>
              <span>{receiveProgress}%</span>
            </div>
            <Progress value={receiveProgress} className="h-1.5 bg-emerald-100 dark:bg-emerald-950" />
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-rose-500">
          <CardHeader className="pb-2">
            <CardDescription className="flex items-center justify-between">
              <span>Total a Pagar</span>
              <TrendingDown className="h-4 w-4 text-rose-500" />
            </CardDescription>
            <CardTitle className="text-2xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">
              {fmt(payableTotal)}
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0 space-y-2">
            <div className="flex justify-between text-xs text-muted-foreground">
              <span className={cn(totalOverdue > 0 && "text-rose-600 font-medium")}>
                {totalOverdue > 0 ? `Vencido: ${fmt(totalOverdue)}` : "Sem vencidos"}
              </span>
              {totalOverdue > 0 && <span className="text-rose-600">{overduePercent}%</span>}
            </div>
            {totalOverdue > 0 && (
              <Progress value={overduePercent} className="h-1.5 bg-rose-100 dark:bg-rose-950 [&>div]:bg-rose-500" />
            )}
          </CardContent>
        </Card>

        <Card className={cn("border-l-4", balance >= 0 ? "border-l-primary" : "border-l-rose-500")}>
          <CardHeader className="pb-2">
            <CardDescription className="flex items-center justify-between">
              <span>Saldo Projetado</span>
              <CircleDollarSign className={cn("h-4 w-4", balance >= 0 ? "text-primary" : "text-rose-500")} />
            </CardDescription>
            <CardTitle className={cn(
              "text-2xl font-bold tabular-nums",
              balance >= 0 ? "text-primary" : "text-rose-600 dark:text-rose-400"
            )}>
              {fmt(balance)}
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0">
            <p className="text-xs text-muted-foreground">
              {balance >= 0 ? "Fluxo de caixa positivo" : "Atenção ao fluxo de caixa"}
            </p>
          </CardContent>
        </Card>

        <Card className={cn(
          "border-l-4",
          totalOverdue > 0 ? "border-l-rose-500" : "border-l-emerald-500"
        )}>
          <CardHeader className="pb-2">
            <CardDescription className="flex items-center justify-between">
              <span>Despesas do Mês</span>
              <Clock className="h-4 w-4 text-amber-500" />
            </CardDescription>
            <CardTitle className="text-2xl font-bold tabular-nums text-amber-600 dark:text-amber-400">
              {fmt(totalExpenses)}
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0">
            <p className="text-xs text-muted-foreground">
              {totalOverdue > 0
                ? `${fmt(totalOverdue)} em atraso`
                : "Sem contas em atraso"}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Alerta de contas vencidas */}
      {totalOverdue > 0 && (
        <Card className="border-rose-200 bg-rose-50 dark:bg-rose-950/20 dark:border-rose-900">
          <CardContent className="flex items-center justify-between py-4">
            <div className="flex items-center gap-3">
              <div className="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900">
                <AlertTriangle className="h-5 w-5 text-rose-600 dark:text-rose-400" />
              </div>
              <div>
                <p className="font-medium text-rose-800 dark:text-rose-200">
                  Você tem {fmt(totalOverdue)} em contas vencidas
                </p>
                <p className="text-sm text-rose-600 dark:text-rose-400">
                  Regularize para evitar juros e negativação
                </p>
              </div>
            </div>
            <Button
              variant="outline"
              size="sm"
              className="border-rose-300 text-rose-700 hover:bg-rose-100 dark:text-rose-300 dark:border-rose-700"
              onClick={() => router.push('/financial/accounts-payable')}
            >
              Ver contas
              <ArrowRight className="ml-1 h-3 w-3" />
            </Button>
          </CardContent>
        </Card>
      )}

      {/* Ações Rápidas */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">Acesso Rápido</CardTitle>
          <CardDescription>Navegue para as principais áreas financeiras</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {[
              {
                label: "Contas a Receber",
                description: `${fmt(parseFinancialAmount(receivableStats?.total_pending))} pendente`,
                icon: TrendingUp,
                color: "text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30",
                href: "/financial/accounts-receivable",
              },
              {
                label: "Contas a Pagar",
                description: `${fmt(payableTotal)} em aberto`,
                icon: TrendingDown,
                color: "text-rose-600 bg-rose-50 dark:bg-rose-950/30",
                href: "/financial/accounts-payable",
              },
              {
                label: "Despesas",
                description: `${fmt(totalExpenses)} este mês`,
                icon: DollarSign,
                color: "text-amber-600 bg-amber-50 dark:bg-amber-950/30",
                href: "/financial/expenses",
              },
              {
                label: "Fornecedores",
                description: "Gerencie seus fornecedores",
                icon: Truck,
                color: "text-primary bg-primary/10",
                href: "/financial/suppliers",
              },
              {
                label: "Categorias",
                description: "Categorias financeiras",
                icon: Tag,
                color: "text-violet-600 bg-violet-50 dark:bg-violet-950/30",
                href: "/financial/categories",
              },
              {
                label: "Dados Bancários",
                description: "Contas e transações",
                icon: FileText,
                color: "text-sky-600 bg-sky-50 dark:bg-sky-950/30",
                href: "/contas-bancarias",
              },
            ].map((item) => (
              <button
                key={item.label}
                onClick={() => router.push(item.href)}
                className="flex items-center gap-3 rounded-lg border border-border p-3 text-left transition-colors hover:bg-accent hover:text-accent-foreground group"
              >
                <div className={cn("flex h-9 w-9 shrink-0 items-center justify-center rounded-lg", item.color)}>
                  <item.icon className="h-4 w-4" />
                </div>
                <div className="min-w-0">
                  <p className="text-sm font-medium leading-none mb-1">{item.label}</p>
                  <p className="text-xs text-muted-foreground truncate">{item.description}</p>
                </div>
                <ArrowRight className="ml-auto h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
              </button>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
