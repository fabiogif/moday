"use client"

import { Textarea } from "@/components/ui/textarea"
import { cn } from "@/lib/utils"

interface OrderNotesProps {
  value: string
  onChange: (value: string) => void
  placeholder?: string
  label?: string
  className?: string
}

export function OrderNotes({
  value,
  onChange,
  placeholder = "Instruções adicionais",
  label = "Observações do pedido",
  className,
}: OrderNotesProps) {
  return (
    <div className={cn("space-y-1.5", className)}>
      <Textarea
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="min-h-[36px] rounded-md px-2 py-1.5 text-xs resize-none"
      />
    </div>
  )
}
