'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Plus, Trash2, DollarSign } from 'lucide-react'
import { Badge } from '@/components/ui/badge'

export interface ProductOptional {
  id: string
  name: string
  price: number
}

interface ProductOptionalsProps {
  optionals: ProductOptional[]
  onChange: (optionals: ProductOptional[]) => void
  disabled?: boolean
}

export function ProductOptionals({ optionals, onChange, disabled }: ProductOptionalsProps) {
  const [newOptionalName, setNewOptionalName] = useState('')
  const [newOptionalPrice, setNewOptionalPrice] = useState('')

  const addOptional = () => {
    if (!newOptionalName || !newOptionalPrice) {
      return
    }

    const price = parseFloat(newOptionalPrice)
    if (isNaN(price) || price < 0) {
      return
    }

    const newOptional: ProductOptional = {
      id: Date.now().toString(),
      name: newOptionalName.trim(),
      price: price,
    }

    onChange([...optionals, newOptional])
    setNewOptionalName('')
    setNewOptionalPrice('')
  }

  const removeOptional = (id: string) => {
    onChange(optionals.filter((v) => v.id !== id))
  }

  const updateOptional = (id: string, field: 'name' | 'price', value: string) => {
    onChange(
      optionals.map((v) => {
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
          <DollarSign className="h-5 w-5" />
          Opcionais do Produto
        </CardTitle>
        <CardDescription>
          Adicione opcionais como tamanhos, sabores ou complementos que alteram o preço
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Lista de Opcionais Existentes */}
        {optionals.length > 0 && (
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <Label className="text-sm font-medium">Opcionais Cadastrados</Label>
              <Badge variant="secondary">{optionals.length}</Badge>
            </div>
            {optionals.map((optional) => (
              <Card key={optional.id} className="p-3 bg-muted/50">
                <div className="flex items-center gap-2">
                  <div className="flex-1 grid grid-cols-2 gap-2">
                    <div>
                      <Label className="text-xs text-muted-foreground">Nome</Label>
                      <Input
                        value={optional.name}
                        onChange={(e) => updateOptional(optional.id, 'name', e.target.value)}
                        placeholder="Ex: Grande, Borda Recheada"
                        disabled={disabled}
                        className="h-8"
                      />
                    </div>
                    <div>
                      <Label className="text-xs text-muted-foreground">Preço Adicional</Label>
                      <div className="relative">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                          R$
                        </span>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          value={optional.price}
                          onChange={(e) => updateOptional(optional.id, 'price', e.target.value)}
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
                    onClick={() => removeOptional(optional.id)}
                    disabled={disabled}
                    className="h-8 w-8 text-destructive hover:text-destructive"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
                <p className="text-xs text-muted-foreground mt-1">
                  Valor adicional: {formatCurrency(optional.price)}
                </p>
              </Card>
            ))}
          </div>
        )}

        {/* Adicionar Novo Opcional */}
        {!disabled && (
          <div className="border-t pt-4">
            <Label className="text-sm font-medium mb-3 block">Adicionar Novo Opcional</Label>
            <div className="flex gap-2">
              <div className="flex-1">
                <Input
                  value={newOptionalName}
                  onChange={(e) => setNewOptionalName(e.target.value)}
                  placeholder="Nome do opcional (ex: Médio, Grande)"
                  onKeyPress={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault()
                      addOptional()
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
                    min="0"
                    value={newOptionalPrice}
                    onChange={(e) => setNewOptionalPrice(e.target.value)}
                    placeholder="0,00"
                    className="pl-10"
                    onKeyPress={(e) => {
                      if (e.key === 'Enter') {
                        e.preventDefault()
                        addOptional()
                      }
                    }}
                  />
                </div>
              </div>
              <Button
                type="button"
                onClick={addOptional}
                size="icon"
                disabled={!newOptionalName || !newOptionalPrice}
              >
                <Plus className="h-4 w-4" />
              </Button>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              Pressione Enter ou clique no + para adicionar
            </p>
          </div>
        )}

        {optionals.length === 0 && (
          <div className="text-center py-8 text-sm text-muted-foreground">
            <DollarSign className="h-12 w-12 mx-auto mb-2 opacity-20" />
            <p>Nenhum opcional cadastrado</p>
            <p className="text-xs mt-1">
              Adicione opcionais para oferecer opções diferentes do produto
            </p>
          </div>
        )}
      </CardContent>
    </Card>
  )
}

