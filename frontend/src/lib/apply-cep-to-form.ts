import type { AddressData } from '@/services/viacep'
import type { FieldValues, Path, UseFormSetValue } from 'react-hook-form'

export interface ApplyCepFieldMap {
  address?: string
  neighborhood?: string
  complement?: string
  state: string
  city: string
  zipCode?: string
}

/**
 * Aplica o resultado do CEP nos campos do formulário.
 * Define estado (UF) primeiro e cidade no próximo tick para aguardar o carregamento local.
 */
export function applyCepToForm<T extends FieldValues>(
  setValue: UseFormSetValue<T>,
  address: AddressData,
  fields: ApplyCepFieldMap,
  options?: { shouldDirty?: boolean; shouldValidate?: boolean }
): void {
  const opts = {
    shouldDirty: options?.shouldDirty ?? true,
    shouldValidate: options?.shouldValidate ?? false,
  }

  if (fields.address) {
    setValue(fields.address as Path<T>, (address.address || '') as never, opts)
  }
  if (fields.neighborhood) {
    setValue(fields.neighborhood as Path<T>, (address.neighborhood || '') as never, opts)
  }
  if (fields.complement && address.complement !== undefined) {
    setValue(fields.complement as Path<T>, (address.complement || '') as never, opts)
  }
  if (fields.zipCode && address.zipCode) {
    setValue(fields.zipCode as Path<T>, address.zipCode as never, opts)
  }

  setValue(fields.state as Path<T>, (address.state || '') as never, opts)

  // Aguarda o select de cidades carregar a lista do estado selecionado
  window.setTimeout(() => {
    setValue(fields.city as Path<T>, (address.city || '') as never, opts)
  }, 300)
}

/**
 * Aplica CEP em estado controlado (não-RHF), ex.: StateCitySelect.
 */
export function applyCepToStateHandlers(
  address: AddressData,
  handlers: {
    setAddress?: (v: string) => void
    setNeighborhood?: (v: string) => void
    setComplement?: (v: string) => void
    setState: (v: string) => void
    setCity: (v: string) => void
    setZipCode?: (v: string) => void
  }
): void {
  handlers.setAddress?.(address.address || '')
  handlers.setNeighborhood?.(address.neighborhood || '')
  handlers.setComplement?.(address.complement || '')
  handlers.setZipCode?.(address.zipCode || '')
  handlers.setState(address.state || '')
  window.setTimeout(() => {
    handlers.setCity(address.city || '')
  }, 300)
}
