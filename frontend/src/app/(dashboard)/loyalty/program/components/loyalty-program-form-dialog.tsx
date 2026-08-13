'use client'

import { useState, useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Gift, AlertCircle, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import apiClient, { endpoints } from '@/lib/api-client'
import { extractValidationErrors } from '@/lib/error-formatter'

const programSchema = z.object({
  name: z.string().min(3, 'Nome deve ter no mínimo 3 caracteres'),
  description: z.string().optional(),
  is_active: z.boolean(),
  points_per_currency: z.number().min(0.01, 'Mínimo 0.01 pontos por real'),
  min_purchase_amount: z.number().min(0).optional(),
  max_points_per_purchase: z.number().min(0).optional(),
  points_expiry_days: z.number().min(1).optional(),
})

type ProgramFormValues = z.infer<typeof programSchema>

interface LoyaltyProgramFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  program?: any
  onSuccess: () => void
}

export function LoyaltyProgramFormDialog({
  open,
  onOpenChange,
  program,
  onSuccess,
}: LoyaltyProgramFormDialogProps) {
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})
  const [isSubmitting, setIsSubmitting] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setValue,
    watch,
  } = useForm<ProgramFormValues>({
    resolver: zodResolver(programSchema),
    defaultValues: {
      is_active: true,
      points_per_currency: 1.0,
    },
  })

  useEffect(() => {
    if (program) {
      setValue('name', program.name)
      setValue('description', program.description || '')
      setValue('is_active', program.is_active)
      setValue('points_per_currency', program.points_per_currency)
      setValue('min_purchase_amount', program.min_purchase_amount || 0)
      setValue('max_points_per_purchase', program.max_points_per_purchase || 0)
      setValue('points_expiry_days', program.points_expiry_days || 0)
      setBackendErrors({})
    } else {
      reset({
        name: 'Programa VIP',
        description: '',
        is_active: true,
        points_per_currency: 1.0,
        min_purchase_amount: 0,
        max_points_per_purchase: 0,
        points_expiry_days: 365,
      })
      setBackendErrors({})
    }
  }, [program, setValue, reset, open])

  const handleFormSubmit = async (data: ProgramFormValues) => {
    setIsSubmitting(true)
    setBackendErrors({})

    try {
      const submitData = {
        ...data,
        min_purchase_amount: data.min_purchase_amount || null,
        max_points_per_purchase: data.max_points_per_purchase || null,
        points_expiry_days: data.points_expiry_days || null,
      }

      if (program) {
        await apiClient.put(endpoints.loyalty.updateProgram(program.uuid), submitData)
        toast.success('Programa atualizado com sucesso')
      } else {
        await apiClient.post(endpoints.loyalty.program, submitData)
        toast.success('Programa criado com sucesso')
      }

      reset()
      onSuccess()
    } catch (error: any) {
      const validationErrors = extractValidationErrors(error)
      setBackendErrors(validationErrors)
      
      if (validationErrors._general) {
        toast.error(validationErrors._general)
      } else {
        toast.error(error.message || 'Erro ao salvar programa')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  const hasError = (field: string) => errors[field as keyof ProgramFormValues] || backendErrors[field]
  const getErrorMessage = (field: string) => {
    const frontendError = errors[field as keyof ProgramFormValues]?.message
    const backendError = backendErrors[field]
    return frontendError || backendError
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Gift className="h-5 w-5" />
            {program ? 'Editar Programa' : 'Criar Programa de Fidelidade'}
          </DialogTitle>
          <DialogDescription>
            Configure as regras de acúmulo e resgate de pontos
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-4">
          {backendErrors._general && (
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>{backendErrors._general}</AlertDescription>
            </Alert>
          )}

          <div className="space-y-2">
            <Label htmlFor="name">
              Nome do Programa <span className="text-destructive">*</span>
            </Label>
            <Input
              id="name"
              {...register('name')}
              placeholder="Ex: Programa VIP"
              className={hasError('name') ? 'border-destructive' : ''}
            />
            {hasError('name') && (
              <p className="text-sm text-destructive">{getErrorMessage('name')}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Descrição</Label>
            <Textarea
              id="description"
              {...register('description')}
              placeholder="Descreva o programa..."
              rows={3}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="points_per_currency">
                Pontos por R$ <span className="text-destructive">*</span>
              </Label>
              <Input
                id="points_per_currency"
                type="number"
                step="0.01"
                {...register('points_per_currency', { valueAsNumber: true })}
                className={hasError('points_per_currency') ? 'border-destructive' : ''}
              />
              {hasError('points_per_currency') && (
                <p className="text-sm text-destructive">{getErrorMessage('points_per_currency')}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="min_purchase_amount">Compra Mínima (R$)</Label>
              <Input
                id="min_purchase_amount"
                type="number"
                step="0.01"
                {...register('min_purchase_amount', { valueAsNumber: true })}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="max_points_per_purchase">Máx. Pontos por Compra</Label>
              <Input
                id="max_points_per_purchase"
                type="number"
                {...register('max_points_per_purchase', { valueAsNumber: true })}
                placeholder="0 = ilimitado"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="points_expiry_days">Validade (dias)</Label>
              <Input
                id="points_expiry_days"
                type="number"
                {...register('points_expiry_days', { valueAsNumber: true })}
                placeholder="0 = sem expiração"
              />
            </div>
          </div>

          <div className="flex items-center space-x-2 rounded-lg border p-4">
            <Switch
              id="is_active"
              checked={watch('is_active')}
              onCheckedChange={(checked) => setValue('is_active', checked)}
            />
            <Label htmlFor="is_active" className="cursor-pointer">
              Programa ativo
            </Label>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isSubmitting}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {program ? 'Atualizar' : 'Criar Programa'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

