import { useState, useCallback, useRef } from 'react'
import { searchAddressByCEP, isValidCEP, AddressData } from '@/services/viacep'
import { toast } from 'sonner'

export type CepLookupStatus = 'idle' | 'loading' | 'found' | 'not_found' | 'invalid' | 'error'

function digitsOnly(cep: string): string {
  return cep.replace(/\D/g, '')
}

interface UseViaCEPReturn {
  loading: boolean
  error: string | null
  status: CepLookupStatus
  /** True only after a successful lookup for the current CEP. */
  found: boolean
  searchCEP: (cep: string) => Promise<AddressData | null>
  /**
   * Call on every CEP input change.
   * Returns true when a previously found CEP was altered or cleared,
   * so linked address fields should be reset.
   */
  notifyCepChange: (cep: string) => boolean
  reset: () => void
}

/**
 * Consulta de CEP com trava de Estado/Cidade baseada no resultado da busca,
 * não na mera presença de um valor no campo.
 */
export function useViaCEP(): UseViaCEPReturn {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [status, setStatus] = useState<CepLookupStatus>('idle')
  const [found, setFound] = useState(false)

  const resolvedCepRef = useRef<string | null>(null)
  const requestIdRef = useRef(0)
  const inFlightCepRef = useRef<string | null>(null)

  const invalidateInFlight = useCallback(() => {
    requestIdRef.current += 1
    inFlightCepRef.current = null
  }, [])

  const reset = useCallback(() => {
    invalidateInFlight()
    resolvedCepRef.current = null
    setFound(false)
    setStatus('idle')
    setError(null)
    setLoading(false)
  }, [invalidateInFlight])

  const notifyCepChange = useCallback(
    (cep: string): boolean => {
      const digits = digitsOnly(cep)
      const resolved = resolvedCepRef.current
      const inFlight = inFlightCepRef.current

      if (inFlight && inFlight !== digits) {
        invalidateInFlight()
        setLoading(false)
        setFound(false)
        setError(null)
        setStatus(digits.length === 0 ? 'idle' : digits.length < 8 ? 'invalid' : 'idle')
      }

      if (!resolved) {
        if (digits.length === 0) {
          setStatus('idle')
          setError(null)
        } else if (digits.length < 8) {
          setStatus('invalid')
        }
        return false
      }

      if (digits === resolved) {
        return false
      }

      invalidateInFlight()
      resolvedCepRef.current = null
      setFound(false)
      setLoading(false)
      setError(null)
      setStatus(digits.length === 0 ? 'idle' : digits.length < 8 ? 'invalid' : 'idle')
      return true
    },
    [invalidateInFlight]
  )

  const searchCEP = useCallback(async (cep: string): Promise<AddressData | null> => {
    const digits = digitsOnly(cep)

    if (digits.length === 0) {
      notifyCepChange(cep)
      return null
    }

    if (!isValidCEP(digits)) {
      notifyCepChange(cep)
      setStatus('invalid')
      return null
    }

    if (resolvedCepRef.current === digits) {
      return null
    }

    if (inFlightCepRef.current === digits) {
      return null
    }

    const requestId = ++requestIdRef.current
    inFlightCepRef.current = digits
    setFound(false)
    setLoading(true)
    setError(null)
    setStatus('loading')

    try {
      const address = await searchAddressByCEP(digits)

      if (requestId !== requestIdRef.current) {
        return null
      }

      if (!address) {
        resolvedCepRef.current = null
        setFound(false)
        setStatus('not_found')
        setError(null)
        toast.info('CEP não encontrado. Preencha estado e cidade manualmente.')
        return null
      }

      resolvedCepRef.current = digits
      setFound(true)
      setStatus('found')
      toast.success('Endereço encontrado!')
      return address
    } catch (err: unknown) {
      if (requestId !== requestIdRef.current) {
        return null
      }

      const errorMessage =
        err instanceof Error ? err.message : 'Erro ao buscar CEP'
      resolvedCepRef.current = null
      setFound(false)
      setStatus('error')
      setError(errorMessage)
      toast.error(errorMessage)
      return null
    } finally {
      if (requestId === requestIdRef.current) {
        setLoading(false)
        inFlightCepRef.current = null
      }
    }
  }, [notifyCepChange])

  return {
    loading,
    error,
    status,
    found,
    searchCEP,
    notifyCepChange,
    reset,
  }
}

/**
 * Hook simplificado que apenas busca o CEP sem feedback visual.
 */
export function useViaCEPQuiet(): {
  searchCEP: (cep: string) => Promise<AddressData | null>
} {
  const searchCEP = useCallback(async (cep: string): Promise<AddressData | null> => {
    try {
      return await searchAddressByCEP(cep)
    } catch {
      return null
    }
  }, [])

  return { searchCEP }
}
