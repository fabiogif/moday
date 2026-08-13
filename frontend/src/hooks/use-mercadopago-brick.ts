"use client"

import { useEffect, useRef, useCallback } from "react"

declare global {
  interface Window {
    MercadoPago: any
  }
}

interface BrickCallbacks {
  onSubmit: (formData: any, additionalData: any) => Promise<void>
  onError?: (error: any) => void
  onReady?: () => void
}

interface BrickOptions {
  amount: number
  containerId: string
}

let scriptLoaded = false
let scriptLoading = false
const scriptCallbacks: (() => void)[] = []

function loadMpScript(): Promise<void> {
  return new Promise((resolve) => {
    if (scriptLoaded) { resolve(); return }
    scriptCallbacks.push(resolve)
    if (scriptLoading) return
    scriptLoading = true
    const script = document.createElement("script")
    script.src = "https://sdk.mercadopago.com/js/v2"
    script.onload = () => {
      scriptLoaded = true
      scriptLoading = false
      scriptCallbacks.forEach((cb) => cb())
      scriptCallbacks.length = 0
    }
    document.head.appendChild(script)
  })
}

export function useMercadoPagoBrick(
  publicKey: string,
  options: BrickOptions,
  callbacks: BrickCallbacks
) {
  const controllerRef = useRef<any>(null)
  const mountedRef = useRef(false)

  const destroy = useCallback(() => {
    if (controllerRef.current) {
      try {
        controllerRef.current.unmount()
      } catch {}
      controllerRef.current = null
    }
  }, [])

  useEffect(() => {
    if (!publicKey || !options.containerId) return
    mountedRef.current = true

    let readyFired = false
    const timeoutId = setTimeout(() => {
      if (!mountedRef.current || readyFired) return
      callbacks.onError?.({
        message:
          "Tempo limite ao inicializar o formulário. Verifique: Public Key válida e domínio autorizado no painel do Mercado Pago.",
      })
    }, 15000)

    loadMpScript().then(() => {
      if (!mountedRef.current) return

      let mp: any
      try {
        mp = new window.MercadoPago(publicKey, { locale: "pt-BR" })
      } catch (err: any) {
        clearTimeout(timeoutId)
        callbacks.onError?.(err)
        return
      }

      const bricksBuilder = mp.bricks()

      const settings = {
        initialization: { amount: options.amount },
        callbacks: {
          onReady: () => {
            readyFired = true
            clearTimeout(timeoutId)
            callbacks.onReady?.()
          },
          onSubmit: (formData: any, additionalData: any) =>
            callbacks.onSubmit(formData, additionalData),
          onError: (err: any) => {
            clearTimeout(timeoutId)
            callbacks.onError?.(err)
          },
        },
        customization: {
          paymentMethods: { minInstallments: 1, maxInstallments: 12 },
        },
      }

      bricksBuilder
        .create("cardPayment", options.containerId, settings)
        .then((controller: any) => {
          if (!mountedRef.current) {
            try { controller.unmount() } catch {}
            return
          }
          controllerRef.current = controller
        })
        .catch((err: any) => {
          clearTimeout(timeoutId)
          callbacks.onError?.(err)
        })
    })

    return () => {
      mountedRef.current = false
      clearTimeout(timeoutId)
      destroy()
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [publicKey, options.containerId, options.amount])

  return { destroy }
}
