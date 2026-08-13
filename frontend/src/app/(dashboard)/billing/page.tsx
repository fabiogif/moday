"use client"

import { useEffect, useState } from "react"
import { useAuth } from "@/contexts/auth-context"
import { useSubscription } from "@/hooks/use-subscription"
import { apiClient } from "@/lib/api-client"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { UpgradeDialog } from "@/components/subscription/upgrade-dialog"
import { DowngradeDialog } from "@/components/subscription/downgrade-dialog"
import { CancelDialog } from "@/components/subscription/cancel-dialog"
import { ArrowUp, ArrowDown, X, FileText } from "lucide-react"
import type { PlanWithDetails } from "@/types/plan"
import type { SubscriptionInvoice } from "@/types/subscription"

const STATUS_LABELS: Record<string, { label: string; variant: "default" | "secondary" | "destructive" | "outline" }> = {
  trial:        { label: "Trial",          variant: "secondary" },
  active:       { label: "Ativo",          variant: "default" },
  pending:      { label: "Pendente",       variant: "outline" },
  under_review: { label: "Em análise",     variant: "outline" },
  delinquent:   { label: "Inadimplente",   variant: "destructive" },
  suspended:    { label: "Suspenso",       variant: "destructive" },
  cancelled:    { label: "Cancelado",      variant: "destructive" },
  expired:      { label: "Expirado",       variant: "destructive" },
}

const INVOICE_STATUS_LABELS: Record<string, { label: string; className: string }> = {
  paid:      { label: "Pago",       className: "text-green-600" },
  pending:   { label: "Pendente",   className: "text-amber-600" },
  failed:    { label: "Falhou",     className: "text-red-600" },
  refunded:  { label: "Estornado",  className: "text-blue-600" },
  cancelled: { label: "Cancelado",  className: "text-gray-500" },
}

export default function BillingPage() {
  const { trialStatus, refreshTrialStatus } = useAuth()
  const { cancelDowngrade, fetchInvoices, loading } = useSubscription()

  const [plans, setPlans] = useState<PlanWithDetails[]>([])
  const [invoices, setInvoices] = useState<SubscriptionInvoice[]>([])
  const [loadingPlans, setLoadingPlans] = useState(true)

  const [upgradeDialog, setUpgradeDialog] = useState<{ open: boolean; plan: PlanWithDetails | null }>({ open: false, plan: null })
  const [downgradeDialog, setDowngradeDialog] = useState<{ open: boolean; plan: PlanWithDetails | null }>({ open: false, plan: null })
  const [cancelDialog, setCancelDialog] = useState(false)

  const status = trialStatus as any

  useEffect(() => {
    async function load() {
      try {
        const [plansRes, invoiceList] = await Promise.all([
          apiClient.get<PlanWithDetails[]>("/api/subscription/plans"),
          fetchInvoices(),
        ])
        setPlans(plansRes?.data ?? [])
        setInvoices(invoiceList)
      } finally {
        setLoadingPlans(false)
      }
    }
    load()
  }, [])

  const currentPlan = plans.find((p) => String(p.id) === String(status?.plan_id ?? "")) ?? null
  const accountStatus = status?.account_status ?? "trial"
  const statusConfig = STATUS_LABELS[accountStatus] ?? { label: accountStatus, variant: "outline" as const }

  async function handleCancelDowngrade() {
    await cancelDowngrade()
    await refreshTrialStatus()
  }

  if (loadingPlans) {
    return (
      <div className="space-y-6 p-6">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-48 w-full" />
        <Skeleton className="h-64 w-full" />
      </div>
    )
  }

  return (
    <div className="space-y-6 p-6">
      <div>
        <h1 className="text-2xl font-bold">Assinatura e Cobrança</h1>
        <p className="text-muted-foreground">Gerencie seu plano, métodos de pagamento e histórico de faturas.</p>
      </div>

      {/* Current subscription card */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Plano atual</CardTitle>
            <Badge variant={statusConfig.variant}>{statusConfig.label}</Badge>
          </div>
          {currentPlan && (
            <CardDescription>{currentPlan.name} — R$ {Number(currentPlan.price).toFixed(2)}/mês</CardDescription>
          )}
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div>
              <p className="text-muted-foreground">Período atual</p>
              <p className="font-medium">{status?.current_period_end ?? "—"}</p>
            </div>
            <div>
              <p className="text-muted-foreground">Próxima cobrança</p>
              <p className="font-medium">{status?.next_billing_date ?? "—"}</p>
            </div>
            {status?.cancellation_pending && (
              <div className="col-span-2">
                <p className="text-amber-600 font-medium">Cancelamento solicitado — acesso mantido até {status.current_period_end}</p>
              </div>
            )}
            {status?.scheduled_downgrade && (
              <div className="col-span-2 flex items-center gap-2">
                <p className="text-amber-600 font-medium">
                  Downgrade agendado para {status.scheduled_downgrade.effective_date}
                </p>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={handleCancelDowngrade}
                  disabled={loading}
                  className="text-xs"
                >
                  Cancelar downgrade
                </Button>
              </div>
            )}
          </div>

          {!status?.cancellation_pending && accountStatus === "active" && (
            <div className="flex flex-wrap gap-2">
              <Button
                variant="destructive"
                size="sm"
                onClick={() => setCancelDialog(true)}
              >
                <X className="mr-1 h-4 w-4" />
                Cancelar assinatura
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Plan comparison / upgrade-downgrade */}
      {plans.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Planos disponíveis</CardTitle>
            <CardDescription>Faça upgrade ou downgrade do seu plano a qualquer momento.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {plans.map((plan) => {
                const isCurrent = plan.id === currentPlan?.id
                const isHigher = currentPlan && Number(plan.price) > Number(currentPlan.price)
                const isLower = currentPlan && Number(plan.price) < Number(currentPlan.price)

                return (
                  <div
                    key={plan.id}
                    className={`rounded-lg border p-4 ${isCurrent ? "border-primary bg-primary/5" : ""}`}
                  >
                    <div className="flex items-center justify-between">
                      <p className="font-semibold">{plan.name}</p>
                      {isCurrent && <Badge>Atual</Badge>}
                    </div>
                    <p className="mt-1 text-2xl font-bold">
                      R$ {Number(plan.price).toFixed(2)}
                      <span className="text-sm font-normal text-muted-foreground">/mês</span>
                    </p>
                    {!isCurrent && accountStatus === "active" && (
                      <Button
                        className="mt-3 w-full"
                        variant={isHigher ? "default" : "outline"}
                        size="sm"
                        onClick={() =>
                          isHigher
                            ? setUpgradeDialog({ open: true, plan })
                            : setDowngradeDialog({ open: true, plan })
                        }
                      >
                        {isHigher ? (
                          <><ArrowUp className="mr-1 h-4 w-4" /> Upgrade</>
                        ) : (
                          <><ArrowDown className="mr-1 h-4 w-4" /> Downgrade</>
                        )}
                      </Button>
                    )}
                  </div>
                )
              })}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Invoice history */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <FileText className="h-5 w-5" />
            <CardTitle>Histórico de faturas</CardTitle>
          </div>
        </CardHeader>
        <CardContent>
          {invoices.length === 0 ? (
            <p className="text-sm text-muted-foreground">Nenhuma fatura encontrada.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Fatura</TableHead>
                  <TableHead>Plano</TableHead>
                  <TableHead>Valor</TableHead>
                  <TableHead>Vencimento</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {invoices.map((inv) => {
                  const invStatus = INVOICE_STATUS_LABELS[inv.status] ?? { label: inv.status, className: "" }
                  return (
                    <TableRow key={inv.id}>
                      <TableCell className="font-mono text-sm">{inv.invoice_number}</TableCell>
                      <TableCell>{inv.plan_name}</TableCell>
                      <TableCell>R$ {Number(inv.amount).toFixed(2)}</TableCell>
                      <TableCell>{inv.due_date}</TableCell>
                      <TableCell>
                        <span className={`font-medium ${invStatus.className}`}>{invStatus.label}</span>
                      </TableCell>
                    </TableRow>
                  )
                })}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Dialogs */}
      {upgradeDialog.plan && (
        <UpgradeDialog
          open={upgradeDialog.open}
          onOpenChange={(open) => setUpgradeDialog((s) => ({ ...s, open }))}
          currentPlan={currentPlan}
          targetPlan={upgradeDialog.plan}
          onSuccess={refreshTrialStatus}
        />
      )}

      {downgradeDialog.plan && (
        <DowngradeDialog
          open={downgradeDialog.open}
          onOpenChange={(open) => setDowngradeDialog((s) => ({ ...s, open }))}
          currentPlan={currentPlan}
          targetPlan={downgradeDialog.plan}
          nextBillingDate={status?.next_billing_date ?? null}
          onSuccess={refreshTrialStatus}
        />
      )}

      <CancelDialog
        open={cancelDialog}
        onOpenChange={setCancelDialog}
        currentPeriodEnd={status?.current_period_end ?? null}
        onSuccess={refreshTrialStatus}
      />
    </div>
  )
}
