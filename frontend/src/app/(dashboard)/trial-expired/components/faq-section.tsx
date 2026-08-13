"use client"

import { memo } from "react"
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"
import { SITE_CONTACT } from "@/lib/site-config"

const faqItems = [
  {
    id: "dados",
    question: "Meus dados serão apagados?",
    answer:
      "Não. Seus cadastros, pedidos e configurações permanecem preservados. Ao assinar um plano, você retoma exatamente de onde parou.",
  },
  {
    id: "depois",
    question: "Posso contratar depois?",
    answer:
      "Sim. Você pode voltar quando quiser e assinar um plano. Enquanto isso, o acesso aos módulos operacionais fica pausado.",
  },
  {
    id: "imediata",
    question: "A ativação é imediata?",
    answer:
      "Sim. Após a confirmação do pagamento (ou ativação do plano gratuito, quando disponível), o acesso é liberado na hora.",
  },
  {
    id: "cancelar",
    question: "Posso cancelar meu plano?",
    answer:
      "Sim. Você pode cancelar a renovação a qualquer momento nas configurações de assinatura, conforme as regras do plano contratado.",
  },
  {
    id: "suporte",
    question: "Como falar com o suporte?",
    answer: `Fale conosco pelo WhatsApp ${SITE_CONTACT.whatsappDisplay} ou pelo e-mail ${SITE_CONTACT.supportEmail}. Nossa equipe responde em português.`,
  },
]

function TrialFaqSectionComponent() {
  return (
    <section aria-labelledby="trial-faq-heading" className="space-y-3">
      <h2 id="trial-faq-heading" className="font-semibold text-lg">
        Perguntas frequentes
      </h2>
      <Accordion type="single" collapsible className="rounded-xl border bg-card px-2">
        {faqItems.map((item) => (
          <AccordionItem key={item.id} value={item.id} className="px-2">
            <AccordionTrigger className="text-left text-sm font-medium hover:no-underline">
              {item.question}
            </AccordionTrigger>
            <AccordionContent className="text-sm text-muted-foreground leading-relaxed">
              {item.answer}
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>
    </section>
  )
}

export const TrialFaqSection = memo(TrialFaqSectionComponent)
