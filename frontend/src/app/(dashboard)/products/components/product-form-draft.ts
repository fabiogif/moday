import type { ProductFormValues } from "./product-form-wizard"
import { ProductOptional, ProductVariation } from "@/types/product-variations"

const DRAFT_KEY = "product-form-draft"

export interface ProductFormDraft {
  step: number
  values: Omit<ProductFormValues, "image">
  variations: ProductVariation[]
  optionals: ProductOptional[]
}

function canUseDom() {
  return typeof window !== "undefined"
}

export function loadProductFormDraft(): ProductFormDraft | null {
  if (!canUseDom()) return null
  const raw = localStorage.getItem(DRAFT_KEY)
  if (!raw) return null
  try {
    const parsed = JSON.parse(raw) as ProductFormDraft
    if (!parsed || typeof parsed.step !== "number" || !parsed.values) return null
    return parsed
  } catch {
    return null
  }
}

export function saveProductFormDraft(draft: ProductFormDraft) {
  if (!canUseDom()) return
  const { image: _image, ...values } = draft.values as ProductFormValues
  localStorage.setItem(
    DRAFT_KEY,
    JSON.stringify({
      step: draft.step,
      values,
      variations: draft.variations,
      optionals: draft.optionals,
    }),
  )
}

export function clearProductFormDraft() {
  if (!canUseDom()) return
  localStorage.removeItem(DRAFT_KEY)
}

export function hasMeaningfulDraft(draft: ProductFormDraft | null): draft is ProductFormDraft {
  if (!draft) return false
  const name = draft.values.name?.trim()
  const description = draft.values.description?.trim()
  return Boolean(name || description || draft.values.categories?.length)
}
