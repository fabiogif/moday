'use client'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Plus, Trash2 } from 'lucide-react'
import { FEE_TYPE_OPTIONS, FeeType as DistanceFeeType } from './fee-type'

export type { DistanceFeeType }

export interface DistanceBand {
  km_from: number
  km_to: number
  fee_type: DistanceFeeType
  fee_value: number | null
  estimated_time_minutes: number
}

interface DistanceBandsEditorProps {
  bands: DistanceBand[]
  onChange: (bands: DistanceBand[]) => void
}

const emptyBand = (previousKmTo = 0): DistanceBand => ({
  km_from: previousKmTo,
  km_to: previousKmTo + 3,
  fee_type: 'fixed',
  fee_value: 0,
  estimated_time_minutes: 40,
})

export function DistanceBandsEditor({ bands, onChange }: DistanceBandsEditorProps) {
  const updateBand = (index: number, patch: Partial<DistanceBand>) => {
    const updated = bands.map((band, i) => (i === index ? { ...band, ...patch } : band))
    onChange(updated)
  }

  const addBand = () => {
    const lastKmTo = bands.length > 0 ? bands[bands.length - 1].km_to : 0
    onChange([...bands, emptyBand(lastKmTo)])
  }

  const removeBand = (index: number) => {
    onChange(bands.filter((_, i) => i !== index))
  }

  return (
    <div className="space-y-3">
      {bands.map((band, index) => (
        <div key={index} className="grid grid-cols-12 gap-2 items-end rounded-lg border p-3">
          <div className="col-span-2 space-y-1">
            <Label className="text-xs">De (km)</Label>
            <Input
              type="number"
              min="0"
              step="0.1"
              value={band.km_from}
              onChange={(e) => updateBand(index, { km_from: Number(e.target.value) || 0 })}
            />
          </div>
          <div className="col-span-2 space-y-1">
            <Label className="text-xs">Até (km)</Label>
            <Input
              type="number"
              min="0"
              step="0.1"
              value={band.km_to}
              onChange={(e) => updateBand(index, { km_to: Number(e.target.value) || 0 })}
            />
          </div>
          <div className="col-span-3 space-y-1">
            <Label className="text-xs">Taxa</Label>
            <Select
              value={band.fee_type}
              onValueChange={(value: DistanceFeeType) => updateBand(index, { fee_type: value })}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {FEE_TYPE_OPTIONS.map((option) => (
                  <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          {band.fee_type === 'fixed' ? (
            <div className="col-span-2 space-y-1">
              <Label className="text-xs">Valor (R$)</Label>
              <Input
                type="number"
                min="0"
                step="0.01"
                value={band.fee_value ?? 0}
                onChange={(e) => updateBand(index, { fee_value: Number(e.target.value) || 0 })}
              />
            </div>
          ) : (
            <div className="col-span-2" />
          )}
          <div className="col-span-2 space-y-1">
            <Label className="text-xs">Tempo (min)</Label>
            <Input
              type="number"
              min="0"
              value={band.estimated_time_minutes}
              onChange={(e) => updateBand(index, { estimated_time_minutes: Number(e.target.value) || 0 })}
            />
          </div>
          <div className="col-span-1 flex justify-end">
            <Button type="button" variant="ghost" size="icon" onClick={() => removeBand(index)}>
              <Trash2 className="h-4 w-4 text-destructive" />
            </Button>
          </div>
        </div>
      ))}

      <Button type="button" variant="outline" size="sm" onClick={addBand}>
        <Plus className="h-4 w-4 mr-2" />
        Adicionar Faixa
      </Button>
    </div>
  )
}
