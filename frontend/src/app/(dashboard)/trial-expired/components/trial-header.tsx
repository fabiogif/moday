"use client"

import Link from "next/link"
import { Rocket, ArrowRight } from "lucide-react"
import { Button } from "@/components/ui/button"
import { SITE_CONTACT } from "@/lib/site-config"

type TrialHeaderProps = {
  subscribeHref?: string
}

export function TrialHeader({ subscribeHref = "/subscribe" }: TrialHeaderProps) {
  return (
    <header className="text-center space-y-5">
      <div className="flex justify-center">
        <div
          className="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/20"
          aria-hidden
        >
          <Rocket className="h-8 w-8" />
        </div>
      </div>

      <div className="space-y-3">
        <h1 className="text-3xl font-bold tracking-tight text-balance sm:text-4xl">
          Obrigado por testar o Alba Tec!
        </h1>
        <p className="text-base text-muted-foreground sm:text-lg max-w-xl mx-auto text-balance">
          Seu período gratuito chegou ao fim. Continue utilizando todos os recursos escolhendo um
          dos nossos planos — seus dados estão seguros e prontos para seguir.
        </p>
      </div>

      <div className="flex flex-col sm:flex-row gap-3 justify-center pt-1">
        <Button
          asChild
          size="lg"
          className="w-full sm:w-auto sm:min-w-[280px] h-12 text-base font-semibold shadow-md hover:shadow-lg transition-all hover:scale-[1.02] focus-visible:ring-2 focus-visible:ring-primary"
        >
          <Link href={subscribeHref} aria-label="Ver planos e assinar o Alba Tec">
            Ver Planos e Assinar
            <ArrowRight className="ml-2 h-5 w-5" aria-hidden />
          </Link>
        </Button>
        <Button asChild variant="outline" size="lg" className="w-full sm:w-auto h-12">
          <a
            href={SITE_CONTACT.whatsappUrl}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Falar com suporte pelo WhatsApp"
          >
            Falar com Suporte
          </a>
        </Button>
      </div>
    </header>
  )
}
