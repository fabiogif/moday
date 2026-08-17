"use client"

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Edit, Trash2, Eye, CheckCircle, XCircle } from "lucide-react"
import { Plan } from "../page"
import { PLAN_MODULE_OPTION_LABELS, type PlanModuleOptionKey } from "@/lib/plan-modules"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog"
import Link from "next/link"

interface DataTableProps {
  plans: Plan[]
  onDeletePlan: (id: number) => void
  onEditPlan: (plan: Plan) => void
}

export function DataTable({ plans, onDeletePlan, onEditPlan }: DataTableProps) {
  if (!plans || plans.length === 0) {
    return (
      <div className="text-center py-12 border rounded-lg bg-muted/20">
        <p className="text-muted-foreground">Nenhum plano cadastrado</p>
        <p className="text-sm text-muted-foreground mt-2">
          Crie um novo plano para começar
        </p>
      </div>
    )
  }

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Nome</TableHead>
            <TableHead>Preço</TableHead>
            <TableHead className="hidden md:table-cell">Usuários</TableHead>
            <TableHead className="hidden lg:table-cell">Pedidos/mês</TableHead>
            <TableHead className="hidden xl:table-cell">Features</TableHead>
            <TableHead>Status</TableHead>
            <TableHead className="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {plans.map((plan, index) => (
            <TableRow key={plan.id || plan.url || `plan-${index}`}>
              <TableCell>
                <div>
                  <div className="font-medium">{plan.name}</div>
                  {plan.description && (
                    <div className="text-sm text-muted-foreground mt-1">
                      {plan.description}
                    </div>
                  )}
                </div>
              </TableCell>
              <TableCell>
                <div className="font-semibold">
                  {Number(plan.price) === 0 
                    ? <Badge variant="secondary">Grátis</Badge>
                    : `R$ ${Number(plan.price).toFixed(2)}`
                  }
                </div>
              </TableCell>
              <TableCell className="hidden md:table-cell">
                {plan.max_users === null || plan.max_users >= 999999 
                  ? <Badge variant="outline">Ilimitado</Badge>
                  : plan.max_users
                }
              </TableCell>
              <TableCell className="hidden lg:table-cell">
                {plan.max_orders_per_month === null || plan.max_orders_per_month >= 999999 
                  ? <Badge variant="outline">Ilimitado</Badge>
                  : plan.max_orders_per_month
                }
              </TableCell>
              <TableCell className="hidden xl:table-cell">
                <div className="flex flex-wrap gap-1">
                  {(Object.keys(PLAN_MODULE_OPTION_LABELS) as PlanModuleOptionKey[]).map((key) =>
                    plan[key] ? (
                      <Badge key={key} variant="secondary" className="text-xs">
                        {PLAN_MODULE_OPTION_LABELS[key]}
                      </Badge>
                    ) : null
                  )}
                </div>
              </TableCell>
              <TableCell>
                {plan.is_active ? (
                  <Badge variant="default" className="gap-1">
                    <CheckCircle className="w-3 h-3" />
                    Ativo
                  </Badge>
                ) : (
                  <Badge variant="secondary" className="gap-1">
                    <XCircle className="w-3 h-3" />
                    Inativo
                  </Badge>
                )}
              </TableCell>
              <TableCell className="text-right">
                <div className="flex justify-end gap-2">
                  <Button
                    variant="ghost"
                    size="icon"
                    asChild
                    className="cursor-pointer"
                  >
                    <Link href={`/admin/plans/${plan.id}`}>
                      <Eye className="w-4 h-4" />
                    </Link>
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => onEditPlan(plan)}
                    className="cursor-pointer"
                  >
                    <Edit className="w-4 h-4" />
                  </Button>
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="cursor-pointer text-destructive hover:text-destructive"
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Excluir Plano</AlertDialogTitle>
                        <AlertDialogDescription>
                          Tem certeza que deseja excluir o plano <strong>{plan.name}</strong>?
                          Esta ação não pode ser desfeita.
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel className="cursor-pointer">Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                          onClick={() => onDeletePlan(plan.id)}
                          className="cursor-pointer bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                          Excluir
                        </AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  )
}

