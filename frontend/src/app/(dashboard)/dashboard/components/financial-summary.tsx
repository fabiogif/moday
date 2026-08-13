"use client"

import Link from "next/link"
import { Wallet, TrendingDown, TrendingUp, AlertTriangle, ArrowRight, CheckCircle2 } from "lucide-react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import { useExpenseStats } from "@/hooks/use-expenses"
import { useAccountPayableStats } from "@/hooks/use-accounts-payable"
import { useAccountReceivableStats } from "@/hooks/use-accounts-receivable"
import { computeFinancialDashboardMetrics, formatFinancialCurrency } from "@/lib/financial-dashboard-metrics"
import { cn } from "@/lib/utils"

/**
 * Reaproveita os mesmos hooks e a mesma função de cálculo que
 * /financial/dashboard usa — sem duplicar a lógica de negócio. Qualquer
 * divergência entre os dois é, por definição, um bug (mesma fonte).
 */
export function FinancialSummary() {
  const { data: expenseStats, loading: loadingExpense } = useExpenseStats()
  const { data: payableStats, loading: loadingPayable } = useAccountPayableStats()
  const { data: receivableStats, loading: loadingReceivable } = useAccountReceivableStats()

  const loading = loadingExpense || loadingPayable || loadingReceivable

  const fmt = formatFinancialCurrency
  const { totalReceivable, totalExpenses, totalOverdue, payableTotal, balance } =
    computeFinancialDashboardMetrics(receivableStats, payableStats, expenseStats)

  const isHealthy = balance >= 0
  const overdueRisk = totalOverdue > 0

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Wallet className="h-4 w-4" />
            </div>
            <div>
              <CardTitle className="text-base">Financeiro</CardTitle>
              <CardDescription>Resumo do mês atual</CardDescription>
            </div>
          </div>
          {!loading && (
            <Badge
              variant="outline"
              className={cn(
                "flex items-center gap-1.5 text-xs font-medium",
                isHealthy && !overdueRisk
                  ? "border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-400"
                  : "border-rose-200 bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-400"
              )}
            >
              {isHealthy && !overdueRisk ? (
                <CheckCircle2 className="h-3.5 w-3.5" />
              ) : (
                <AlertTriangle className="h-3.5 w-3.5" />
              )}
              {isHealthy && !overdueRisk ? "Saudável" : overdueRisk ? "Contas vencidas" : "Saldo negativo"}
            </Badge>
          )}
        </div>
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="space-y-1.5">
                <Skeleton className="h-3 w-16" />
                <Skeleton className="h-6 w-20" />
              </div>
            ))}
          </div>
        ) : (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div>
              <p className="flex items-center gap-1 text-xs text-muted-foreground">
                <TrendingUp className="h-3 w-3 text-emerald-500" /> A receber
              </p>
              <p className="text-lg font-semibold tabular-nums">{fmt(totalReceivable)}</p>
            </div>
            <div>
              <p className="flex items-center gap-1 text-xs text-muted-foreground">
                <TrendingDown className="h-3 w-3 text-rose-500" /> Despesas
              </p>
              <p className="text-lg font-semibold tabular-nums">{fmt(totalExpenses)}</p>
            </div>
            <div>
              <p className="flex items-center gap-1 text-xs text-muted-foreground">
                <Wallet className="h-3 w-3" /> A pagar
              </p>
              <p className="text-lg font-semibold tabular-nums">{fmt(payableTotal)}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Saldo projetado</p>
              <p className={cn("text-lg font-semibold tabular-nums", isHealthy ? "text-emerald-600" : "text-rose-600")}>
                {fmt(balance)}
              </p>
            </div>
          </div>
        )}

        <Link
          href="/financial/dashboard"
          className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
        >
          Ver detalhes <ArrowRight className="h-3.5 w-3.5" />
        </Link>
      </CardContent>
    </Card>
  )
}
