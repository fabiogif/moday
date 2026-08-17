"use client"

import { zodResolver } from "@hookform/resolvers/zod"
import { useState } from "react"
import { useForm } from "react-hook-form"
import { z } from "zod"
import Link from "next/link"
import { Separator } from '@/components/ui/separator'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AlbaTecLogo, LANDING_FOOTER_BRAND_ICON_SIZE } from '@/components/albatec-logo'
import { toast } from 'sonner'
import { Loader2 } from 'lucide-react'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form"
import { SITE_CONTACT } from '@/lib/site-config'

const newsletterSchema = z.object({
  email: z.string().email({
    message: "Por favor, insira um endereço de e-mail válido.",
  }),
})

const footerLinks = {
  product: [
    { name: 'O que é o Alba Tec', href: '/#o-que-e' },
    { name: 'Recursos do sistema', href: '/#features' },
    { name: 'Planos e preços', href: '/#pricing' },
    { name: 'Demonstração do cardápio', href: '/demo/menu' },
    { name: 'Criar conta grátis', href: '/auth/register' },
  ],
  company: [
    { name: 'Sobre', href: '/sobre' },
    { name: 'Blog', href: '/blog' },
    { name: 'Contato', href: '/#contact' },
    { name: 'Perguntas frequentes', href: '/#faq' },
    { name: 'Política de Privacidade', href: '/privacidade' },
    { name: 'Termos de Uso', href: '/termos' },
  ],
}

export function LandingFooter() {
  const [isSubmitting, setIsSubmitting] = useState(false)

  const form = useForm<z.infer<typeof newsletterSchema>>({
    resolver: zodResolver(newsletterSchema),
    defaultValues: {
      email: "",
    },
  })

  async function onSubmit(values: z.infer<typeof newsletterSchema>) {
    setIsSubmitting(true)

    try {
      const response = await fetch('/api/newsletter/subscribe', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(values),
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Erro ao realizar inscrição')
      }

      toast.success(data.message || 'Inscrição realizada com sucesso!')
      form.reset()
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Erro ao realizar inscrição')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <footer className="border-t bg-background" role="contentinfo">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="mb-16">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-2xl font-bold mb-4">Fique por dentro</h2>
            <p className="text-muted-foreground mb-6">
              Receba novidades, dicas e atualizações sobre gestão de restaurantes.
            </p>
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-col gap-2 max-w-md mx-auto sm:flex-row">
                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem className="flex-1">
                      <FormControl>
                        <Input
                          type="email"
                          placeholder="Digite seu e-mail"
                          aria-label="E-mail para newsletter"
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <Button type="submit" className="cursor-pointer rounded-md bg-zinc-900 text-white hover:bg-zinc-700 transition-colors" disabled={isSubmitting}>
                  {isSubmitting ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Enviando...
                    </>
                  ) : (
                    'Inscrever'
                  )}
                </Button>
              </form>
            </Form>
          </div>
        </div>

        <div className="grid gap-8 grid-cols-2 lg:grid-cols-4">
          <div className="col-span-2 max-w-2xl">
            <div className="mb-5 max-lg:flex max-lg:justify-center">
              <AlbaTecLogo
                variant="icon"
                width={LANDING_FOOTER_BRAND_ICON_SIZE}
                height={LANDING_FOOTER_BRAND_ICON_SIZE}
                className="shrink-0"
              />
            </div>
            <p className="text-muted-foreground mb-4 max-lg:text-center max-lg:flex max-lg:justify-center text-sm leading-relaxed">
              Sistema de gestão para restaurantes com PDV, cardápio digital, financeiro e relatórios
              em uma única plataforma.
            </p>
            <p className="text-sm text-muted-foreground max-lg:text-center">
              Contato:{' '}
              <a
                href={`mailto:${SITE_CONTACT.email}`}
                className="text-foreground hover:underline"
              >
                {SITE_CONTACT.email}
              </a>
              {' · '}
              WhatsApp:{' '}
              <a
                href={SITE_CONTACT.whatsappUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-foreground hover:underline"
              >
                {SITE_CONTACT.whatsappDisplay}
              </a>
            </p>
            {SITE_CONTACT.cnpj ? (
              <p className="text-xs text-muted-foreground mt-2 max-lg:text-center">
                CNPJ: {SITE_CONTACT.cnpj}
                {SITE_CONTACT.address ? ` · ${SITE_CONTACT.address}` : ''}
              </p>
            ) : null}
          </div>

          <div className='max-md:col-span-1 lg:col-span-1'>
            <h3 className="font-semibold mb-4">Produto</h3>
            <ul className="space-y-3">
              {footerLinks.product.map((link) => (
                <li key={link.name}>
                  <Link
                    href={link.href}
                    className="text-muted-foreground hover:text-foreground transition-colors text-sm"
                  >
                    {link.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div className='max-md:col-span-1 lg:col-span-1'>
            <h3 className="font-semibold mb-4">Empresa</h3>
            <ul className="space-y-3">
              {footerLinks.company.map((link) => (
                <li key={link.name}>
                  <Link
                    href={link.href}
                    className="text-muted-foreground hover:text-foreground transition-colors text-sm"
                  >
                    {link.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <Separator className="my-8" />

        <div className="flex flex-col lg:flex-row justify-between items-center gap-2">
          <div className="text-muted-foreground text-sm text-center lg:text-left">
            © {new Date().getFullYear()} Alba Tec. Todos os direitos reservados.
          </div>
          <div className="flex gap-4 text-sm text-muted-foreground">
            <Link href="/privacidade" className="hover:text-foreground">
              Privacidade
            </Link>
            <Link href="/termos" className="hover:text-foreground">
              Termos
            </Link>
          </div>
        </div>
      </div>
    </footer>
  )
}
