"use client"

import { Button } from "@/components/ui/button"
import { Delete } from "lucide-react"
import { cn } from "@/lib/utils"

interface NumericKeypadProps {
  value: string
  onChange: (value: string) => void
  onConfirm?: () => void
  placeholder?: string
  maxLength?: number
  className?: string
  disabled?: boolean
}

export function NumericKeypad({
  value,
  onChange,
  onConfirm,
  placeholder = "0",
  maxLength = 10,
  className,
  disabled = false,
}: NumericKeypadProps) {
  const handleNumberClick = (num: string) => {
    if (disabled) return
    if (value.length >= maxLength) return

    if (value === "0") {
      onChange(num)
    } else {
      onChange(value + num)
    }
  }

  const handleBackspace = () => {
    if (disabled) return
    if (value.length > 1) {
      onChange(value.slice(0, -1))
    } else {
      onChange("0")
    }
  }

  const handleClear = () => {
    if (disabled) return
    onChange("0")
  }

  const handleDecimal = () => {
    if (disabled) return
    if (!value.includes(".")) {
      onChange(value + ".")
    }
  }

  const displayValue = value || "0"

  return (
    <div className={cn("space-y-3", className)}>
      {/* Display */}
      <div className="rounded-lg border-2 border-primary/20 bg-muted/50 p-4 text-right">
        <div className="text-3xl font-bold tabular-nums">
          {displayValue === "0" ? (
            <span className="text-muted-foreground">{placeholder}</span>
          ) : (
            displayValue
          )}
        </div>
      </div>

      {/* Keypad */}
      <div className="grid grid-cols-3 gap-2">
        {/* Row 1 */}
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("7")}
          disabled={disabled}
        >
          7
        </Button>
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("8")}
          disabled={disabled}
        >
          8
        </Button>
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("9")}
          disabled={disabled}
        >
          9
        </Button>

        {/* Row 2 */}
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("4")}
          disabled={disabled}
        >
          4
        </Button>
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("5")}
          disabled={disabled}
        >
          5
        </Button>
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("6")}
          disabled={disabled}
        >
          6
        </Button>

        {/* Row 3 */}
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("1")}
          disabled={disabled}
        >
          1
        </Button>
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("2")}
          disabled={disabled}
        >
          2
        </Button>
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("3")}
          disabled={disabled}
        >
          3
        </Button>

        {/* Row 4 */}
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={() => handleNumberClick("0")}
          disabled={disabled}
        >
          0
        </Button>
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={handleDecimal}
          disabled={disabled}
        >
          ,
        </Button>
        <Button
          variant="outline"
          size="lg"
          className="h-14 text-lg font-semibold"
          onClick={handleBackspace}
          disabled={disabled}
        >
          <span className="text-lg font-semibold">&larr;</span>
        </Button>

        {/* Row 5 - Actions */}
        <Button
          variant="destructive"
          size="lg"
          className="h-14 col-span-2"
          onClick={handleClear}
          disabled={disabled}
        >
          <Delete className="h-5 w-5 mr-2" />
          Limpar
        </Button>
        {onConfirm && (
          <Button
            variant="default"
            size="lg"
            className="h-14"
            onClick={onConfirm}
            disabled={disabled || displayValue === "0"}
          >
            OK
          </Button>
        )}
      </div>
    </div>
  )
}

