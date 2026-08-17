import { toast } from "sonner"
import { AlertCircle, XCircle } from "lucide-react"
import { formatErrorMessage, extractValidationErrors, translateFieldName } from "@/lib/error-formatter"

/**
 * Exibe um toast de erro formatado com as mensagens do backend
 */
export function showErrorToast(error: any, title?: string) {
  const errors = extractValidationErrors(error)
  const entries = Object.entries(errors).filter(([key]) => key !== '_general')

  // Se tem erro geral ou apenas um erro de campo
  if (errors._general || entries.length === 1) {
    const message = errors._general || entries[0][1]
    toast.error(title || "Erro", {
      description: message,
      icon: <XCircle className="h-4 w-4" />,
      duration: 5000,
    })
    return
  }

  // Se tem múltiplos erros de validação
  if (entries.length > 1) {
    const errorList = entries.slice(0, 5).map(([field, msg]) => {
      const fieldName = translateFieldName(field)
      return `${fieldName}: ${msg}`
    }).join('\n')

    toast.error(title || "Erros de Validação", {
      description: errorList,
      icon: <AlertCircle className="h-4 w-4" />,
      duration: 7000,
    })
    return
  }

  // Fallback
  toast.error(title || "Erro", {
    description: "Ocorreu um erro ao processar sua requisição",
    icon: <XCircle className="h-4 w-4" />,
    duration: 5000,
  })
}

/**
 * Exibe um toast de sucesso
 */
export function showSuccessToast(message: string, title?: string) {
  toast.success(title || "Sucesso", {
    description: message,
    duration: 4000,
  })
}

/**
 * Exibe um toast de warning
 */
export function showWarningToast(message: string, title?: string) {
  toast.warning(title || "Atenção", {
    description: message,
    duration: 5000,
  })
}

