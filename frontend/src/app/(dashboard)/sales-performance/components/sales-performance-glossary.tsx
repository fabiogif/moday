"use client"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import { HelpCircle } from "lucide-react"

export function SalesPerformanceGlossary() {
  const glossaryItems = [
    {
      term: "Total de Vendas",
      definition: "Número total de vendas realizadas no período selecionado, excluindo pedidos cancelados."
    },
    {
      term: "Valor Total das Vendas",
      definition: "Soma de todos os valores das vendas realizadas no período selecionado, excluindo pedidos cancelados."
    },
    {
      term: "Ticket Médio",
      definition: "Valor médio por venda, calculado dividindo o valor total das vendas pelo número total de vendas."
    },
    {
      term: "Novos Clientes",
      definition: "Número de clientes cadastrados pela primeira vez no período selecionado."
    },
    {
      term: "Crescimento Percentual",
      definition: "Comparação percentual entre o período atual e o período anterior equivalente. Valores positivos indicam crescimento, valores negativos indicam queda."
    },
    {
      term: "Melhor Horário",
      definition: "Horário do dia com maior número de vendas realizadas."
    },
    {
      term: "Melhor Dia da Semana",
      definition: "Dia da semana com maior número de vendas realizadas."
    },
    {
      term: "Vendas por Forma de Pagamento",
      definition: "Distribuição de vendas agrupadas por forma de pagamento utilizada, incluindo quantidade de vendas e valor total."
    }
  ]

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center gap-2">
          <HelpCircle className="h-5 w-5 text-muted-foreground" />
          <CardTitle>Glossário de Indicadores</CardTitle>
        </div>
        <CardDescription>
          Entenda o significado de cada indicador de desempenho/vendas
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Accordion type="single" collapsible className="w-full">
          {glossaryItems.map((item) => (
            <AccordionItem key={item.term} value={item.term}>
              <AccordionTrigger>{item.term}</AccordionTrigger>
              <AccordionContent>
                <p className="text-sm text-muted-foreground">{item.definition}</p>
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </CardContent>
    </Card>
  )
}

