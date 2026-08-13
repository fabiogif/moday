"use client"

import { useEffect, useMemo, useState, type ReactNode } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Switch } from "@/components/ui/switch"
import { Calendar } from "@/components/ui/calendar"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import {
  AlertCircle,
  CalendarIcon,
  ChevronLeft,
  ChevronRight,
  ClipboardCheck,
  Loader2,
  Percent,
  Tag,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { format, parseISO } from "date-fns"
import { OrderStepper } from "@/components/order-stepper"
import { apiClient, endpoints } from "@/lib/api-client"
import { extractValidationErrors } from "@/lib/error-formatter"
import { scheduleWizardStep } from "@/app/(dashboard)/financial/components/account-form-shared"
import { toast } from "sonner"

export type CouponFormValues = {
  code: string
  name: string
  description: string
  discount_type: "percentage" | "fixed"
  discount_value: string
  max_discount_amount: string
  minimum_order_amount: string
  usage_limit: string
  usage_limit_per_client: string
  start_at: string
  end_at: string
  is_active: boolean
  is_featured: boolean
  optionals: string[]
}

const defaultValues: CouponFormValues = {
  code: "",
  name: "",
  description: "",
  discount_type: "percentage",
  discount_value: "10",
  max_discount_amount: "",
  minimum_order_amount: "",
  usage_limit: "",
  usage_limit_per_client: "",
  start_at: "",
  end_at: "",
  is_active: true,
  is_featured: false,
  optionals: [],
}

const STEPS = [
  { label: "Detalhes", icon: Tag },
  { label: "Regras", icon: Percent },
  { label: "Validade", icon: ClipboardCheck },
]

type CouponFormSubmitPayload = {
  values: CouponFormValues
  imageFile: File | null
  removeImage: boolean
}

interface CouponFormProps {
  mode: "create" | "edit"
  busy?: boolean
  initialValues?: Partial<CouponFormValues>
  initialImageUrl?: string | null
  couponUuid?: string | null
  onCancel?: () => void
  onSubmit: (payload: CouponFormSubmitPayload) => Promise<void>
}

export function CouponForm({
  mode,
  busy,
  initialValues,
  initialImageUrl = null,
  couponUuid = null,
  onCancel,
  onSubmit,
}: CouponFormProps) {
  const [formState, setFormState] = useState<CouponFormValues>({ ...defaultValues })
  const [imageFile, setImageFile] = useState<File | null>(null)
  const [imagePreview, setImagePreview] = useState<string | null>(initialImageUrl)
  const [removeImage, setRemoveImage] = useState(false)
  const [timeInputs, setTimeInputs] = useState({ start_at: "", end_at: "" })
  const [currentStep, setCurrentStep] = useState(0)
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set())
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})
  const [errorSnapshot, setErrorSnapshot] = useState<string | null>(null)
  const [validatingStep, setValidatingStep] = useState(false)

  const parseDateValue = (value: string) => {
    if (!value) return undefined
    try {
      return parseISO(value)
    } catch {
      return undefined
    }
  }

  useEffect(() => {
    const normalized = normalizeValues(initialValues)
    setFormState(normalized)
    setCurrentStep(0)
    setCompletedSteps(new Set())
    setFieldErrors({})
    setBackendErrors({})
    setErrorSnapshot(null)

    const startDate = normalized.start_at ? parseDateValue(normalized.start_at) : undefined
    const endDate = normalized.end_at ? parseDateValue(normalized.end_at) : undefined

    setTimeInputs({
      start_at: startDate ? format(startDate, "HH:mm") : "",
      end_at: endDate ? format(endDate, "HH:mm") : "",
    })
  }, [initialValues])

  useEffect(() => {
    setImagePreview(initialImageUrl ?? null)
    setRemoveImage(false)
    setImageFile(null)
  }, [initialImageUrl])

  useEffect(() => {
    return () => {
      if (imagePreview && imagePreview.startsWith("blob:")) {
        URL.revokeObjectURL(imagePreview)
      }
    }
  }, [imagePreview])

  const getStepSnapshot = (step: number, values: CouponFormValues) => {
    const fields = STEP_SNAPSHOT_FIELDS[step]
    return JSON.stringify(Object.fromEntries(fields.map((field) => [field, values[field] ?? ""])))
  }

  const stepBackendErrorKeys = useMemo(() => {
    const fields = STEP_SNAPSHOT_FIELDS[currentStep] as string[]
    return Object.keys(backendErrors).filter((key) => fields.includes(key))
  }, [backendErrors, currentStep])

  const hasPendingBackendErrors = stepBackendErrorKeys.length > 0
  const fieldsChangedSinceError =
    errorSnapshot !== null && getStepSnapshot(currentStep, formState) !== errorSnapshot

  const canContinue =
    !busy && !validatingStep && (!hasPendingBackendErrors || fieldsChangedSinceError)

  const isLastStep = currentStep === STEPS.length - 1

  const handleInputChange = (field: keyof CouponFormValues, value: string | boolean) => {
    setFormState((prev) => ({ ...prev, [field]: value }))
    if (fieldErrors[field]) {
      setFieldErrors((prev) => {
        const next = { ...prev }
        delete next[field]
        return next
      })
    }
  }

  const handleDateSelection = (
    field: keyof Pick<CouponFormValues, "start_at" | "end_at">,
    date: Date | undefined
  ) => {
    if (!date) {
      handleInputChange(field, "")
      setTimeInputs((prev) => ({ ...prev, [field]: "" }))
      return
    }

    const fallbackTime =
      timeInputs[field] && timeInputs[field].includes(":") ? timeInputs[field] : "00:00"

    const [hours, minutes] = fallbackTime.split(":").map((value) => Number(value) || 0)

    const updated = new Date(date)
    updated.setHours(hours, minutes, 0, 0)
    handleInputChange(field, updated.toISOString())
    setTimeInputs((prev) => ({ ...prev, [field]: fallbackTime }))
  }

  const handleTimeChange = (
    field: keyof Pick<CouponFormValues, "start_at" | "end_at">,
    value: string
  ) => {
    let input = value.replace(/[^0-9]/g, "").slice(0, 4)
    if (input.length > 2) {
      input = `${input.slice(0, 2)}:${input.slice(2)}`
    }
    setTimeInputs((prev) => ({ ...prev, [field]: input }))
  }

  const handleTimeBlur = (field: keyof Pick<CouponFormValues, "start_at" | "end_at">) => {
    const time = timeInputs[field]
    const [hoursStr, minutesStr] = time.split(":")

    let hours = parseInt(hoursStr, 10)
    let minutes = parseInt(minutesStr, 10)

    if (isNaN(hours) || hours < 0 || hours > 23) hours = 0
    if (isNaN(minutes) || minutes < 0 || minutes > 59) minutes = 0

    const formattedTime = `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`
    setTimeInputs((prev) => ({ ...prev, [field]: formattedTime }))

    const current = parseDateValue(formState[field]) ?? new Date()
    const updated = new Date(current)
    updated.setHours(hours, minutes, 0, 0)
    handleInputChange(field, updated.toISOString())
  }

  const handleImageReset = () => {
    if (imagePreview && imagePreview.startsWith("blob:")) {
      URL.revokeObjectURL(imagePreview)
    }
    setImageFile(null)
    setImagePreview(null)
    setRemoveImage(true)
  }

  const validateStepFrontend = (step: number): boolean => {
    const errors: Record<string, string> = {}

    if (step === 0) {
      if (!formState.code.trim()) errors.code = "Informe um código para o cupom."
      else if (formState.code.trim().length > 40) errors.code = "O código pode ter no máximo 40 caracteres."
      if (!formState.name.trim()) errors.name = "Informe um nome para o cupom."
      else if (formState.name.trim().length > 255) errors.name = "O nome pode ter no máximo 255 caracteres."
      if (formState.description && formState.description.length > 500) {
        errors.description = "A descrição pode ter no máximo 500 caracteres."
      }
    }

    if (step === 1) {
      if (!formState.discount_type) errors.discount_type = "Selecione o tipo de desconto."
      const value = Number(formState.discount_value)
      if (!formState.discount_value || Number.isNaN(value) || value <= 0) {
        errors.discount_value = "Informe um valor de desconto maior que zero."
      }
      if (formState.discount_type === "percentage" && value > 100) {
        errors.discount_value = "Percentual não pode ser maior que 100."
      }
    }

    if (step === 2) {
      if (formState.start_at && formState.end_at) {
        const start = parseDateValue(formState.start_at)
        const end = parseDateValue(formState.end_at)
        if (start && end && end < start) {
          errors.end_at = "A data final deve ser igual ou posterior à data inicial."
        }
      }
    }

    setFieldErrors(errors)
    return Object.keys(errors).length === 0
  }

  const buildStepPayload = (step: number) => {
    const base = {
      step,
      uuid: couponUuid || undefined,
    }

    if (step === 0) {
      return {
        ...base,
        code: formState.code,
        name: formState.name,
        description: formState.description || undefined,
      }
    }

    if (step === 1) {
      return {
        ...base,
        discount_type: formState.discount_type,
        discount_value: formState.discount_value,
        max_discount_amount: formState.max_discount_amount || undefined,
        minimum_order_amount: formState.minimum_order_amount || undefined,
        usage_limit: formState.usage_limit || undefined,
        usage_limit_per_client: formState.usage_limit_per_client || undefined,
      }
    }

    return {
      ...base,
      start_at: formState.start_at || undefined,
      end_at: formState.end_at || undefined,
      is_active: formState.is_active,
      is_featured: formState.is_featured,
    }
  }

  const clearBackendValidation = () => {
    setBackendErrors({})
    setErrorSnapshot(null)
  }

  const applyBackendErrors = (error: unknown) => {
    const extracted = extractValidationErrors(error)
    const { _general, ...fieldErrs } = extracted
    setBackendErrors(fieldErrs)
    setErrorSnapshot(getStepSnapshot(currentStep, formState))
    if (_general || Object.keys(fieldErrs).length > 0) {
      toast.error(_general || Object.values(fieldErrs)[0] || "Dados inválidos")
    }
  }

  const goNext = async () => {
    if (!canContinue) return
    if (!validateStepFrontend(currentStep)) return

    try {
      setValidatingStep(true)
      await apiClient.post(endpoints.marketing.coupons.validate, buildStepPayload(currentStep))
      clearBackendValidation()
      setFieldErrors({})
      setCompletedSteps((prev) => new Set(prev).add(currentStep))
      scheduleWizardStep(() => {
        setCurrentStep((s) => Math.min(s + 1, STEPS.length - 1))
      })
    } catch (error: unknown) {
      applyBackendErrors(error)
    } finally {
      setValidatingStep(false)
    }
  }

  const goBack = () => setCurrentStep((s) => Math.max(s - 1, 0))

  const goToStep = (step: number) => {
    if (hasPendingBackendErrors && !fieldsChangedSinceError) return
    if (step <= currentStep || completedSteps.has(step)) {
      setCurrentStep(step)
    }
  }

  const handleFinalSubmit = async () => {
    if (!validateStepFrontend(currentStep)) return

    try {
      setValidatingStep(true)
      await apiClient.post(endpoints.marketing.coupons.validate, buildStepPayload(currentStep))
      clearBackendValidation()
      setFieldErrors({})
      await onSubmit({
        values: {
          ...formState,
          optionals: Array.isArray(formState.optionals)
            ? formState.optionals
            : String(formState.optionals ?? "")
                .split(",")
                .map((item: string) => item.trim())
                .filter(Boolean),
        },
        imageFile,
        removeImage,
      })
    } catch (error: unknown) {
      applyBackendErrors(error)
    } finally {
      setValidatingStep(false)
    }
  }

  const getError = (field: string) => fieldErrors[field] || backendErrors[field]

  return (
    <div className="flex min-w-0 flex-col gap-6 overflow-x-hidden">
      <OrderStepper
        currentStep={currentStep}
        steps={STEPS}
        onStepClick={goToStep}
        completedSteps={completedSteps}
      />

      <div className="min-w-0 space-y-4 overflow-x-hidden">
        {currentStep === 0 && (
          <div className="space-y-4">
            <h3 className="text-base font-semibold">Detalhes Básicos</h3>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <Field
                label="Código *"
                htmlFor="code"
                error={getError("code")}
              >
                <Input
                  id="code"
                  value={formState.code}
                  onChange={(event) =>
                    handleInputChange("code", event.target.value.toUpperCase())
                  }
                  placeholder="PROMO2024"
                  maxLength={40}
                  className={cn("h-9 w-full uppercase tracking-wide", getError("code") && "border-destructive")}
                  disabled={busy || validatingStep}
                />
              </Field>
              <Field label="Nome *" htmlFor="name" error={getError("name")}>
                <Input
                  id="name"
                  value={formState.name}
                  onChange={(event) => handleInputChange("name", event.target.value)}
                  placeholder="Desconto de Primavera"
                  className={cn("h-9 w-full", getError("name") && "border-destructive")}
                  disabled={busy || validatingStep}
                />
              </Field>
            </div>

            <Field label="Descrição" htmlFor="description" error={getError("description")}>
              <Textarea
                id="description"
                value={formState.description}
                onChange={(event) => handleInputChange("description", event.target.value)}
                placeholder="Detalhe regras adicionais ou canais de divulgação"
                rows={3}
                className={cn("min-h-[80px] w-full resize-none", getError("description") && "border-destructive")}
                disabled={busy || validatingStep}
              />
            </Field>

            <div className="min-w-0 space-y-2">
              <Label>Imagem promocional</Label>
              <p className="text-xs text-muted-foreground">
                Exibida no cardápio no slider (380 x 220). Prefira JPG/PNG otimizados.
              </p>
              <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div className="h-[110px] w-full max-w-[190px] overflow-hidden rounded-lg border border-dashed border-border/70 bg-muted/30">
                  {imagePreview ? (
                    <img
                      src={imagePreview}
                      alt="Pré-visualização do cupom"
                      className="h-full w-full object-cover"
                    />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center text-xs text-muted-foreground">
                      Prévia 380x220
                    </div>
                  )}
                </div>
                <div className="flex w-full min-w-0 flex-col gap-2 sm:max-w-xs">
                  <Input
                    type="file"
                    accept="image/*"
                    className="h-9 w-full"
                    disabled={busy || validatingStep}
                    onChange={(event) => {
                      const file = event.target.files?.[0]
                      if (!file) {
                        if (imagePreview && imagePreview.startsWith("blob:")) {
                          URL.revokeObjectURL(imagePreview)
                        }
                        setImageFile(null)
                        setImagePreview(initialImageUrl ?? null)
                        setRemoveImage(false)
                        return
                      }

                      if (imagePreview && imagePreview.startsWith("blob:")) {
                        URL.revokeObjectURL(imagePreview)
                      }

                      setImageFile(file)
                      setImagePreview(URL.createObjectURL(file))
                      setRemoveImage(false)
                    }}
                  />
                  {(imageFile || imagePreview) && (
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      className="h-9 w-full sm:w-auto"
                      onClick={handleImageReset}
                      disabled={busy || validatingStep}
                    >
                      Remover imagem
                    </Button>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}

        {currentStep === 1 && (
          <div className="space-y-4">
            <h3 className="text-base font-semibold">Regras e Valores</h3>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="min-w-0 space-y-2">
                <Label>Tipo de desconto *</Label>
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                  <Button
                    type="button"
                    variant={formState.discount_type === "percentage" ? "default" : "outline"}
                    onClick={() => handleInputChange("discount_type", "percentage")}
                    size="sm"
                    className="h-9 w-full"
                    disabled={busy || validatingStep}
                  >
                    Percentual (%)
                  </Button>
                  <Button
                    type="button"
                    variant={formState.discount_type === "fixed" ? "default" : "outline"}
                    onClick={() => handleInputChange("discount_type", "fixed")}
                    size="sm"
                    className="h-9 w-full"
                    disabled={busy || validatingStep}
                  >
                    Valor fixo (R$)
                  </Button>
                </div>
                {getError("discount_type") && <FieldError message={getError("discount_type")} />}
              </div>

              <Field label="Valor *" htmlFor="discount_value" error={getError("discount_value")}>
                <Input
                  id="discount_value"
                  type="number"
                  min="0"
                  step="0.01"
                  value={formState.discount_value}
                  onChange={(event) => handleInputChange("discount_value", event.target.value)}
                  className={cn("h-9 w-full", getError("discount_value") && "border-destructive")}
                  disabled={busy || validatingStep}
                />
              </Field>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <Field label="Limite de desconto" htmlFor="max_discount_amount" error={getError("max_discount_amount")}>
                <Input
                  id="max_discount_amount"
                  type="number"
                  min="0"
                  step="0.01"
                  value={formState.max_discount_amount}
                  onChange={(event) => handleInputChange("max_discount_amount", event.target.value)}
                  placeholder="Opcional"
                  className="h-9 w-full"
                  disabled={busy || validatingStep}
                />
              </Field>
              <Field label="Pedido mínimo" htmlFor="minimum_order_amount" error={getError("minimum_order_amount")}>
                <Input
                  id="minimum_order_amount"
                  type="number"
                  min="0"
                  step="0.01"
                  value={formState.minimum_order_amount}
                  onChange={(event) => handleInputChange("minimum_order_amount", event.target.value)}
                  placeholder="Opcional"
                  className="h-9 w-full"
                  disabled={busy || validatingStep}
                />
              </Field>
              <Field label="Limite total de uso" htmlFor="usage_limit" error={getError("usage_limit")}>
                <Input
                  id="usage_limit"
                  type="number"
                  min="0"
                  step="1"
                  value={formState.usage_limit}
                  onChange={(event) => handleInputChange("usage_limit", event.target.value)}
                  placeholder="Ilimitado"
                  className="h-9 w-full"
                  disabled={busy || validatingStep}
                />
              </Field>
              <Field
                label="Limite por cliente"
                htmlFor="usage_limit_per_client"
                error={getError("usage_limit_per_client")}
              >
                <Input
                  id="usage_limit_per_client"
                  type="number"
                  min="0"
                  step="1"
                  value={formState.usage_limit_per_client}
                  onChange={(event) =>
                    handleInputChange("usage_limit_per_client", event.target.value)
                  }
                  placeholder="Ilimitado"
                  className="h-9 w-full"
                  disabled={busy || validatingStep}
                />
              </Field>
            </div>
          </div>
        )}

        {currentStep === 2 && (
          <div className="space-y-4">
            <h3 className="text-base font-semibold">Validade e Status</h3>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              {(["start_at", "end_at"] as const).map((field) => {
                const parsedDate = parseDateValue(formState[field])
                const timeValue = timeInputs[field]

                return (
                  <div key={field} className="min-w-0 space-y-2">
                    <Label className="flex items-center gap-2">
                      <CalendarIcon className="h-4 w-4" />
                      {field === "start_at" ? "Início" : "Fim"}
                    </Label>
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                      <Popover modal={false}>
                        <PopoverTrigger asChild>
                          <Button
                            type="button"
                            variant="outline"
                            className={cn(
                              "h-9 w-full justify-start text-left font-normal sm:flex-1",
                              !parsedDate && "text-muted-foreground",
                              getError(field) && "border-destructive"
                            )}
                            disabled={busy || validatingStep}
                          >
                            {parsedDate ? format(parsedDate, "dd/MM/yyyy") : "Selecionar data"}
                          </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0" align="start">
                          <Calendar
                            mode="single"
                            selected={parsedDate}
                            onSelect={(date) => handleDateSelection(field, date ?? undefined)}
                            initialFocus
                          />
                        </PopoverContent>
                      </Popover>
                      <Input
                        type="text"
                        placeholder="HH:MM"
                        value={timeValue}
                        onChange={(event) => handleTimeChange(field, event.target.value)}
                        onBlur={() => handleTimeBlur(field)}
                        maxLength={5}
                        disabled={busy || validatingStep}
                        className="h-9 w-full sm:w-[120px] sm:shrink-0"
                      />
                    </div>
                    {getError(field) && <FieldError message={getError(field)} />}
                  </div>
                )
              })}
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="flex items-center justify-between gap-3 rounded-lg border border-border/60 bg-muted/20 px-3 py-2.5">
                <div className="min-w-0">
                  <p className="font-medium">Cupom ativo</p>
                  <p className="text-xs text-muted-foreground">
                    Clientes poderão utilizar imediatamente.
                  </p>
                </div>
                <Switch
                  checked={formState.is_active}
                  onCheckedChange={(checked) => handleInputChange("is_active", checked)}
                  disabled={busy || validatingStep}
                />
              </div>
              <div className="flex items-center justify-between gap-3 rounded-lg border border-border/60 bg-muted/20 px-3 py-2.5">
                <div className="min-w-0">
                  <p className="font-medium">Destacar cupom</p>
                  <p className="text-xs text-muted-foreground">
                    Evidencie na vitrine pública e nas comunicações.
                  </p>
                </div>
                <Switch
                  checked={formState.is_featured}
                  onCheckedChange={(checked) => handleInputChange("is_featured", checked)}
                  disabled={busy || validatingStep}
                />
              </div>
            </div>
          </div>
        )}
      </div>

      <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
        {currentStep > 0 ? (
          <Button
            type="button"
            variant="outline"
            className="h-9 w-full sm:justify-self-start"
            onClick={goBack}
            disabled={busy || validatingStep}
          >
            <ChevronLeft className="mr-1 h-4 w-4" />
            Voltar
          </Button>
        ) : onCancel ? (
          <Button
            type="button"
            variant="outline"
            className="h-9 w-full sm:justify-self-start"
            onClick={onCancel}
            disabled={busy || validatingStep}
          >
            Cancelar
          </Button>
        ) : (
          <div />
        )}

        {isLastStep ? (
          <Button
            type="button"
            className="h-9 w-full sm:justify-self-end"
            onClick={() => void handleFinalSubmit()}
            disabled={!canContinue}
          >
            {(busy || validatingStep) && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {mode === "edit"
              ? busy
                ? "Salvando..."
                : "Salvar alterações"
              : busy
                ? "Criando..."
                : "Criar cupom"}
          </Button>
        ) : (
          <Button
            type="button"
            className="h-9 w-full sm:justify-self-end"
            onClick={() => void goNext()}
            disabled={!canContinue}
          >
            {validatingStep ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Validando...
              </>
            ) : (
              <>
                Continuar
                <ChevronRight className="ml-1 h-4 w-4" />
              </>
            )}
          </Button>
        )}
      </div>
    </div>
  )
}

const STEP_SNAPSHOT_FIELDS: (keyof CouponFormValues)[][] = [
  ["code", "name", "description"],
  [
    "discount_type",
    "discount_value",
    "max_discount_amount",
    "minimum_order_amount",
    "usage_limit",
    "usage_limit_per_client",
  ],
  ["start_at", "end_at", "is_active", "is_featured"],
]

function Field({
  label,
  htmlFor,
  error,
  children,
}: {
  label: string
  htmlFor: string
  error?: string
  children: ReactNode
}) {
  return (
    <div className="min-w-0 space-y-2">
      <Label htmlFor={htmlFor}>{label}</Label>
      {children}
      {error && <FieldError message={error} />}
    </div>
  )
}

function FieldError({ message }: { message?: string }) {
  if (!message) return null
  return (
    <p className="flex items-center gap-1 text-sm text-destructive">
      <AlertCircle className="h-3 w-3 shrink-0" />
      {message}
    </p>
  )
}

function normalizeValues(values?: Partial<CouponFormValues>): CouponFormValues {
  if (!values) {
    return { ...defaultValues }
  }

  const convert = (value: string | number | null | undefined, fallback = ""): string => {
    if (value === null || value === undefined) return fallback
    return String(value)
  }

  return {
    code: values.code ?? defaultValues.code,
    name: values.name ?? defaultValues.name,
    description: values.description ?? defaultValues.description,
    discount_type: values.discount_type ?? defaultValues.discount_type,
    discount_value: convert(values.discount_value ?? defaultValues.discount_value, defaultValues.discount_value),
    max_discount_amount: convert(values.max_discount_amount ?? defaultValues.max_discount_amount),
    minimum_order_amount: convert(values.minimum_order_amount ?? defaultValues.minimum_order_amount),
    usage_limit: convert(values.usage_limit ?? defaultValues.usage_limit),
    usage_limit_per_client: convert(values.usage_limit_per_client ?? defaultValues.usage_limit_per_client),
    start_at: values.start_at ?? defaultValues.start_at,
    end_at: values.end_at ?? defaultValues.end_at,
    is_active: values.is_active ?? defaultValues.is_active,
    is_featured: values.is_featured ?? defaultValues.is_featured,
    optionals: Array.isArray(values.optionals) ? values.optionals : defaultValues.optionals,
  }
}
