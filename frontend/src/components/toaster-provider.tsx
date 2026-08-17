"use client"

import { Toaster as Sonner } from "sonner"

export function ToasterProvider() {
  return (
    <Sonner
      className="toaster group"
      // Limita quantos toasts ficam visíveis ao mesmo tempo (o restante fica
      // enfileirado) — evita o empilhamento descoordenado quando vários
      // eventos (ex.: pedidos + login) disparam toast() em sequência rápida.
      visibleToasts={3}
      style={
        {
          "--normal-bg": "var(--popover)",
          "--normal-text": "var(--popover-foreground)",
          "--normal-border": "var(--border)",
        } as React.CSSProperties
      }
    />
  )
}
