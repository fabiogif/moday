'use client'

import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Expense, ExpenseFormData } from '@/hooks/use-expenses'
import { FinancialCategory } from '@/hooks/use-financial-categories'
import { Supplier } from '@/hooks/use-suppliers'
import {
  AlertCircle,
  ChevronLeft,
  ChevronRight,
  FileText,
  Loader2,
} from 'lucide-react'
import { format } from 'date-fns'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { extractValidationErrors } from '@/lib/error-formatter'
import { OrderStepper } from '@/components/order-stepper'
import {
  ACCOUNT_FORM_STEPS,
  EXPENSE_STATUS_LABELS,
  FieldError,
  FormField,
  formatAccountCurrency,
  formatAccountDate,
  ReviewRow,
  scheduleWizardStep,
  WIZARD_DIALOG_CONTENT_CLASS,
} from '../../components/account-form-shared'

interface ExpenseFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  expense?: Expense | null
  categories: FinancialCategory[]
  suppliers: Supplier[]
  onSubmit: (data: ExpenseFormData) => Promise<void>
  isLoading?: boolean
}

const STEP_FIELDS: (keyof ExpenseFormData)[][] = [
  ['description'],
  ['issue_date', 'due_date', 'amount', 'status'],
  [],
]

const FIELD_STEP: Partial<Record<keyof ExpenseFormData, number>> = {
  description: 0,
  financial_category_id: 0,
  supplier_id: 0,
  issue_date: 1,
  due_date: 1,
  amount: 1,
  status: 1,
  payment_date: 1,
  notes: 2,
}

export function ExpenseFormDialog({
  open,
  onOpenChange,
  expense,
  categories,
  suppliers,
  onSubmit,
  isLoading,
}: ExpenseFormDialogProps) {
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})
  const [currentStep, setCurrentStep] = useState(0)
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set())

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setValue,
    watch,
    trigger,
  } = useForm<ExpenseFormData>({
    mode: 'onBlur',
  })

  const values = watch()
  const issueDate = watch('issue_date')

  useEffect(() => {
    if (expense) {
      setValue('description', expense.description)
      setValue('financial_category_id', expense.category?.id)
      setValue('supplier_id', expense.supplier?.id)
      setValue('payment_method_id', expense.payment_method?.uuid)
      setValue('issue_date', expense.issue_date)
      setValue('due_date', expense.due_date)
      setValue('payment_date', expense.payment_date || '')
      setValue('amount', expense.amount)
      setValue('status', expense.status)
      setValue('notes', expense.notes || '')
      setBackendErrors({})
    } else {
      reset()
      const today = format(new Date(), 'yyyy-MM-dd')
      setValue('issue_date', today)
      setValue('due_date', today)
      setValue('status', 'pendente')
      setBackendErrors({})
    }
    setCurrentStep(0)
    setCompletedSteps(new Set())
  }, [expense, setValue, reset, open])

  const goNext = async () => {
    const fields = STEP_FIELDS[currentStep]
    const valid = fields.length === 0 || (await trigger(fields))
    if (!valid) return
    setCompletedSteps((prev) => new Set(prev).add(currentStep))
    scheduleWizardStep(() => {
      setCurrentStep((s) => Math.min(s + 1, ACCOUNT_FORM_STEPS.length - 1))
    })
  }

  const goBack = () => setCurrentStep((s) => Math.max(s - 1, 0))

  const goToStep = (step: number) => {
    if (step <= currentStep || completedSteps.has(step)) {
      setCurrentStep(step)
    }
  }

  const handleFormSubmit = async (data: ExpenseFormData) => {
    try {
      setBackendErrors({})
      await onSubmit(data)
      reset()
      setCurrentStep(0)
      setCompletedSteps(new Set())
    } catch (error: unknown) {
      const validationErrors = extractValidationErrors(error)
      setBackendErrors(validationErrors)

      if (validationErrors._general) {
        toast.error(validationErrors._general)
      } else {
        const firstError = Object.values(validationErrors)[0]
        if (firstError) toast.error(firstError)
      }

      const firstErrorField = Object.keys(validationErrors).find((k) => k !== '_general') as
        | keyof ExpenseFormData
        | undefined
      const stepWithError = firstErrorField ? FIELD_STEP[firstErrorField] : undefined
      if (stepWithError !== undefined) {
        setCurrentStep(stepWithError)
      }
    }
  }

  const getErrorMessage = (field: keyof ExpenseFormData) =>
    errors[field]?.message || backendErrors[field]

  const hasError = (field: keyof ExpenseFormData) =>
    Boolean(errors[field] || backendErrors[field])

  const isLastStep = currentStep === ACCOUNT_FORM_STEPS.length - 1

  const categoryName =
    categories.find((c) => c.id === values.financial_category_id)?.name || '—'
  const supplierName =
    suppliers.find((s) => s.id === values.supplier_id)?.name || '—'

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className={WIZARD_DIALOG_CONTENT_CLASS}>
        <DialogHeader className="shrink-0 space-y-1 text-left">
          <DialogTitle className="flex items-center gap-2">
            <FileText className="h-5 w-5" />
            {expense ? 'Editar Despesa' : 'Nova Despesa'}
          </DialogTitle>
          <DialogDescription>
            Registre uma nova despesa. Campos com * são obrigatórios.
          </DialogDescription>
        </DialogHeader>

        <div className="shrink-0 overflow-x-hidden">
          <OrderStepper
            currentStep={currentStep}
            steps={ACCOUNT_FORM_STEPS}
            onStepClick={goToStep}
            completedSteps={completedSteps}
          />
        </div>

        {backendErrors._general && (
          <Alert variant="destructive" className="shrink-0">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{backendErrors._general}</AlertDescription>
          </Alert>
        )}

        <form
          onSubmit={(e) => {
            if (!isLastStep) {
              e.preventDefault()
              return
            }
            void handleSubmit(handleFormSubmit)(e)
          }}
          className="flex min-h-0 flex-1 flex-col gap-6 overflow-x-hidden"
        >
          <div className="min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden pr-1">
            {currentStep === 0 && (
              <div className="space-y-4">
                <h3 className="text-base font-semibold">Informações Básicas</h3>

                <FormField>
                  <Label htmlFor="description">Descrição *</Label>
                  <Input
                    id="description"
                    className={cn('h-9', hasError('description') && 'border-red-500')}
                    {...register('description', { required: 'A descrição é obrigatória' })}
                    placeholder="Ex: Aluguel do mês, Conta de energia..."
                  />
                  <FieldError message={getErrorMessage('description')} />
                </FormField>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <FormField>
                    <Label htmlFor="financial_category_id">Categoria</Label>
                    <Select
                      value={watch('financial_category_id')?.toString() || undefined}
                      onValueChange={(value) =>
                        setValue('financial_category_id', parseInt(value, 10))
                      }
                    >
                      <SelectTrigger id="financial_category_id" className="h-9 w-full">
                        <SelectValue placeholder="Selecione uma categoria" />
                      </SelectTrigger>
                      <SelectContent>
                        {categories.map((cat) => (
                          <SelectItem key={cat.id} value={cat.id.toString()}>
                            {cat.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </FormField>

                  <FormField>
                    <Label htmlFor="supplier_id">Fornecedor</Label>
                    <Select
                      value={watch('supplier_id')?.toString() || undefined}
                      onValueChange={(value) =>
                        setValue('supplier_id', parseInt(value, 10))
                      }
                    >
                      <SelectTrigger id="supplier_id" className="h-9 w-full">
                        <SelectValue placeholder="Selecione um fornecedor" />
                      </SelectTrigger>
                      <SelectContent>
                        {suppliers.map((sup) => (
                          <SelectItem key={sup.id} value={sup.id.toString()}>
                            {sup.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </FormField>
                </div>
              </div>
            )}

            {currentStep === 1 && (
              <div className="space-y-4">
                <h3 className="text-base font-semibold">Dados Financeiros</h3>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <FormField>
                    <Label htmlFor="issue_date">Data de Emissão *</Label>
                    <Input
                      id="issue_date"
                      type="date"
                      className={cn('h-9', hasError('issue_date') && 'border-red-500')}
                      {...register('issue_date', { required: 'Data obrigatória' })}
                    />
                    <FieldError message={getErrorMessage('issue_date')} />
                  </FormField>

                  <FormField>
                    <Label htmlFor="due_date">Data de Vencimento *</Label>
                    <Input
                      id="due_date"
                      type="date"
                      min={issueDate}
                      className={cn('h-9', hasError('due_date') && 'border-red-500')}
                      {...register('due_date', { required: 'Data obrigatória' })}
                    />
                    <FieldError message={getErrorMessage('due_date')} />
                  </FormField>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <FormField>
                    <Label htmlFor="amount">Valor *</Label>
                    <Input
                      id="amount"
                      type="number"
                      step="0.01"
                      min="0.01"
                      className={cn('h-9', hasError('amount') && 'border-red-500')}
                      {...register('amount', {
                        required: 'Valor obrigatório',
                        valueAsNumber: true,
                        min: { value: 0.01, message: 'Valor deve ser maior que zero' },
                      })}
                      placeholder="0,00"
                    />
                    <FieldError message={getErrorMessage('amount')} />
                  </FormField>

                  <FormField>
                    <Label htmlFor="status">Status *</Label>
                    <input
                      type="hidden"
                      {...register('status', { required: 'Status é obrigatório' })}
                    />
                    <Select
                      value={watch('status') || 'pendente'}
                      onValueChange={(value) =>
                        setValue('status', value as ExpenseFormData['status'], {
                          shouldValidate: true,
                        })
                      }
                    >
                      <SelectTrigger id="status" className="h-9 w-full">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {Object.entries(EXPENSE_STATUS_LABELS).map(([value, label]) => (
                          <SelectItem key={value} value={value}>
                            {label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <FieldError message={getErrorMessage('status')} />
                  </FormField>
                </div>

                {watch('status') === 'pago' && (
                  <FormField>
                    <Label htmlFor="payment_date">Data de Pagamento</Label>
                    <Input
                      id="payment_date"
                      type="date"
                      className="h-9"
                      {...register('payment_date')}
                    />
                  </FormField>
                )}
              </div>
            )}

            {currentStep === 2 && (
              <div className="space-y-4">
                <h3 className="text-base font-semibold">Observações e Revisão</h3>

                <FormField>
                  <Label htmlFor="notes">Observações</Label>
                  <Textarea
                    id="notes"
                    {...register('notes')}
                    placeholder="Informações adicionais..."
                    rows={3}
                    className="resize-none"
                  />
                  <FieldError message={getErrorMessage('notes')} />
                </FormField>

                <div className="rounded-lg border bg-muted/40 p-4">
                  <h4 className="mb-3 text-sm font-semibold">Resumo da despesa</h4>
                  <dl className="space-y-2">
                    <ReviewRow label="Descrição" value={values.description} />
                    <ReviewRow label="Categoria" value={categoryName} />
                    <ReviewRow label="Fornecedor" value={supplierName} />
                    <ReviewRow
                      label="Emissão"
                      value={formatAccountDate(values.issue_date)}
                    />
                    <ReviewRow
                      label="Vencimento"
                      value={formatAccountDate(values.due_date)}
                    />
                    <ReviewRow
                      label="Valor"
                      value={formatAccountCurrency(values.amount)}
                    />
                    <ReviewRow
                      label="Status"
                      value={EXPENSE_STATUS_LABELS[values.status || ''] || values.status}
                    />
                    {values.status === 'pago' && (
                      <ReviewRow
                        label="Pagamento"
                        value={formatAccountDate(values.payment_date)}
                      />
                    )}
                    <ReviewRow label="Observações" value={values.notes || '—'} />
                  </dl>
                </div>
              </div>
            )}
          </div>

          <DialogFooter className="shrink-0 grid grid-cols-1 gap-2 sm:grid-cols-2">
            {currentStep > 0 ? (
              <Button
                type="button"
                variant="outline"
                className="h-9 w-full sm:justify-self-start"
                onClick={goBack}
                disabled={isLoading}
              >
                <ChevronLeft className="mr-1 h-4 w-4" />
                Voltar
              </Button>
            ) : (
              <Button
                type="button"
                variant="outline"
                className="h-9 w-full sm:justify-self-start"
                onClick={() => onOpenChange(false)}
                disabled={isLoading}
              >
                Cancelar
              </Button>
            )}

            {isLastStep ? (
              <Button
                type="submit"
                className="h-9 w-full sm:justify-self-end"
                disabled={isLoading}
              >
                {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {expense ? 'Atualizar' : 'Criar'}
              </Button>
            ) : (
              <Button
                type="button"
                className="h-9 w-full sm:justify-self-end"
                onClick={(e) => {
                  e.preventDefault()
                  e.stopPropagation()
                  void goNext()
                }}
                disabled={isLoading}
              >
                Continuar
                <ChevronRight className="ml-1 h-4 w-4" />
              </Button>
            )}
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
