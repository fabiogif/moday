'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Plus, Trash2, ShoppingBag } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { ProductOptional } from '@/types/product-variations'

interface ProductOptionalsManagerProps {
  optionals: ProductOptional[]
  onChange: (optionals: ProductOptional[]) => void
  disabled?: boolean
}

/**
 * Componente para gerenciar OPCIONAIS de produto
 * Opcionais = Seleção MÚLTIPLA com QUANTIDADE (ex: Bacon 2x, Queijo 1x)
 * Cliente pode escolher VÁRIOS opcionais e REPETIR cada um
 */
export function ProductOptionalsManager({ optionals, onChange, disabled }: ProductOptionalsManagerProps) {
  const [newName, setNewName] = useState('')
  const [newPrice, setNewPrice] = useState('')

  const addOptional = () => {
    if (!newName || !newPrice) {
      return
    }

    const price = parseFloat(newPrice)
    if (isNaN(price) || price < 0) {
      return
    }

    const newOptional: ProductOptional = {
      id: Date.now().toString(),
      name: newName.trim(),
      price: price,
    }

    onChange([...optionals, newOptional])
    setNewName('')
    setNewPrice('')
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
          <ShoppingBag className="h-5 w-5" />
          Opcionais do Produto
        </CardTitle>
        <CardDescription>
          Adicionais com quantidade (ex: Bacon, Queijo). Cliente pode escolher VÁRIOS e REPETIR cada um.
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
            {optionals.map((optional, index) => (
              <Card key={optional.id || `optional-${index}`} className="p-3 bg-muted/50">
                <div className="flex items-center gap-2">
                  <div className="flex-1 grid grid-cols-2 gap-2">
                    <div>
                      <Label className="text-xs text-muted-foreground">Nome do Opcional</Label>
                      <Input
                        value={optional.name || ''}
                        onChange={(e) => updateOptional(optional.id, 'name', e.target.value)}
                        placeholder="Ex: Bacon, Queijo, Cebola"
                        disabled={disabled}
                        className="h-8"
                      />
                    </div>
                    <div>
                      <Label className="text-xs text-muted-foreground">Preço Unitário</Label>
                      <div className="relative">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                          R$
                        </span>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          value={optional.price ?? 0}
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
                  {formatCurrency(optional.price)} por unidade • Cliente pode adicionar múltiplas vezes
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
                  value={newName}
                  onChange={(e) => setNewName(e.target.value)}
                  placeholder="Nome do opcional (ex: Bacon Extra, Queijo)"
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
                    value={newPrice}
                    onChange={(e) => setNewPrice(e.target.value)}
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
                disabled={!newName || !newPrice}
              >
                <Plus className="h-4 w-4" />
              </Button>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              Preço unitário - cliente poderá escolher quantidade
            </p>
          </div>
        )}

        {optionals.length === 0 && (
          <div className="text-center py-8 text-sm text-muted-foreground">
            <ShoppingBag className="h-12 w-12 mx-auto mb-2 opacity-20" />
            <p>Nenhum opcional cadastrado</p>
            <p className="text-xs mt-1">
              Opcionais são adicionais como Bacon, Queijo que o cliente pode escolher a quantidade
            </p>
          </div>
        )}
      </CardContent>
    </Card>
  )
}

