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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Award, AlertCircle, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import apiClient, { endpoints } from '@/lib/api-client'
import { extractValidationErrors } from '@/lib/error-formatter'

const rewardSchema = z.object({
  name: z.string().min(3, 'Nome deve ter no mínimo 3 caracteres'),
  description: z.string().optional(),
  type: z.enum(['discount_percentage', 'discount_fixed', 'free_product', 'free_shipping', 'custom']),
  points_required: z.number().min(1, 'Mínimo 1 ponto'),
  discount_value: z.number().min(0).optional(),
  stock_quantity: z.number().min(0).optional(),
  max_redemptions_per_user: z.number().min(1).optional(),
  validity_days: z.number().min(1).optional(),
  is_active: z.boolean(),
})

type RewardFormValues = z.infer<typeof rewardSchema>

interface LoyaltyRewardFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  reward?: any
  onSuccess: () => void
}

export function LoyaltyRewardFormDialog({
  open,
  onOpenChange,
  reward,
  onSuccess,
}: LoyaltyRewardFormDialogProps) {
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})
  const [isSubmitting, setIsSubmitting] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setValue,
    watch,
  } = useForm<RewardFormValues>({
    resolver: zodResolver(rewardSchema),
    defaultValues: {
      is_active: true,
      type: 'discount_fixed',
    },
  })

  const selectedType = watch('type')

  useEffect(() => {
    if (reward) {
      setValue('name', reward.name)
      setValue('description', reward.description || '')
      setValue('type', reward.type)
      setValue('points_required', reward.points_required)
      setValue('discount_value', reward.discount_value || 0)
      setValue('stock_quantity', reward.stock_quantity || 0)
      setValue('max_redemptions_per_user', reward.max_redemptions_per_user || 0)
      setValue('validity_days', reward.validity_days || 0)
      setValue('is_active', reward.is_active)
      setBackendErrors({})
    } else {
      reset({
        name: '',
        description: '',
        type: 'discount_fixed',
        points_required: 100,
        discount_value: 10,
        stock_quantity: 0,
        max_redemptions_per_user: 0,
        validity_days: 30,
        is_active: true,
      })
      setBackendErrors({})
    }
  }, [reward, setValue, reset, open])

  const handleFormSubmit = async (data: RewardFormValues) => {
    setIsSubmitting(true)
    setBackendErrors({})

    try {
      const submitData = {
        ...data,
        discount_value: ['discount_percentage', 'discount_fixed'].includes(data.type) ? data.discount_value : null,
        stock_quantity: data.stock_quantity || null,
        max_redemptions_per_user: data.max_redemptions_per_user || null,
        validity_days: data.validity_days || null,
      }

      if (reward) {
        await apiClient.put(endpoints.loyalty.updateReward(reward.uuid), submitData)
        toast.success('Recompensa atualizada com sucesso')
      } else {
        await apiClient.post(endpoints.loyalty.createReward, submitData)
        toast.success('Recompensa criada com sucesso')
      }

      reset()
      onSuccess()
    } catch (error: any) {
      const validationErrors = extractValidationErrors(error)
      setBackendErrors(validationErrors)
      
      if (validationErrors._general) {
        toast.error(validationErrors._general)
      } else {
        toast.error(error.message || 'Erro ao salvar recompensa')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  const hasError = (field: string) => errors[field as keyof RewardFormValues] || backendErrors[field]
  const getErrorMessage = (field: string) => {
    const frontendError = errors[field as keyof RewardFormValues]?.message
    const backendError = backendErrors[field]
    return frontendError || backendError
  }

  const showDiscountField = ['discount_percentage', 'discount_fixed'].includes(selectedType)

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Award className="h-5 w-5" />
            {reward ? 'Editar Recompensa' : 'Nova Recompensa'}
          </DialogTitle>
          <DialogDescription>
            Configure uma recompensa que os clientes podem resgatar
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
              Nome da Recompensa <span className="text-destructive">*</span>
            </Label>
            <Input
              id="name"
              {...register('name')}
              placeholder="Ex: R$ 10 de desconto"
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
              placeholder="Descreva a recompensa..."
              rows={2}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="type">
                Tipo de Recompensa <span className="text-destructive">*</span>
              </Label>
              <Select
                value={selectedType}
                onValueChange={(value: any) => setValue('type', value)}
              >
                <SelectTrigger className={hasError('type') ? 'border-destructive' : ''}>
                  <SelectValue placeholder="Selecione o tipo" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="discount_fixed">Desconto Fixo (R$)</SelectItem>
                  <SelectItem value="discount_percentage">Desconto Percentual (%)</SelectItem>
                  <SelectItem value="free_product">Produto Grátis</SelectItem>
                  <SelectItem value="free_shipping">Frete Grátis</SelectItem>
                  <SelectItem value="custom">Personalizado</SelectItem>
                </SelectContent>
              </Select>
              {hasError('type') && (
                <p className="text-sm text-destructive">{getErrorMessage('type')}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="points_required">
                Pontos Necessários <span className="text-destructive">*</span>
              </Label>
              <Input
                id="points_required"
                type="number"
                {...register('points_required', { valueAsNumber: true })}
                className={hasError('points_required') ? 'border-destructive' : ''}
              />
              {hasError('points_required') && (
                <p className="text-sm text-destructive">{getErrorMessage('points_required')}</p>
              )}
            </div>
          </div>

          {showDiscountField && (
            <div className="space-y-2">
              <Label htmlFor="discount_value">
                Valor do Desconto <span className="text-destructive">*</span>
              </Label>
              <Input
                id="discount_value"
                type="number"
                step="0.01"
                {...register('discount_value', { valueAsNumber: true })}
                placeholder={selectedType === 'discount_percentage' ? '15' : '10.00'}
                className={hasError('discount_value') ? 'border-destructive' : ''}
              />
              <p className="text-xs text-muted-foreground">
                {selectedType === 'discount_percentage' ? 'Percentual de desconto (ex: 15 para 15%)' : 'Valor em reais (ex: 10.00)'}
              </p>
              {hasError('discount_value') && (
                <p className="text-sm text-destructive">{getErrorMessage('discount_value')}</p>
              )}
            </div>
          )}

          <div className="grid grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label htmlFor="stock_quantity">Estoque</Label>
              <Input
                id="stock_quantity"
                type="number"
                {...register('stock_quantity', { valueAsNumber: true })}
                placeholder="0 = ilimitado"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="max_redemptions_per_user">Limite por Cliente</Label>
              <Input
                id="max_redemptions_per_user"
                type="number"
                {...register('max_redemptions_per_user', { valueAsNumber: true })}
                placeholder="0 = ilimitado"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="validity_days">Validade (dias)</Label>
              <Input
                id="validity_days"
                type="number"
                {...register('validity_days', { valueAsNumber: true })}
                placeholder="30"
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
              Recompensa ativa
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
              {reward ? 'Atualizar' : 'Criar Recompensa'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

