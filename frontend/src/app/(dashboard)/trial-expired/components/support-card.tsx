"use client"

import { memo } from "react"
import { MessageCircle } from "lucide-react"
import { Button } from "@/components/ui/button"
import { SITE_CONTACT } from "@/lib/site-config"

function SupportCardComponent() {
  return (
    <aside
      className="rounded-xl border bg-card p-5 sm:p-6 text-center shadow-xs"
      aria-labelledby="trial-support-heading"
    >
      <h2 id="trial-support-heading" className="font-semibold text-lg">
        Ainda possui dúvidas?
      </h2>
      <p className="text-sm text-muted-foreground mt-1 mb-4">
        Nossa equipe responde rapidamente pelo WhatsApp.
      </p>
      <Button asChild variant="outline" size="lg" className="w-full sm:w-auto">
        <a
          href={SITE_CONTACT.whatsappUrl}
          target="_blank"
          rel="noopener noreferrer"
          aria-label={`Falar com suporte no WhatsApp ${SITE_CONTACT.whatsappDisplay}`}
        >
          <MessageCircle className="mr-2 h-4 w-4" aria-hidden />
          Falar pelo WhatsApp
        </a>
      </Button>
    </aside>
  )
}

export const SupportCard = memo(SupportCardComponent)
