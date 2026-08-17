'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Plus, Trash2, Layers } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { ProductVariation } from '@/types/product-variations'

interface ProductVariationsManagerProps {
  variations: ProductVariation[]
  onChange: (variations: ProductVariation[]) => void
  disabled?: boolean
}

/**
 * Componente para gerenciar VARIAÇÕES de produto
 * Variações = Seleção ÚNICA (ex: Pequeno, Médio, Grande)
 * Cliente escolhe apenas UMA variação por produto
 */
export function ProductVariationsManager({ variations, onChange, disabled }: ProductVariationsManagerProps) {
  const [newName, setNewName] = useState('')
  const [newPrice, setNewPrice] = useState('')

  const addVariation = () => {
    if (!newName || newPrice === '') {
      return
    }

    const price = parseFloat(newPrice)
    if (isNaN(price)) {
      return
    }

    const newVariation: ProductVariation = {
      id: Date.now().toString(),
      name: newName.trim(),
      price: price,
    }

    onChange([...variations, newVariation])
    setNewName('')
    setNewPrice('')
  }

  const removeVariation = (id: string) => {
    onChange(variations.filter((v) => v.id !== id))
  }

  const updateVariation = (id: string, field: 'name' | 'price', value: string) => {
    onChange(
      variations.map((v) => {
        if (v.id === id) {
          if (field === 'price') {
            const price = parseFloat(value)
            return { ...v, [field]: isNaN(price) ? 0 : price }
          }
          return { ...v, [field]: value }
        }
        return v
      })
    )
  }

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value)
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Layers className="h-5 w-5" />
          Variações do Produto
        </CardTitle>
        <CardDescription>
          Opções de seleção única (ex: Pequeno, Médio, Grande). Cliente escolhe apenas UMA variação.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Lista de Variações Existentes */}
        {variations.length > 0 && (
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <Label className="text-sm font-medium">Variações Cadastradas</Label>
              <Badge variant="secondary">{variations.length}</Badge>
            </div>
            {variations.map((variation, index) => (
              <Card key={variation.id || `variation-${index}`} className="p-3 bg-muted/50">
                <div className="flex items-center gap-2">
                  <div className="flex-1 grid grid-cols-2 gap-2">
                    <div>
                      <Label className="text-xs text-muted-foreground">Nome da Variação</Label>
                      <Input
                        value={variation.name || ''}
                        onChange={(e) => updateVariation(variation.id, 'name', e.target.value)}
                        placeholder="Ex: Pequeno, Médio, Grande"
                        disabled={disabled}
                        className="h-8"
                      />
                    </div>
                    <div>
                      <Label className="text-xs text-muted-foreground">Ajuste de Preço</Label>
                      <div className="relative">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                          R$
                        </span>
                        <Input
                          type="number"
                          step="0.01"
                          value={variation.price ?? 0}
                          onChange={(e) => updateVariation(variation.id, 'price', e.target.value)}
                          placeholder="0,00"
                          disabled={disabled}
                          className="h-8 pl-10"
                        />
                      </div>
                    </div>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeVariation(variation.id)}
                    disabled={disabled}
                    className="h-8 w-8 text-destructive hover:text-destructive"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
                <p className="text-xs text-muted-foreground mt-1">
                  {variation.price > 0 && `Adiciona ${formatCurrency(variation.price)}`}
                  {variation.price === 0 && 'Sem alteração de preço'}
                  {variation.price < 0 && `Desconto de ${formatCurrency(Math.abs(variation.price))}`}
                </p>
              </Card>
            ))}
          </div>
        )}

        {/* Adicionar Nova Variação */}
        {!disabled && (
          <div className="border-t pt-4">
            <Label className="text-sm font-medium mb-3 block">Adicionar Nova Variação</Label>
            <div className="flex gap-2">
              <div className="flex-1">
                <Input
                  value={newName}
                  onChange={(e) => setNewName(e.target.value)}
                  placeholder="Nome (ex: Pequeno, Médio, Grande)"
                  onKeyPress={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault()
                      addVariation()
                    }
                  }}
                />
              </div>
              <div className="w-32">
                <div className="relative">
                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                    R$
                  </span>
                  <Input
                    type="number"
                    step="0.01"
                    value={newPrice}
                    onChange={(e) => setNewPrice(e.target.value)}
                    placeholder="0,00"
                    className="pl-10"
                    onKeyPress={(e) => {
                      if (e.key === 'Enter') {
                        e.preventDefault()
                        addVariation()
                      }
                    }}
                  />
                </div>
              </div>
              <Button
                type="button"
                onClick={addVariation}
                size="icon"
                disabled={!newName || newPrice === ''}
              >
                <Plus className="h-4 w-4" />
              </Button>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              Use valores negativos para descontos (ex: -5.00 para Pequeno)
            </p>
          </div>
        )}

        {variations.length === 0 && (
          <div className="text-center py-8 text-sm text-muted-foreground">
            <Layers className="h-12 w-12 mx-auto mb-2 opacity-20" />
            <p>Nenhuma variação cadastrada</p>
            <p className="text-xs mt-1">
              Variações são opções exclusivas como tamanhos (P/M/G)
            </p>
          </div>
        )}
      </CardContent>
    </Card>
  )
}

