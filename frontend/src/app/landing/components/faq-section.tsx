"use client"

import { CircleHelp } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import { LANDING_FAQ_ITEMS } from '@/lib/landing-faq'

const FaqSection = () => {
  return (
    <section id="faq" className="py-20 sm:py-24 bg-white border-t border-zinc-200">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="max-w-2xl mb-16">
          <p className="text-[11px] uppercase tracking-[0.18em] text-orange-600 font-medium mb-4">
            Perguntas Frequentes
          </p>
          <h2 className="text-3xl font-bold tracking-[-0.02em] sm:text-4xl mb-4 text-zinc-900">
            Dúvidas sobre o sistema de gestão para restaurantes
          </h2>
          <p className="text-lg text-zinc-500">
            Respostas sobre preços, teste grátis, mobile, WhatsApp, suporte e implantação. Ainda
            precisa de ajuda?{' '}
            <a href="#contact" className="text-zinc-900 font-medium underline-offset-4 hover:underline">
              Fale conosco
            </a>
            .
          </p>
        </div>

        <div className="max-w-3xl">
          <Accordion type="single" collapsible className="space-y-3">
            {LANDING_FAQ_ITEMS.map(item => (
              <AccordionItem
                key={item.id}
                value={item.id}
                className="rounded-xl border border-zinc-200 bg-zinc-50 px-1"
              >
                <AccordionTrigger className="cursor-pointer items-center gap-4 rounded-none bg-transparent py-2 ps-3 pe-4 hover:no-underline data-[state=open]:border-b data-[state=open]:border-zinc-200">
                  <div className="flex items-center gap-4">
                    <div className="bg-orange-50 text-orange-600 flex size-9 shrink-0 items-center justify-center rounded-lg">
                      <CircleHelp className="size-4" />
                    </div>
                    <span className="text-start font-semibold text-zinc-900 text-sm">
                      {item.question}
                    </span>
                  </div>
                </AccordionTrigger>
                <AccordionContent className="px-4 pb-4 pt-3 text-sm text-zinc-500 leading-relaxed">
                  {item.answer}
                </AccordionContent>
              </AccordionItem>
            ))}
          </Accordion>

          <div className="mt-12 text-center">
            <p className="text-zinc-500 mb-4">Ainda tem dúvidas? Estamos aqui para ajudar.</p>
            <Button
              className="bg-zinc-900 text-white hover:bg-zinc-700 rounded-md h-10 px-6 text-sm cursor-pointer"
              asChild
            >
              <a href="#contact">Fale Conosco</a>
            </Button>
          </div>
        </div>
      </div>
    </section>
  )
}

export { FaqSection }
