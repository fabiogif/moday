"use client"

import { useState, useEffect } from "react"
import * as React from "react"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { Plus, Loader2, User, MapPin, ChevronLeft, ChevronRight } from "lucide-react"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"
import { useInputMask } from "@/hooks/use-input-mask"
import { validateCPF, validateEmail, validatePhone, maskCPF, maskPhone, maskZipCode } from "@/lib/masks"
import { useViaCEP } from "@/hooks/use-viacep"
import { StateCityFormFields } from "@/components/location/state-city-form-fields"
import { applyCepToForm } from "@/lib/apply-cep-to-form"
import { useBackendValidation } from "@/hooks/use-backend-validation"
import { showErrorToast } from "@/components/ui/error-toast"
import { OrderStepper } from "@/components/order-stepper"
import { apiClient, endpoints } from "@/lib/api-client"
import { extractValidationErrors } from "@/lib/error-formatter"
import { scheduleWizardStep } from "@/app/(dashboard)/financial/components/account-form-shared"

const clientFormSchema = z.object({
  name: z.string().min(3, {
    message: "O nome completo deve ter pelo menos 3 caracteres.",
  }).max(255, {
    message: "O nome completo não pode ter mais de 255 caracteres.",
  }),
  cpf: z.string()
    .min(1, { message: "CPF é obrigatório." })
    .refine((value) => validateCPF(value), {
      message: "CPF inválido. Verifique os dígitos.",
    }),
  email: z.string()
    .min(1, { message: "Email é obrigatório." })
    .refine((value) => validateEmail(value), {
      message: "Email inválido. Use o formato: exemplo@email.com",
    }),
  phone: z.string()
    .min(1, { message: "Telefone é obrigatório." })
    .refine((value) => validatePhone(value), {
      message: "Telefone inválido. Use (00) 00000-0000",
    }),
  address: z.string().optional(),
  city: z.string().optional(),
  state: z.string().max(2, {
    message: "Estado deve ter 2 caracteres (UF).",
  }).optional(),
  zip_code: z.string().optional(),
  neighborhood: z.string().optional(),
  number: z.string().optional(),
  complement: z.string().optional(),
  isActive: z.boolean(),
})

interface ClientFormValues {
  name: string
  cpf: string
  email: string
  phone: string
  address?: string
  city?: string
  state?: string
  zip_code?: string
  neighborhood?: string
  number?: string
  complement?: string
  isActive: boolean
}

const STEPS = [
  { label: "Dados Básicos", icon: User },
  { label: "Endereço e Status", icon: MapPin },
]

// Campos validados ao avançar de cada passo (os demais são opcionais)
const STEP_FIELDS: (keyof ClientFormValues)[][] = [
  ["name", "cpf", "email", "phone"],
  [],
]

// Passo em que cada campo aparece — usado para levar o usuário até o erro retornado pelo backend
const FIELD_STEP: Partial<Record<keyof ClientFormValues, number>> = {
  name: 0,
  cpf: 0,
  email: 0,
  phone: 0,
  address: 1,
  number: 1,
  complement: 1,
  neighborhood: 1,
  state: 1,
  city: 1,
  zip_code: 1,
  isActive: 1,
}

interface ClientFormDialogProps {
  onAddClient: (clientData: ClientFormValues) => void | Promise<void>
  onEditClient?: (id: number, clientData: ClientFormValues) => void | Promise<void>
  editingClient?: ClientFormValues & { id: number } | null
  open: boolean
  onOpenChange: (open: boolean) => void
  hideTrigger?: boolean // Se true, não mostra o botão "Novo Cliente"
}

export function ClientFormDialog({ 
  onAddClient, 
  onEditClient, 
  editingClient, 
  open, 
  onOpenChange,
  hideTrigger = false 
}: ClientFormDialogProps) {
  const isEditing = !!editingClient
  const { loading: loadingCEP, searchCEP } = useViaCEP();
  const [submitting, setSubmitting] = React.useState(false);
  const [validatingStep, setValidatingStep] = React.useState(false);
  const [currentStep, setCurrentStep] = React.useState(0);
  const [completedSteps, setCompletedSteps] = React.useState<Set<number>>(new Set());
  const [backendErrors, setBackendErrors] = React.useState<Record<string, string>>({});
  const [errorSnapshot, setErrorSnapshot] = React.useState<string | null>(null);

  const form = useForm<ClientFormValues>({
    resolver: zodResolver(clientFormSchema),
    defaultValues: {
      name: "",
      cpf: "",
      email: "",
      phone: "",
      address: "",
      city: "",
      state: "",
      zip_code: "",
      neighborhood: "",
      number: "",
      complement: "",
      isActive: true,
    },
  })

  const { handleBackendErrors } = useBackendValidation(form.setError)

  const watchedValues = form.watch()

  const getStepSnapshot = React.useCallback(
    (step: number, values: ClientFormValues) => {
      const fields =
        step === 0
          ? (["name", "cpf", "email", "phone"] as const)
          : (["address", "number", "complement", "neighborhood", "city", "state", "zip_code", "isActive"] as const)
      return JSON.stringify(
        Object.fromEntries(fields.map((field) => [field, values[field] ?? ""]))
      )
    },
    []
  )

  const stepBackendErrorFields = React.useMemo(() => {
    const fields =
      currentStep === 0
        ? ["name", "cpf", "email", "phone"]
        : ["address", "number", "complement", "neighborhood", "city", "state", "zip_code", "isActive", "is_active"]
    return Object.keys(backendErrors).filter((field) => fields.includes(field))
  }, [backendErrors, currentStep])

  const hasPendingBackendErrors = stepBackendErrorFields.length > 0
  const fieldsChangedSinceError =
    errorSnapshot !== null &&
    getStepSnapshot(currentStep, watchedValues) !== errorSnapshot

  const canContinue =
    !submitting &&
    !validatingStep &&
    (!hasPendingBackendErrors || fieldsChangedSinceError)

  const applyBackendErrors = (error: unknown) => {
    const extracted = extractValidationErrors(error)
    const { _general, ...fieldErrors } = extracted
    setBackendErrors(fieldErrors)
    setErrorSnapshot(getStepSnapshot(currentStep, form.getValues()))

    Object.entries(fieldErrors).forEach(([field, message]) => {
      form.setError(field as keyof ClientFormValues, {
        type: "server",
        message,
      })
    })

    handleBackendErrors(error)

    if (_general) {
      showErrorToast(error, "Erro de validação")
    } else if (Object.keys(fieldErrors).length > 0) {
      showErrorToast(error, "Erro de validação")
    }

    const firstErrorField = Object.keys(fieldErrors)[0] as keyof ClientFormValues | undefined
    const stepWithError = firstErrorField ? FIELD_STEP[firstErrorField] : undefined
    if (stepWithError !== undefined) {
      setCurrentStep(stepWithError)
    }
  }

  const clearBackendValidation = () => {
    setBackendErrors({})
    setErrorSnapshot(null)
    const serverFields = Object.keys(form.formState.errors) as (keyof ClientFormValues)[]
    serverFields.forEach((field) => {
      if (form.formState.errors[field]?.type === "server") {
        form.clearErrors(field)
      }
    })
  }

  const buildStepPayload = (step: number) => {
    const values = form.getValues()
    const base = {
      step,
      client_id: editingClient?.id,
    }

    if (step === 0) {
      return {
        ...base,
        name: values.name,
        cpf: values.cpf,
        email: values.email,
        phone: values.phone,
      }
    }

    return {
      ...base,
      address: values.address,
      number: values.number,
      complement: values.complement,
      neighborhood: values.neighborhood,
      city: values.city,
      state: values.state,
      zip_code: values.zip_code,
      isActive: values.isActive,
      is_active: values.isActive,
    }
  }

  const goNext = async () => {
    if (!canContinue) return

    const fields = STEP_FIELDS[currentStep]
    const valid = fields.length === 0 || (await form.trigger(fields))
    if (!valid) return

    try {
      setValidatingStep(true)
      await apiClient.post(endpoints.clients.validate, buildStepPayload(currentStep))
      clearBackendValidation()
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

  // Se um erro (frontend ou backend) surgir num campo de um passo anterior, leva o usuário até lá
  React.useEffect(() => {
    const erroredFields = Object.keys(form.formState.errors) as (keyof ClientFormValues)[]
    if (erroredFields.length === 0) return
    const steps = erroredFields
      .map((f) => FIELD_STEP[f])
      .filter((s): s is number => s !== undefined)
    if (steps.length === 0) return
    const earliest = Math.min(...steps)
    setCurrentStep((current) => (earliest < current ? earliest : current))
  }, [form.formState.errors])

  // Função para buscar endereço pelo CEP
  const handleSearchCEP = async (cep: string) => {
    const cleanCEP = cep.replace(/\D/g, '');
    
    if (cleanCEP.length !== 8) {
      return;
    }
    
    try {
      const address = await searchCEP(cep);
      
      if (address) {
        applyCepToForm(form.setValue, address, {
          address: 'address',
          neighborhood: 'neighborhood',
          state: 'state',
          city: 'city',
        })
      }
    } catch {
      // Erro já é tratado pelo useViaCEP com toast
    }
  }

  // Preencher o formulário quando editingClient mudar (ou o modal reabrir)
  React.useEffect(() => {
    setCurrentStep(0);
    setCompletedSteps(new Set());
    setBackendErrors({});
    setErrorSnapshot(null);

    if (editingClient) {
      form.reset({
        name: editingClient.name || "",
        cpf: editingClient.cpf || "",
        email: editingClient.email || "",
        phone: editingClient.phone || "",
        address: editingClient.address || "",
        city: editingClient.city || "",
        state: editingClient.state || "",
        zip_code: editingClient.zip_code || "",
        neighborhood: editingClient.neighborhood || "",
        number: editingClient.number || "",
        complement: editingClient.complement || "",
        isActive: editingClient.isActive ?? true,
      })
    } else {
      form.reset({
        name: "",
        cpf: "",
        email: "",
        phone: "",
        address: "",
        city: "",
        state: "",
        zip_code: "",
        neighborhood: "",
        number: "",
        complement: "",
        isActive: true,
      })
    }
  }, [editingClient, form, open])

  const onSubmit = async (data: ClientFormValues) => {
    try {
      setSubmitting(true)
      
      if (isEditing && editingClient && onEditClient) {
        await onEditClient(editingClient.id, data)
      } else {
        await onAddClient(data)
      }
      
      // Só fechar e limpar se não houver erro
      form.reset()
      onOpenChange(false)
      
    } catch (error: unknown) {
      applyBackendErrors(error)
    } finally {
      setSubmitting(false)
    }
  }

  const isLastStep = currentStep === STEPS.length - 1
  const fieldHasError = (field: keyof ClientFormValues) =>
    Boolean(form.formState.errors[field] || backendErrors[field])

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      {!isEditing && !hideTrigger && (
        <DialogTrigger asChild>
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            Novo Cliente
          </Button>
        </DialogTrigger>
      )}
      <DialogContent className="flex max-h-[90vh] w-[calc(100%-2rem)] max-w-3xl flex-col gap-6 overflow-x-hidden overflow-y-auto p-6 sm:max-w-3xl">
        <DialogHeader className="shrink-0 space-y-1 text-left">
          <DialogTitle>{isEditing ? 'Editar Cliente' : 'Novo Cliente'}</DialogTitle>
          <DialogDescription>
            {isEditing 
              ? 'Edite os dados do cliente abaixo.' 
              : 'Adicione um novo cliente ao sistema. Preencha os dados abaixo.'
            }
          </DialogDescription>
        </DialogHeader>

        <div className="shrink-0 overflow-x-hidden">
          <OrderStepper
            currentStep={currentStep}
            steps={STEPS}
            onStepClick={goToStep}
            completedSteps={completedSteps}
          />
        </div>

        <Form {...form}>
          <form
            onSubmit={(e) => {
              if (!isLastStep) {
                e.preventDefault()
                return
              }
              void form.handleSubmit(onSubmit)(e)
            }}
            className="flex min-h-0 flex-1 flex-col gap-6 overflow-x-hidden"
          >
            <div className="min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden pr-1">
            {/* Passo 1: Dados básicos */}
            {currentStep === 0 && (
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem className="md:col-span-2">
                    <FormLabel>Nome Completo *</FormLabel>
                    <FormControl>
                      <Input
                        placeholder="João Silva"
                        className={fieldHasError("name") ? "border-destructive" : undefined}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                    {backendErrors.name && !form.formState.errors.name && (
                      <p className="text-sm font-medium text-destructive">{backendErrors.name}</p>
                    )}
                  </FormItem>
                )}
              />
              
              <FormField
                control={form.control}
                name="cpf"
                render={({ field }) => {
                  const handleCPFChange = useInputMask('cpf', field.onChange);
                  
                  return (
                    <FormItem className="md:col-span-2">
                      <FormLabel>CPF *</FormLabel>
                      <FormControl>
                        <Input 
                          placeholder="000.000.000-00" 
                          className={fieldHasError("cpf") ? "border-destructive" : undefined}
                          value={field.value}
                          onChange={handleCPFChange}
                          onBlur={field.onBlur}
                          name={field.name}
                          maxLength={14}
                        />
                      </FormControl>
                      <FormMessage />
                      {backendErrors.cpf && !form.formState.errors.cpf && (
                        <p className="text-sm font-medium text-destructive">{backendErrors.cpf}</p>
                      )}
                    </FormItem>
                  );
                }}
              />
              
              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Email *</FormLabel>
                    <FormControl>
                      <Input
                        type="email"
                        placeholder="joao@example.com"
                        className={fieldHasError("email") ? "border-destructive" : undefined}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                    {backendErrors.email && !form.formState.errors.email && (
                      <p className="text-sm font-medium text-destructive">{backendErrors.email}</p>
                    )}
                  </FormItem>
                )}
              />
              
              <FormField
                control={form.control}
                name="phone"
                render={({ field }) => {
                  const handlePhoneChange = useInputMask('phone', field.onChange);
                  
                  return (
                    <FormItem>
                      <FormLabel>Telefone *</FormLabel>
                      <FormControl>
                        <Input 
                          placeholder="(11) 99999-9999" 
                          className={fieldHasError("phone") ? "border-destructive" : undefined}
                          value={field.value}
                          onChange={handlePhoneChange}
                          onBlur={field.onBlur}
                          name={field.name}
                          maxLength={15}
                        />
                      </FormControl>
                      <FormMessage />
                      {backendErrors.phone && !form.formState.errors.phone && (
                        <p className="text-sm font-medium text-destructive">{backendErrors.phone}</p>
                      )}
                    </FormItem>
                  );
                }}
              />
            </div>
            )}

            {/* Passo 2: Endereço e Status */}
            {currentStep === 1 && (
            <>
            <div className="space-y-4">
              <h3 className="text-lg font-medium">Endereço (Opcional)</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="address"
                  render={({ field }) => (
                    <FormItem className="md:col-span-2">
                      <FormLabel>Logradouro</FormLabel>
                      <FormControl>
                        <Input placeholder="Rua das Flores, Av. Paulista" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="number"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Número</FormLabel>
                      <FormControl>
                        <Input placeholder="123" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="complement"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Complemento</FormLabel>
                      <FormControl>
                        <Input placeholder="Apto 101" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="neighborhood"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Bairro</FormLabel>
                      <FormControl>
                        <Input placeholder="Centro" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Estado e Cidade */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <StateCityFormFields
                    control={form.control}
                    stateFieldName="state"
                    cityFieldName="city"
                    stateLabel="Estado"
                    cityLabel="Cidade"
                    gridCols="equal"
                  />
                </div>

                <FormField
                  control={form.control}
                  name="zip_code"
                  render={({ field }) => {
                    const handleZipCodeChange = useInputMask('zipCode', field.onChange);
                    
                    return (
                      <FormItem>
                        <FormLabel>CEP</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <Input 
                              placeholder="01234-567" 
                              value={field.value}
                              onChange={handleZipCodeChange}
                              onBlur={(e) => {
                                field.onBlur();
                                handleSearchCEP(e.target.value);
                              }}
                              name={field.name}
                              maxLength={9}
                              disabled={loadingCEP}
                            />
                            {loadingCEP && (
                              <div className="absolute right-2 top-1/2 -translate-y-1/2">
                                <div className="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                              </div>
                            )}
                          </div>
                        </FormControl>
                        <p className="text-xs text-muted-foreground mt-1">
                          {loadingCEP ? 'Buscando endereço...' : 'Digite o CEP para preencher automaticamente'}
                        </p>
                        <FormMessage />
                      </FormItem>
                    );
                  }}
                />
              </div>
            </div>

            {/* Status */}
            <FormField
              control={form.control}
              name="isActive"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">Cliente Ativo</FormLabel>
                    <div className="text-sm text-muted-foreground">
                      Este cliente poderá fazer pedidos
                    </div>
                  </div>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                </FormItem>
              )}
            />
            </>
            )}
            </div>

            <DialogFooter className="shrink-0 grid grid-cols-1 gap-2 sm:grid-cols-2">
              {currentStep > 0 ? (
                <Button
                  type="button"
                  variant="outline"
                  className="h-9 w-full sm:justify-self-start"
                  onClick={goBack}
                  disabled={submitting || validatingStep}
                >
                  <ChevronLeft className="h-4 w-4 mr-1" />
                  Voltar
                </Button>
              ) : (
                <Button
                  type="button"
                  variant="outline"
                  className="h-9 w-full sm:justify-self-start"
                  onClick={() => onOpenChange(false)}
                  disabled={submitting || validatingStep}
                >
                  Cancelar
                </Button>
              )}

              {currentStep < STEPS.length - 1 ? (
                <Button
                  type="button"
                  className="h-9 w-full sm:justify-self-end"
                  onClick={(e) => {
                    e.preventDefault()
                    e.stopPropagation()
                    void goNext()
                  }}
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
                      <ChevronRight className="h-4 w-4 ml-1" />
                    </>
                  )}
                </Button>
              ) : (
                <Button
                  type="submit"
                  className="h-9 w-full sm:justify-self-end"
                  disabled={submitting || validatingStep || (hasPendingBackendErrors && !fieldsChangedSinceError)}
                >
                  {submitting ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      {isEditing ? 'Salvando...' : 'Criando...'}
                    </>
                  ) : (
                    isEditing ? 'Salvar Alterações' : 'Criar Cliente'
                  )}
                </Button>
              )}
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
