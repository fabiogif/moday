"use client"

import { useState, useEffect } from "react"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { cn } from "@/lib/utils"
import { AlertCircle } from "lucide-react"

interface PaymentAmountInputProps {
  value: number | null
  onChange: (value: number | null) => void
  orderTotal: number
  label?: string
  placeholder?: string
  required?: boolean
  showChange?: boolean
  className?: string
  error?: string
}

export function PaymentAmountInput({
  value,
  onChange,
  orderTotal,
  label = "Valor recebido",
  placeholder = "0,00",
  required = false,
  showChange = true,
  className,
  error,
}: PaymentAmountInputProps) {
  const [displayValue, setDisplayValue] = useState("")
  const [localError, setLocalError] = useState("")

  useEffect(() => {
    if (value === null) {
      setDisplayValue("")
    } else {
      setDisplayValue(
        value.toLocaleString("pt-BR", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
      )
    }
  }, [value])

  const formatCurrency = (inputValue: string) => {
    // Remove tudo exceto números
    const numbers = inputValue.replace(/\D/g, "")
    if (!numbers) return ""
    
    // Converte para número e formata
    const amount = parseFloat(numbers) / 100
    return amount.toLocaleString("pt-BR", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  }

  const parseAmount = (formattedValue: string): number | null => {
    const cleaned = formattedValue.replace(/\./g, "").replace(",", ".")
    const parsed = parseFloat(cleaned)
    return isNaN(parsed) ? null : parsed
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const formatted = formatCurrency(e.target.value)
    setDisplayValue(formatted)
    setLocalError("")
    
    const parsed = parseAmount(formatted)
    onChange(parsed)
    
    // Validação
    if (parsed !== null) {
      if (parsed <= 0) {
        setLocalError("O valor deve ser maior que zero")
      } else if (parsed < orderTotal) {
        setLocalError(
          `O valor recebido deve ser maior ou igual ao total (${orderTotal.toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          })})`
        )
      }
    } else if (required && !formatted) {
      setLocalError("Este campo é obrigatório")
    }
  }

  const changeAmount = value && value >= orderTotal ? value - orderTotal : 0
  const hasError = error || localError

  return (
    <div className={cn("space-y-2", className)}>
      {label && (
        <Label htmlFor="payment-amount" className={required ? "after:content-['*'] after:ml-0.5 after:text-destructive" : ""}>
          {label}
        </Label>
      )}
      <Input
        id="payment-amount"
        type="text"
        placeholder={placeholder}
        value={displayValue}
        onChange={handleChange}
        className={cn(
          "h-12 text-lg",
          hasError && "border-destructive focus-visible:ring-destructive"
        )}
        autoFocus
      />
      {hasError && (
        <div className="flex items-center gap-1 text-sm text-destructive">
          <AlertCircle className="h-4 w-4" />
          <span>{error || localError}</span>
        </div>
      )}
      {showChange && value && value >= orderTotal && changeAmount > 0 && (
        <div className="rounded-lg bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 p-3">
          <div className="flex items-center justify-between">
            <span className="text-sm font-medium text-green-900 dark:text-green-100">
              Troco:
            </span>
            <span className="text-lg font-bold text-green-700 dark:text-green-300">
              {changeAmount.toLocaleString("pt-BR", {
                style: "currency",
                currency: "BRL",
              })}
            </span>
          </div>
        </div>
      )}
    </div>
  )
}

