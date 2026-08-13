"use client"

import { useState } from "react"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Separator } from "@/components/ui/separator"

interface FiscalDocumentProps {
  documentType?: "nfce" | "nfe" | null
  cpfCnpj?: string
  onDocumentTypeChange?: (type: "nfce" | "nfe" | null) => void
  onCpfCnpjChange?: (value: string) => void
  onEmitNow?: () => void
  onEmitLater?: () => void
  className?: string
}

export function FiscalDocument({
  documentType = null,
  cpfCnpj = "",
  onDocumentTypeChange,
  onCpfCnpjChange,
  onEmitNow,
  onEmitLater,
  className,
}: FiscalDocumentProps) {
  const [localType, setLocalType] = useState<"nfce" | "nfe" | null>(documentType)
  const [localCpfCnpj, setLocalCpfCnpj] = useState(cpfCnpj)

  const formatCpfCnpj = (value: string) => {
    const numbers = value.replace(/\D/g, "")
    if (numbers.length <= 11) {
      // CPF
      return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4")
    } else {
      // CNPJ
      return numbers.replace(
        /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
        "$1.$2.$3/$4-$5"
      )
    }
  }

  const handleCpfCnpjChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const formatted = formatCpfCnpj(e.target.value)
    setLocalCpfCnpj(formatted)
    onCpfCnpjChange?.(formatted)
  }

  const handleTypeChange = (value: string) => {
    const type = value === "nfce" || value === "nfe" ? value : null
    setLocalType(type)
    onDocumentTypeChange?.(type)
  }

  const handleEmitNow = () => {
    if (localType && localCpfCnpj) {
      onEmitNow?.()
    }
  }

  const handleEmitLater = () => {
    onEmitLater?.()
  }

  return (
    <div className={`border-2 border-gray-200 rounded-lg p-4 space-y-3 ${className}`}>
      <h4 className="font-semibold text-sm">DOCUMENTO FISCAL:</h4>

      {/* Tipo de Documento */}
      <div>
        <Label className="text-sm text-gray-600">Tipo:</Label>
        <Select
          value={localType || ""}
          onValueChange={handleTypeChange}
        >
          <SelectTrigger>
            <SelectValue placeholder="Selecione o tipo" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="nfce">NFC-e</SelectItem>
            <SelectItem value="nfe">NFe</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* CPF/CNPJ */}
      <div>
        <Label className="text-sm text-gray-600">CPF/CNPJ:</Label>
        <Input
          value={localCpfCnpj}
          onChange={handleCpfCnpjChange}
          placeholder="000.000.000-00"
          maxLength={18}
        />
      </div>

      <Separator />

      {/* Botões */}
      <div className="flex gap-2">
        <Button
          onClick={handleEmitNow}
          className="flex-1 bg-green-600 hover:bg-green-700"
          disabled={!localType || !localCpfCnpj}
        >
          Emitir NFC-e
        </Button>
        <Button
          onClick={handleEmitLater}
          variant="outline"
          className="flex-1"
        >
          Emitir depois
        </Button>
      </div>
    </div>
  )
}

