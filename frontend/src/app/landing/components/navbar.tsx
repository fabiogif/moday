"use client"

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { Menu, X } from 'lucide-react'
import { AlbaTecLogo } from '@/components/albatec-logo'
import { Button } from '@/components/ui/button'
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
} from '@/components/ui/navigation-menu'
import {
  Sheet,
  SheetContent,
  SheetTrigger,
  SheetHeader,
  SheetTitle
} from '@/components/ui/sheet'
import { TRIAL_CTA_LABEL } from '@/lib/landing-copy'
import { useLandingCTAClick } from '@/hooks/use-landing-cta-click'

const navigationItems = [
  { name: 'O que é', href: '#o-que-e' },
  { name: 'Recursos', href: '#features' },
  { name: 'Planos', href: '#pricing' },
  { name: 'Blog', href: '/blog' },
  { name: 'Demo', href: '/demo/menu' },
  { name: 'FAQ', href: '#faq' },
  { name: 'Contato', href: '#contact' },
]

const smoothScrollTo = (targetId: string) => {
  if (targetId.startsWith('#')) {
    const element = document.querySelector(targetId)
    if (element) {
      element.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
      })
    }
  }
}

export function LandingNavbar() {
  const [isOpen, setIsOpen] = useState(false)
  const router = useRouter()
  const trackCTA = useLandingCTAClick('cta_navbar_click')

  return (
    <header
      className="sticky top-0 z-50 w-full border-b border-zinc-200 bg-white/90 backdrop-blur-xl supports-[backdrop-filter]:bg-white/80"
      role="banner"
    >
      <div className="container mx-auto flex h-24 items-center justify-between px-4 sm:px-6 lg:px-8">
        <div className="flex items-center space-x-2">
          <AlbaTecLogo href="/" variant="full" height={80} adaptive priority />
        </div>

        <NavigationMenu className="hidden xl:flex">
          <NavigationMenuList>
            {navigationItems.map((item) => (
              <NavigationMenuItem key={item.name}>
                <NavigationMenuLink
                  className="group inline-flex h-10 w-max cursor-pointer items-center justify-center px-4 py-2 text-sm font-medium text-zinc-700 transition-colors hover:text-zinc-900 focus:text-zinc-900 focus:outline-none"
                  onClick={(e: React.MouseEvent) => {
                    e.preventDefault()
                    if (item.href.startsWith('#')) {
                      smoothScrollTo(item.href)
                    } else {
                      router.push(item.href)
                    }
                  }}
                >
                  {item.name}
                </NavigationMenuLink>
              </NavigationMenuItem>
            ))}
          </NavigationMenuList>
        </NavigationMenu>

        <div className="hidden items-center space-x-2 xl:flex">
          <Link href="/auth/login" className="cursor-pointer">
            <Button variant="ghost" className="cursor-pointer text-zinc-700">
              Login
            </Button>
          </Link>
          <Button asChild className="cursor-pointer rounded-md bg-zinc-900 text-white hover:bg-zinc-700">
            <Link href="/auth/register" onClick={() => trackCTA('/auth/register')}>
              {TRIAL_CTA_LABEL}
            </Link>
          </Button>
        </div>

        <Sheet open={isOpen} onOpenChange={setIsOpen}>
          <SheetTrigger asChild className="xl:hidden">
            <Button variant="ghost" size="icon" className="cursor-pointer">
              <Menu className="h-5 w-5" />
              <span className="sr-only">Abrir menu</span>
            </Button>
          </SheetTrigger>
          <SheetContent side="right" className="flex w-full flex-col gap-0 overflow-hidden p-0 sm:w-[400px] [&>button]:hidden">
            <div className="flex h-full flex-col bg-white">
              <SheetHeader className="space-y-0 border-b p-4 pb-2">
                <div className="flex items-center gap-2">
                  <AlbaTecLogo variant="full" height={80} adaptive />
                  <SheetTitle className="sr-only">Alba Tec</SheetTitle>
                  <Button variant="ghost" size="icon" onClick={() => setIsOpen(false)} className="ml-auto h-8 w-8 cursor-pointer">
                    <X className="h-4 w-4" />
                  </Button>
                </div>
              </SheetHeader>

              <div className="flex-1 overflow-y-auto">
                <nav className="space-y-1 p-6">
                  {navigationItems.map((item) => (
                    <a
                      key={item.name}
                      href={item.href}
                      className="flex cursor-pointer items-center rounded-lg px-4 py-3 text-base font-medium text-zinc-800 transition-colors hover:bg-zinc-100"
                      onClick={(e) => {
                        setIsOpen(false)
                        if (item.href.startsWith('#')) {
                          e.preventDefault()
                          setTimeout(() => smoothScrollTo(item.href), 100)
                        }
                      }}
                    >
                      {item.name}
                    </a>
                  ))}
                </nav>
              </div>

              <div className="space-y-4 border-t p-6">
                <div className="grid grid-cols-2 gap-3">
                  <Link href="/auth/login" className="cursor-pointer" onClick={() => setIsOpen(false)}>
                    <Button variant="outline" size="lg" className="w-full cursor-pointer">
                      Login
                    </Button>
                  </Link>
                  <Button asChild size="lg" className="cursor-pointer rounded-md bg-zinc-900 text-white hover:bg-zinc-700">
                    <Link href="/auth/register" onClick={() => trackCTA('/auth/register')}>
                      {TRIAL_CTA_LABEL}
                    </Link>
                  </Button>
                </div>
              </div>
            </div>
          </SheetContent>
        </Sheet>
      </div>
    </header>
  )
}
