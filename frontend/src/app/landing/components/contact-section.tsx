"use client"

import { zodResolver } from "@hookform/resolvers/zod"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useToast } from '@/hooks/use-toast'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Mail, Phone, Headphones, Wallet } from 'lucide-react'
import { useState } from 'react'
import { SITE_CONTACT } from '@/lib/site-config'

const contactFormSchema = z.object({
  firstName: z.string().min(2, { message: "O nome deve ter pelo menos 2 caracteres." }),
  lastName: z.string().min(2, { message: "O sobrenome deve ter pelo menos 2 caracteres." }),
  email: z.string().email({ message: "Por favor, insira um endereço de email válido." }),
  subject: z.string().min(5, { message: "O assunto deve ter pelo menos 5 caracteres." }),
  message: z.string().min(10, { message: "A mensagem deve ter pelo menos 10 caracteres." }),
})

export function ContactSection() {
  const [isLoading, setIsLoading] = useState(false)
  const { toast } = useToast()

  const form = useForm<z.infer<typeof contactFormSchema>>({
    resolver: zodResolver(contactFormSchema),
    defaultValues: { firstName: "", lastName: "", email: "", subject: "", message: "" },
  })

  async function onSubmit(values: z.infer<typeof contactFormSchema>) {
    setIsLoading(true)
    try {
      const response = await fetch('/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(values),
      })
      const data = await response.json()
      if (response.ok) {
        toast({ title: "Mensagem enviada!", description: "Entraremos em contato em breve." })
        form.reset()
      } else {
        throw new Error(data.message || 'Erro ao enviar mensagem')
      }
    } catch (error: any) {
      toast({ title: "Erro ao enviar mensagem", description: error.message || "Tente novamente mais tarde.", variant: "destructive" })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <section id="contact" className="py-20 sm:py-24 bg-stone-50 border-t border-zinc-200">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mx-auto max-w-2xl text-center mb-16">
          <p className="text-[11px] uppercase tracking-[0.18em] text-orange-600 font-medium mb-4">
            Entre em Contato
          </p>
          <h2 className="text-3xl font-bold tracking-tight sm:text-4xl mb-4">
            Precisa de ajuda ou tem dúvidas?
          </h2>
          <p className="text-lg text-zinc-500">
            Nossa equipe está aqui para ajudá-lo a aproveitar ao máximo o Alba Tec. Entre em contato conosco.
          </p>
        </div>

        <div className="grid gap-8 lg:grid-cols-4">
          <div className="lg:col-span-2 order-2 lg:order-1 space-y-6">
            <Card className="border-zinc-200 hover:border-zinc-300 transition-colors">
              <CardHeader>
                <CardTitle className="flex items-center gap-3">
                  <div className="p-2.5 rounded-lg bg-orange-50">
                    <Mail className="h-5 w-5 text-orange-600" />
                  </div>
                  Contato geral
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-zinc-500 mb-2">Dúvidas, parcerias e informações</p>
                <a href={`mailto:${SITE_CONTACT.email}`} className="text-zinc-800 font-medium underline decoration-zinc-300 hover:decoration-zinc-600 transition-colors">
                  {SITE_CONTACT.email}
                </a>
              </CardContent>
            </Card>

            <Card className="border-zinc-200 hover:border-zinc-300 transition-colors">
              <CardHeader>
                <CardTitle className="flex items-center gap-3">
                  <div className="p-2.5 rounded-lg bg-zinc-100">
                    <Headphones className="h-5 w-5 text-zinc-600" />
                  </div>
                  Atendimento
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-zinc-500 mb-2">Suporte a clientes e assistência técnica</p>
                <a href={`mailto:${SITE_CONTACT.supportEmail}`} className="text-zinc-800 font-medium underline decoration-zinc-300 hover:decoration-zinc-600 transition-colors">
                  {SITE_CONTACT.supportEmail}
                </a>
              </CardContent>
            </Card>

            <Card className="border-zinc-200 hover:border-zinc-300 transition-colors">
              <CardHeader>
                <CardTitle className="flex items-center gap-3">
                  <div className="p-2.5 rounded-lg bg-zinc-100">
                    <Wallet className="h-5 w-5 text-zinc-600" />
                  </div>
                  Financeiro / PIX
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-zinc-500 mb-2">Pagamentos, faturas e questões financeiras</p>
                <a href={`mailto:${SITE_CONTACT.pixEmail}`} className="text-zinc-800 font-medium underline decoration-zinc-300 hover:decoration-zinc-600 transition-colors">
                  {SITE_CONTACT.pixEmail}
                </a>
              </CardContent>
            </Card>

            <Card className="border-zinc-200 hover:border-zinc-300 transition-colors">
              <CardHeader>
                <CardTitle className="flex items-center gap-3">
                  <div className="p-2.5 rounded-lg bg-zinc-100">
                    <Phone className="h-5 w-5 text-zinc-600" />
                  </div>
                  WhatsApp
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-zinc-500 mb-2">Fale conosco pelo WhatsApp</p>
                <a
                  href={SITE_CONTACT.whatsappUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-zinc-800 font-medium underline decoration-zinc-300 hover:decoration-zinc-600 transition-colors"
                >
                  {SITE_CONTACT.whatsappDisplay}
                </a>
              </CardContent>
            </Card>
          </div>

          <div className="lg:col-span-2 order-1 lg:order-2">
            <Card className="border-zinc-200">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Mail className="h-5 w-5" />
                  Envie-nos uma mensagem
                </CardTitle>
              </CardHeader>
              <CardContent>
                <Form {...form}>
                  <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <FormField control={form.control} name="firstName" render={({ field }) => (
                        <FormItem><FormLabel>Nome</FormLabel><FormControl><Input placeholder="Seu nome" {...field} /></FormControl><FormMessage /></FormItem>
                      )} />
                      <FormField control={form.control} name="lastName" render={({ field }) => (
                        <FormItem><FormLabel>Sobrenome</FormLabel><FormControl><Input placeholder="Seu sobrenome" {...field} /></FormControl><FormMessage /></FormItem>
                      )} />
                    </div>
                    <FormField control={form.control} name="email" render={({ field }) => (
                      <FormItem><FormLabel>Email</FormLabel><FormControl><Input type="email" placeholder="seuemail@exemplo.com" {...field} /></FormControl><FormMessage /></FormItem>
                    )} />
                    <FormField control={form.control} name="subject" render={({ field }) => (
                      <FormItem><FormLabel>Assunto</FormLabel><FormControl><Input placeholder="Dúvida sobre o sistema, suporte, orçamento..." {...field} /></FormControl><FormMessage /></FormItem>
                    )} />
                    <FormField control={form.control} name="message" render={({ field }) => (
                      <FormItem><FormLabel>Mensagem</FormLabel><FormControl><Textarea placeholder="Conte-nos como podemos ajudá-lo com o Alba Tec..." rows={10} className="min-h-50" {...field} /></FormControl><FormMessage /></FormItem>
                    )} />
                    <Button type="submit" className="w-full cursor-pointer bg-zinc-900 text-white hover:bg-zinc-700" disabled={isLoading}>
                      {isLoading ? "Enviando..." : "Enviar Mensagem"}
                    </Button>
                  </form>
                </Form>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </section>
  )
}
