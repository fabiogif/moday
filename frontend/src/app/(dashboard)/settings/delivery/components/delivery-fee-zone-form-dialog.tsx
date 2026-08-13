'use client'

import { useState, useEffect } from 'react'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { DeliveryFeeZone, DeliveryFeeZoneFormData, useDeliveryFeeZoneMutation } from '@/hooks/use-delivery-fee-zones'
import { useViaCEP } from '@/hooks/use-viacep'
import { StateCitySelect } from '@/components/location/state-city-select'
import { applyCepToStateHandlers } from '@/lib/apply-cep-to-form'
import { endpoints } from '@/lib/api-client'
import { maskZipCode } from '@/lib/masks'
import { extractValidationErrors } from '@/lib/error-formatter'
import { MapPin, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { FEE_TYPE_OPTIONS } from './fee-type'

interface DeliveryFeeZoneFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  zone?: DeliveryFeeZone | null
  onSuccess: () => void
}

export function DeliveryFeeZoneFormDialog({ open, onOpenChange, zone, onSuccess }: DeliveryFeeZoneFormDialogProps) {
  const { mutate, loading } = useDeliveryFeeZoneMutation()
  const { loading: loadingCep, searchCEP } = useViaCEP()

  const [zipCode, setZipCode] = useState('')
  const [state, setState] = useState('')
  const [city, setCity] = useState('')
  const [neighborhood, setNeighborhood] = useState('')
  const [feeType, setFeeType] = useState<DeliveryFeeZoneFormData['fee_type']>('fixed')
  const [feeValue, setFeeValue] = useState('')
  const [estimatedTime, setEstimatedTime] = useState('40')
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})

  useEffect(() => {
    if (zone) {
      setZipCode('')
      setState('')
      setCity(zone.city)
      setNeighborhood(zone.neighborhood)
      setFeeType(zone.fee_type)
      setFeeValue(zone.fee_value !== null ? String(zone.fee_value) : '')
      setEstimatedTime(String(zone.estimated_time_minutes))
    } else {
      setZipCode('')
      setState('')
      setCity('')
      setNeighborhood('')
      setFeeType('fixed')
      setFeeValue('')
      setEstimatedTime('40')
    }
    setBackendErrors({})
  }, [zone, open])

  const handleCepBlur = async () => {
    const digits = zipCode.replace(/\D/g, '')
    if (digits.length !== 8) return

    const address = await searchCEP(digits)
    if (address) {
      applyCepToStateHandlers(address, {
        setState,
        setCity,
        setNeighborhood,
      })
    }
  }

  const handleSubmit = async () => {
    setBackendErrors({})

    if (!city.trim() || !neighborhood.trim()) {
      setBackendErrors({
        city: !city.trim() ? 'A cidade é obrigatória' : '',
        neighborhood: !neighborhood.trim() ? 'O bairro é obrigatório' : '',
      })
      return
    }

    if (feeType === 'fixed' && !feeValue) {
      setBackendErrors({ fee_value: 'Informe o valor da taxa' })
      return
    }

    const payload: DeliveryFeeZoneFormData = {
      city: city.trim(),
      neighborhood: neighborhood.trim(),
      fee_type: feeType,
      fee_value: feeType === 'fixed' ? Number(feeValue) : null,
      estimated_time_minutes: Number(estimatedTime) || 0,
      is_active: true,
    }

    try {
      if (zone) {
        await mutate(endpoints.deliveryFeeZones.update(zone.uuid), 'PUT', payload)
        toast.success('Bairro atualizado com sucesso')
      } else {
        await mutate(endpoints.deliveryFeeZones.create, 'POST', payload)
        toast.success('Bairro adicionado com sucesso')
      }
      onSuccess()
    } catch (error: any) {
      const validationErrors = extractValidationErrors(error)
      setBackendErrors(validationErrors)
      if (validationErrors._general) {
        toast.error(validationErrors._general)
      } else {
        toast.error(error.message || 'Erro ao salvar bairro')
      }
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <MapPin className="h-5 w-5" />
            {zone ? 'Editar Bairro' : 'Adicionar Bairro'}
          </DialogTitle>
          <DialogDescription>
            Digite o CEP para preencher cidade e bairro automaticamente, ou preencha manualmente.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="zone_zip_code">CEP (opcional, para autopreenchimento)</Label>
            <div className="relative">
              <Input
                id="zone_zip_code"
                value={zipCode}
                onChange={(e) => setZipCode(maskZipCode(e.target.value))}
                onBlur={handleCepBlur}
                placeholder="00000-000"
                maxLength={9}
              />
              {loadingCep && <Loader2 className="absolute right-3 top-2.5 h-4 w-4 animate-spin text-muted-foreground" />}
            </div>
          </div>

          <StateCitySelect
            stateValue={state}
            cityValue={city}
            onStateChange={(value) => {
              setState(value)
              setCity('')
            }}
            onCityChange={setCity}
            cityError={backendErrors.city}
            required
          />

          <div className="space-y-2">
            <Label htmlFor="zone_neighborhood">
              Bairro <span className="text-destructive">*</span>
            </Label>
            <Input
              id="zone_neighborhood"
              value={neighborhood}
              onChange={(e) => setNeighborhood(e.target.value)}
              placeholder="Centro"
              className={backendErrors.neighborhood ? 'border-destructive' : ''}
            />
            {backendErrors.neighborhood && <p className="text-sm text-destructive">{backendErrors.neighborhood}</p>}
          </div>

          <div className="space-y-2">
            <Label htmlFor="zone_fee_type">Taxa</Label>
            <Select value={feeType} onValueChange={(value: any) => setFeeType(value)}>
              <SelectTrigger id="zone_fee_type">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {FEE_TYPE_OPTIONS.map((option) => (
                  <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid grid-cols-2 gap-4">
            {feeType === 'fixed' && (
              <div className="space-y-2">
                <Label htmlFor="zone_fee_value">Valor da Taxa (R$)</Label>
                <Input
                  id="zone_fee_value"
                  type="number"
                  min="0"
                  step="0.01"
                  value={feeValue}
                  onChange={(e) => setFeeValue(e.target.value)}
                  placeholder="10.00"
                  className={backendErrors.fee_value ? 'border-destructive' : ''}
                />
                {backendErrors.fee_value && <p className="text-sm text-destructive">{backendErrors.fee_value}</p>}
              </div>
            )}

            <div className="space-y-2">
              <Label htmlFor="zone_estimated_time">Tempo Médio (min)</Label>
              <Input
                id="zone_estimated_time"
                type="number"
                min="0"
                value={estimatedTime}
                onChange={(e) => setEstimatedTime(e.target.value)}
                placeholder="40"
              />
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
            Cancelar
          </Button>
          <Button type="button" onClick={handleSubmit} disabled={loading}>
            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {zone ? 'Atualizar' : 'Adicionar'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
