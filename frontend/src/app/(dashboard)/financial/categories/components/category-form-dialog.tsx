'use client'

import { useState, useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { FinancialCategory, FinancialCategoryFormData } from '@/hooks/use-financial-categories'
import { FileText, Palette, AlertCircle, TrendingUp, TrendingDown } from 'lucide-react'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { extractValidationErrors } from '@/lib/error-formatter'

interface CategoryFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  category?: FinancialCategory | null
  onSubmit: (data: FinancialCategoryFormData) => Promise<void>
  isLoading?: boolean
  defaultType?: 'receita' | 'despesa'
}

const CATEGORY_COLORS = [
  { label: 'Azul', value: '#3b82f6' },
  { label: 'Verde', value: '#10b981' },
  { label: 'Vermelho', value: '#ef4444' },
  { label: 'Amarelo', value: '#f59e0b' },
  { label: 'Roxo', value: '#8b5cf6' },
  { label: 'Rosa', value: '#ec4899' },
  { label: 'Laranja', value: '#f97316' },
  { label: 'Ciano', value: '#06b6d4' },
  { label: 'Índigo', value: '#6366f1' },
  { label: 'Cinza', value: '#6b7280' },
]

export function CategoryFormDialog({
  open,
  onOpenChange,
  category,
  onSubmit,
  isLoading,
  defaultType,
}: CategoryFormDialogProps) {
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})
  const [selectedColor, setSelectedColor] = useState(CATEGORY_COLORS[0].value)

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setValue,
    watch,
    clearErrors,
  } = useForm<FinancialCategoryFormData>({
    mode: 'onBlur',
  })

  const categoryType = watch('type')

  useEffect(() => {
    if (category) {
      setValue('name', category.name)
      setValue('type', category.type)
      setValue('description', category.description || '')
      setSelectedColor(category.color)
      setBackendErrors({})
    } else {
      reset()
      setValue('type', defaultType || 'despesa')
      setSelectedColor(CATEGORY_COLORS[0].value)
      setBackendErrors({})
    }
  }, [category, defaultType, setValue, reset, open])

  const handleFormSubmit = async (data: any) => {
    try {
      setBackendErrors({})
      
      const formData: FinancialCategoryFormData = {
        ...data,
        color: selectedColor,
      }

      await onSubmit(formData)
      reset()
      setBackendErrors({})
    } catch (error: any) {
      const validationErrors = extractValidationErrors(error)
      setBackendErrors(validationErrors)
      
      if (validationErrors._general) {
        toast.error(validationErrors._general)
      }
    }
  }

  const hasError = (field: string) => errors[field as keyof FinancialCategoryFormData] || backendErrors[field]
  const getErrorMessage = (field: string) => {
    const frontendError = errors[field as keyof FinancialCategoryFormData]?.message
    const backendError = backendErrors[field]
    return frontendError || backendError
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="text-2xl font-bold flex items-center gap-2">
            <FileText className="h-6 w-6" />
            {category ? 'Editar Categoria' : 'Nova Categoria'}
          </DialogTitle>
          <DialogDescription>
            Crie categorias para organizar suas receitas e despesas.
          </DialogDescription>
        </DialogHeader>

        {backendErrors._general && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{backendErrors._general}</AlertDescription>
          </Alert>
        )}

        <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name">Nome da Categoria *</Label>
            <Input
              id="name"
              {...register('name', { required: 'O nome é obrigatório' })}
              className={cn(hasError('name') && 'border-destructive')}
              placeholder="Ex: Aluguel, Vendas, Salários..."
            />
            {hasError('name') && (
              <p className="text-sm text-destructive">{getErrorMessage('name')}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="type">Tipo *</Label>
            <Select
              value={categoryType}
              onValueChange={(value) => setValue('type', value as any)}
            >
              <SelectTrigger className={cn(hasError('type') && 'border-destructive')}>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="receita">
                  <div className="flex items-center gap-2">
                    <TrendingUp className="h-4 w-4 text-green-600" />
                    Receita
                  </div>
                </SelectItem>
                <SelectItem value="despesa">
                  <div className="flex items-center gap-2">
                    <TrendingDown className="h-4 w-4 text-red-600" />
                    Despesa
                  </div>
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label>
              <Palette className="inline h-4 w-4 mr-1" />
              Cor
            </Label>
            <div className="grid grid-cols-5 gap-2">
              {CATEGORY_COLORS.map((color) => (
                <button
                  key={color.value}
                  type="button"
                  onClick={() => setSelectedColor(color.value)}
                  className={cn(
                    'h-10 rounded-md border-2 transition-all hover:scale-110',
                    selectedColor === color.value
                      ? 'border-primary ring-2 ring-primary ring-offset-2 scale-110'
                      : 'border-transparent'
                  )}
                  style={{ backgroundColor: color.value }}
                  title={color.label}
                />
              ))}
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Descrição</Label>
            <Textarea
              id="description"
              {...register('description')}
              placeholder="Descrição opcional da categoria..."
              rows={3}
            />
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isLoading}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading ? 'Salvando...' : category ? 'Atualizar' : 'Criar Categoria'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

