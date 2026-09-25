import { useState } from "react"
import { toast } from "sonner"

interface ImageFieldMessages {
  removed: string
  removeCanceled: string
}

/**
 * Estado de uma imagem da empresa (logo, capa) no formulário: arquivo novo com prévia
 * ou remoção pendente. O envio acontece junto com o restante do formulário.
 */
export function useImageField(messages: ImageFieldMessages) {
  const [file, setFile] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const [markedForRemoval, setMarkedForRemoval] = useState(false)

  const select = (selected: File) => {
    setFile(selected)
    setMarkedForRemoval(false)
    const reader = new FileReader()
    reader.onloadend = () => setPreview(reader.result as string)
    reader.readAsDataURL(selected)
  }

  const markForRemoval = () => {
    setFile(null)
    setPreview(null)
    setMarkedForRemoval(true)
    toast.success(messages.removed)
  }

  const cancelRemoval = () => {
    setMarkedForRemoval(false)
    toast.success(messages.removeCanceled)
  }

  const reset = () => {
    setFile(null)
    setPreview(null)
    setMarkedForRemoval(false)
  }

  /** Adiciona `field` (arquivo novo) ou `remove_{field}` ao multipart do update do tenant */
  const appendTo = (formData: FormData, field: string) => {
    if (file) {
      formData.append(field, file)
    } else if (markedForRemoval) {
      // A regra "boolean" do Laravel aceita "1", não "true"
      formData.append(`remove_${field}`, "1")
    }
  }

  return {
    file,
    preview,
    markedForRemoval,
    hasChanges: file !== null || markedForRemoval,
    select,
    markForRemoval,
    cancelRemoval,
    reset,
    appendTo,
  }
}
