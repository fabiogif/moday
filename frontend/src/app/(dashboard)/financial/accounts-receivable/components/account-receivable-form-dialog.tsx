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
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import {
  AccountReceivable,
  AccountReceivableFormData,
} from '@/hooks/use-accounts-receivable'
import { FinancialCategory } from '@/hooks/use-financial-categories'
import {
  AlertCircle,
  CheckCircle,
  ChevronLeft,
  ChevronRight,
  Loader2,
  Search,
} from 'lucide-react'
import { format } from 'date-fns'
import { extractValidationErrors } from '@/lib/error-formatter'
import { toast } from 'sonner'
import { endpoints, apiClient } from '@/lib/api-client'
import { OrderStepper } from '@/components/order-stepper'
import { cn } from '@/lib/utils'
import {
  ACCOUNT_FORM_STEPS,
  FieldError,
  FormField,
  formatAccountCurrency,
  formatAccountDate,
  RECEIVABLE_STATUS_LABELS,
  ReviewRow,
  scheduleWizardStep,
  WIZARD_DIALOG_CONTENT_CLASS,
} from '../../components/account-form-shared'

interface AccountReceivableFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  account?: AccountReceivable | null
  categories: FinancialCategory[]
  onSubmit: (data: AccountReceivableFormData) => Promise<void>
  isLoading?: boolean
}

const STEP_FIELDS: (keyof AccountReceivableFormData)[][] = [
  ['description'],
  ['issue_date', 'due_date', 'amount', 'status'],
  [],
]

const FIELD_STEP: Partial<Record<keyof AccountReceivableFormData, number>> = {
  description: 0,
  financial_category_id: 0,
  order_id: 0,
  client_id: 0,
  issue_date: 1,
  due_date: 1,
  amount: 1,
  discount: 1,
  interest: 1,
  status: 1,
  document_number: 1,
  notes: 2,
}

export function AccountReceivableFormDialog({
  open,
  onOpenChange,
  account,
  categories,
  onSubmit,
  isLoading,
}: AccountReceivableFormDialogProps) {
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})
  const [currentStep, setCurrentStep] = useState(0)
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set())
  const [orderNumber, setOrderNumber] = useState('')
  const [searchingOrder, setSearchingOrder] = useState(false)
  const [orderFound, setOrderFound] = useState<{
    id: number
    identify: string
    client_name?: string
    total: number
    created_at?: string
    payment_method_name?: string
  } | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setValue,
    watch,
    trigger,
  } = useForm<AccountReceivableFormData>({
    mode: 'onBlur',
  })

  const issueDate = watch('issue_date')
  const values = watch()

  const handleSearchOrder = async () => {
    if (!orderNumber || orderNumber.trim() === '') {
      toast.error('Digite o número do pedido')
      return
    }

    setSearchingOrder(true)
    setOrderFound(null)

    try {
      const response: { data?: Array<{ id: number }> } = await apiClient.get(
        `${endpoints.orders.searchByNumber}?query=${orderNumber}`
      )

      if (Array.isArray(response?.data) && response.data.length > 0) {
        const order = response.data[0]
        const detailsResponse: {
          data?: {
            id: number
            identify: string
            total: number
            client_id?: number
            client_name?: string
            payment_method_id?: string
            payment_method_name?: string
            created_at?: string
          }
        } = await apiClient.get(endpoints.orders.getDetails(order.id))

        if (detailsResponse?.data) {
          const orderDetails = detailsResponse.data
          setOrderFound(orderDetails)
          setValue('order_id', orderDetails.id)
          setValue('description', `Venda - Pedido ${orderDetails.identify}`)
          setValue('amount', orderDetails.total)

          if (orderDetails.client_id) {
            setValue('client_id', orderDetails.client_id)
          }

          if (orderDetails.payment_method_id) {
            setValue('payment_method_id', orderDetails.payment_method_id)
          }

          toast.success('Pedido encontrado! Dados preenchidos automaticamente.')
        }
      } else {
        toast.error('Pedido não encontrado')
        setOrderFound(null)
      }
    } catch {
      toast.error('Erro ao buscar pedido')
      setOrderFound(null)
    } finally {
      setSearchingOrder(false)
    }
  }

  useEffect(() => {
    if (account) {
      setValue('description', account.description)
      setValue('financial_category_id', account.category?.id)
      setValue('client_id', account.client?.id)
      setValue('order_id', account.order_id)
      setValue('issue_date', account.issue_date)
      setValue('due_date', account.due_date)
      setValue('amount', account.amount)
      setValue('status', account.status || 'pendente')
      setValue('document_number', account.document_number || '')
      setValue('discount', account.discount)
      setValue('interest', account.interest)
      setValue('fine', account.fine)
      setValue('notes', account.notes || '')
      setBackendErrors({})
      setOrderNumber(account.order?.identify || '')
      setOrderFound(null)
    } else {
      reset()
      const today = format(new Date(), 'yyyy-MM-dd')
      setValue('issue_date', today)
      setValue('due_date', today)
      setValue('status', 'pendente')
      setBackendErrors({})
      setOrderNumber('')
      setOrderFound(null)
    }
    setCurrentStep(0)
    setCompletedSteps(new Set())
  }, [account, setValue, reset, open])

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

  const handleFormSubmit = async (data: AccountReceivableFormData) => {
    try {
      setBackendErrors({})
      await onSubmit(data)
      reset()
      setCurrentStep(0)
      setCompletedSteps(new Set())
      setOrderNumber('')
      setOrderFound(null)
    } catch (error: unknown) {
      const validationErrors = extractValidationErrors(error)
      setBackendErrors(validationErrors)

      const errorCount = Object.keys(validationErrors).filter((k) => k !== '_general').length
      if (validationErrors._general) {
        toast.error(validationErrors._general)
      } else if (errorCount > 0) {
        const firstError = Object.values(validationErrors)[0]
        toast.error(firstError, {
          description: errorCount > 1 ? `${errorCount - 1} outro(s) erro(s)` : undefined,
        })
      }

      const firstErrorField = Object.keys(validationErrors).find((k) => k !== '_general') as
        | keyof AccountReceivableFormData
        | undefined
      const stepWithError = firstErrorField ? FIELD_STEP[firstErrorField] : undefined
      if (stepWithError !== undefined) {
        setCurrentStep(stepWithError)
      }
    }
  }

  const getErrorMessage = (field: keyof AccountReceivableFormData) =>
    errors[field]?.message || backendErrors[field]

  const hasError = (field: keyof AccountReceivableFormData) =>
    Boolean(errors[field] || backendErrors[field])

  const isLastStep = currentStep === ACCOUNT_FORM_STEPS.length - 1

  const categoryName =
    categories.find((c) => c.id === values.financial_category_id)?.name || '—'

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className={WIZARD_DIALOG_CONTENT_CLASS}>
        <DialogHeader className="shrink-0 space-y-1 text-left">
          <DialogTitle>
            {account ? 'Editar Conta a Receber' : 'Nova Conta a Receber'}
          </DialogTitle>
          <DialogDescription>
            Preencha os dados da conta. Campos com * são obrigatórios.
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
                    {...register('description', { required: 'Descrição é obrigatória' })}
                    placeholder="Ex: Venda de Produtos, Prestação de Serviços..."
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
                      disabled={categories.length === 0}
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
                    {categories.length === 0 && (
                      <p className="text-xs text-muted-foreground">
                        Cadastre categorias do tipo Receita em Financeiro → Categorias.
                      </p>
                    )}
                  </FormField>

                  <FormField>
                    <Label htmlFor="orderNumber">Número do Pedido (Opcional)</Label>
                    <div className="flex items-center gap-2">
                      <Input
                        id="orderNumber"
                        type="text"
                        className="h-9 min-w-0 flex-1"
                        value={orderNumber}
                        onChange={(e) => setOrderNumber(e.target.value)}
                        placeholder="Ex: 001, 1234..."
                        onKeyDown={(e) => {
                          if (e.key === 'Enter') {
                            e.preventDefault()
                            handleSearchOrder()
                          }
                        }}
                      />
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-9 w-9 shrink-0 p-0"
                        onClick={handleSearchOrder}
                        disabled={searchingOrder || !orderNumber}
                        aria-label="Buscar pedido"
                      >
                        {searchingOrder ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                          <Search className="h-4 w-4" />
                        )}
                      </Button>
                    </div>
                    <input type="hidden" {...register('order_id')} />
                    {orderFound && (
                      <Alert className="mt-2 border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/40">
                        <CheckCircle className="h-4 w-4 text-green-600" />
                        <AlertTitle className="text-green-800 dark:text-green-200">
                          Pedido #{orderFound.identify}
                        </AlertTitle>
                        <AlertDescription className="text-green-700 dark:text-green-300">
                          <div className="mt-1 space-y-1 text-sm">
                            {orderFound.client_name && (
                              <p>Cliente: {orderFound.client_name}</p>
                            )}
                            <p>Total: {formatAccountCurrency(orderFound.total)}</p>
                            {orderFound.created_at && <p>Data: {orderFound.created_at}</p>}
                            {orderFound.payment_method_name && (
                              <p>Forma de Pagamento: {orderFound.payment_method_name}</p>
                            )}
                          </div>
                        </AlertDescription>
                      </Alert>
                    )}
                    <FieldError message={getErrorMessage('order_id')} />
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
                      {...register('issue_date', {
                        required: 'Data de emissão é obrigatória',
                        onChange: (e) => {
                          const currentDueDate = watch('due_date')
                          if (currentDueDate && currentDueDate < e.target.value) {
                            setValue('due_date', e.target.value)
                          }
                        },
                      })}
                    />
                    <FieldError message={getErrorMessage('issue_date')} />
                  </FormField>

                  <FormField>
                    <Label htmlFor="due_date">Data de Vencimento *</Label>
                    <Input
                      id="due_date"
                      type="date"
                      className={cn('h-9', hasError('due_date') && 'border-red-500')}
                      min={issueDate || format(new Date(), 'yyyy-MM-dd')}
                      {...register('due_date', {
                        required: 'Data de vencimento é obrigatória',
                        validate: (value) => {
                          const issue = watch('issue_date')
                          if (issue && value < issue) {
                            return 'A data de vencimento deve ser igual ou posterior à data de emissão'
                          }
                          return true
                        },
                      })}
                    />
                    <FieldError message={getErrorMessage('due_date')} />
                  </FormField>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                  <FormField>
                    <Label htmlFor="amount">Valor *</Label>
                    <Input
                      id="amount"
                      type="number"
                      step="0.01"
                      min="0.01"
                      className={cn('h-9', hasError('amount') && 'border-red-500')}
                      {...register('amount', {
                        required: 'Valor é obrigatório',
                        valueAsNumber: true,
                        min: { value: 0.01, message: 'Valor deve ser maior que zero' },
                      })}
                      placeholder="0,00"
                    />
                    <FieldError message={getErrorMessage('amount')} />
                  </FormField>

                  <FormField>
                    <Label htmlFor="discount">Desconto</Label>
                    <Input
                      id="discount"
                      type="number"
                      step="0.01"
                      className="h-9"
                      {...register('discount', { valueAsNumber: true })}
                      placeholder="0,00"
                    />
                  </FormField>

                  <FormField>
                    <Label htmlFor="interest">Juros</Label>
                    <Input
                      id="interest"
                      type="number"
                      step="0.01"
                      className="h-9"
                      {...register('interest', { valueAsNumber: true })}
                      placeholder="0,00"
                    />
                  </FormField>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <FormField>
                    <Label htmlFor="status">Status *</Label>
                    <input
                      type="hidden"
                      {...register('status', { required: 'Status é obrigatório' })}
                    />
                    <Select
                      value={watch('status') || 'pendente'}
                      onValueChange={(value) =>
                        setValue('status', value, { shouldValidate: true })
                      }
                    >
                      <SelectTrigger
                        id="status"
                        className={cn('h-9 w-full', hasError('status') && 'border-red-500')}
                      >
                        <SelectValue placeholder="Selecione o status" />
                      </SelectTrigger>
                      <SelectContent>
                        {Object.entries(RECEIVABLE_STATUS_LABELS).map(([value, label]) => (
                          <SelectItem key={value} value={value}>
                            {label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <FieldError message={getErrorMessage('status')} />
                  </FormField>

                  <FormField>
                    <Label htmlFor="document_number">Número do Documento</Label>
                    <Input
                      id="document_number"
                      className="h-9"
                      {...register('document_number')}
                      placeholder="Ex: NF-12345"
                    />
                  </FormField>
                </div>
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
                  <h4 className="mb-3 text-sm font-semibold">Resumo da conta</h4>
                  <dl className="space-y-2">
                    <ReviewRow label="Descrição" value={values.description} />
                    <ReviewRow label="Categoria" value={categoryName} />
                    <ReviewRow
                      label="Pedido"
                      value={
                        orderFound?.identify ||
                        account?.order?.identify ||
                        values.order_id ||
                        '—'
                      }
                    />
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
                      label="Desconto"
                      value={formatAccountCurrency(values.discount)}
                    />
                    <ReviewRow
                      label="Juros"
                      value={formatAccountCurrency(values.interest)}
                    />
                    <ReviewRow
                      label="Status"
                      value={RECEIVABLE_STATUS_LABELS[values.status] || values.status}
                    />
                    <ReviewRow
                      label="Documento"
                      value={values.document_number || '—'}
                    />
                    <ReviewRow label="Observações" value={values.notes || '—'} />
                  </dl>
                </div>
              </div>
            )}
          </div>

          <DialogFooter className="shrink-0 grid grid-cols-1 gap-2 sm:grid-cols-2 sm:justify-between">
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
                {account ? 'Atualizar' : 'Criar'}
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
