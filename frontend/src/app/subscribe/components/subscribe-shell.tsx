"use client"

import Link from "next/link"
import { ArrowLeft, LayoutDashboard, LogIn } from "lucide-react"
import { AlbaTecLogo, LANDING_FOOTER_BRAND_ICON_SIZE } from "@/components/albatec-logo"
import { Button } from "@/components/ui/button"
import { LandingLightThemeLock } from "@/app/landing/components/landing-light-theme-lock"
import { useAuth } from "@/contexts/auth-context"

const footerLinks = [
  { name: "Recursos", href: "/landing#features" },
  { name: "Planos", href: "/landing#pricing" },
  { name: "FAQ", href: "/landing#faq" },
  { name: "Contato", href: "/landing#contact" },
]

export function SubscribeShell({ children }: { children: React.ReactNode }) {
  const { isAuthenticated } = useAuth()

  return (
    <LandingLightThemeLock>
      <header className="sticky top-0 z-50 w-full border-b border-zinc-200 bg-white/90 backdrop-blur-xl supports-[backdrop-filter]:bg-white/80">
        <div className="container mx-auto flex h-24 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
          <div className="flex items-center gap-3 min-w-0">
            <AlbaTecLogo href="/landing" variant="full" height={80} adaptive priority />
            <span className="hidden sm:inline text-xs font-medium uppercase tracking-[0.18em] text-zinc-500 truncate">
              Assinatura
            </span>
          </div>

          <nav className="flex items-center gap-1.5 sm:gap-2 shrink-0">
            <Button
              asChild
              variant="ghost"
              size="sm"
              className="hidden md:inline-flex text-zinc-700"
            >
              <Link href="/landing#pricing">
                <ArrowLeft className="h-4 w-4 mr-1.5" />
                Voltar aos planos
              </Link>
            </Button>

            {isAuthenticated ? (
              <Button
                asChild
                size="sm"
                className="rounded-md bg-zinc-900 text-white hover:bg-zinc-700"
              >
                <Link href="/dashboard">
                  <LayoutDashboard className="h-4 w-4 mr-1.5" />
                  Painel
                </Link>
              </Button>
            ) : (
              <Button
                asChild
                size="sm"
                className="rounded-md bg-zinc-900 text-white hover:bg-zinc-700"
              >
                <Link href="/auth/login">
                  <LogIn className="h-4 w-4 mr-1.5" />
                  Login
                </Link>
              </Button>
            )}
          </nav>
        </div>
      </header>

      <main className="flex-1">{children}</main>

      <footer className="border-t border-zinc-200 bg-white mt-auto">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
          <div className="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
            <div className="max-w-md space-y-3">
              <AlbaTecLogo
                href="/landing"
                variant="icon"
                width={LANDING_FOOTER_BRAND_ICON_SIZE}
                height={LANDING_FOOTER_BRAND_ICON_SIZE}
                className="shrink-0"
              />
              <p className="text-sm text-zinc-500 leading-relaxed">
                Sistema completo de gestão para restaurantes. Pedidos, cardápio,
                financeiro e relatórios em uma única plataforma.
              </p>
            </div>

            <div className="grid grid-cols-2 gap-x-8 gap-y-3 sm:flex sm:flex-wrap sm:gap-x-6">
              {footerLinks.map((link) => (
                <Link
                  key={link.name}
                  href={link.href}
                  className="text-sm text-zinc-500 hover:text-zinc-900 transition-colors"
                >
                  {link.name}
                </Link>
              ))}
            </div>
          </div>

          <div className="mt-8 pt-6 border-t border-zinc-100 text-center sm:text-left text-xs text-zinc-400">
            © {new Date().getFullYear()} Alba Tec. Todos os direitos reservados.
          </div>
        </div>
      </footer>
    </LandingLightThemeLock>
  )
}
