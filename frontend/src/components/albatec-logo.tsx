"use client"

import Image from "next/image"
import Link from "next/link"
import { cn } from "@/lib/utils"

/** Wordmark horizontal (símbolo + texto lado a lado) — navbar e headers */
const LOGO_HORIZONTAL_LIGHT = "/brand/logo-symbol.png"
/** Logo completa empilhada — hero, login e fundos claros */
const LOGO_FULL_LIGHT = "/brand/logo-alba-tec-sem-fundo.png"
/** Logo completa para fundos escuros */
const LOGO_FULL_DARK = "/brand/logo-alba-escuro.png"
const LOGO_SYMBOL = "/brand/icon-512.png"

const LOGO_ASPECT = {
  horizontal: 1053 / 702,
  full: 1,
} as const

export const PANEL_BRAND_ICON_SIZE = 50
export const LANDING_FOOTER_BRAND_ICON_SIZE = 56

export type AlbaTecLogoVariant = "horizontal" | "full" | "icon" | "wordmark"

interface AlbaTecLogoProps {
  variant?: AlbaTecLogoVariant
  /** Altura em pixels (largura proporcional) */
  height?: number
  /** Largura fixa para variant icon (quadrado) */
  width?: number
  className?: string
  href?: string
  priority?: boolean
  /** Caixa branca atrás da logo (fundos coloridos/fotos) */
  withBackground?: boolean
  /** Logo para fundo escuro (wordmark com "Alba" branco) */
  onDark?: boolean
  /** Alterna automaticamente entre claro/escuro conforme o tema */
  adaptive?: boolean
  /** @deprecated use onDark */
  inverted?: boolean
}

export function AlbaTecLogo({
  variant = "horizontal",
  height = 32,
  width,
  className,
  href,
  priority = false,
  withBackground = false,
  onDark = false,
  adaptive = false,
  inverted = false,
}: AlbaTecLogoProps) {
  const useDarkLogo = onDark || inverted
  const iconSize = Math.round(width ?? height)

  const isFullLogo = variant === "full"
  const logoAspect = isFullLogo ? LOGO_ASPECT.full : LOGO_ASPECT.horizontal

  const renderWordmark = (src: string, visibilityClass?: string) => {
    const logoHeight = isFullLogo ? height : Math.round(height * 1.15)
    const logoWidth = Math.round(logoHeight * logoAspect)

    return (
      <Image
        src={src}
        alt="Alba Tec"
        width={logoWidth}
        height={logoHeight}
        className={cn("h-auto w-auto object-contain", visibilityClass, className)}
        style={{ height: logoHeight, width: "auto" }}
        priority={priority}
      />
    )
  }

  const resolveWordmarkSrc = (dark: boolean) => {
    if (isFullLogo) {
      return dark ? LOGO_FULL_DARK : LOGO_FULL_LIGHT
    }

    return dark ? LOGO_FULL_DARK : LOGO_HORIZONTAL_LIGHT
  }

  const image = (() => {
    if (variant === "icon") {
      return (
        <Image
          src={LOGO_SYMBOL}
          alt="Alba Tec"
          width={iconSize}
          height={iconSize}
          className={cn("shrink-0 object-contain", className)}
          style={{
            width: iconSize,
            height: iconSize,
            minWidth: iconSize,
            minHeight: iconSize,
          }}
          priority={priority}
        />
      )
    }

    if (withBackground) {
      return renderWordmark(resolveWordmarkSrc(false))
    }

    if (adaptive) {
      return (
        <>
          {renderWordmark(resolveWordmarkSrc(false), "dark:hidden")}
          {renderWordmark(resolveWordmarkSrc(true), "hidden dark:block")}
        </>
      )
    }

    return renderWordmark(resolveWordmarkSrc(useDarkLogo))
  })()

  const content = withBackground ? (
    <span
      className={cn(
        "inline-flex items-center justify-center rounded-2xl border border-white/80 bg-white shadow-md",
        variant === "icon" ? "p-2" : "px-4 py-3"
      )}
    >
      {image}
    </span>
  ) : (
    image
  )

  if (href) {
    return (
      <Link href={href} className="inline-flex cursor-pointer items-center">
        {content}
      </Link>
    )
  }

  return content
}

/** @deprecated Use AlbaTecLogo com variant="icon" */
export function Logo({ size = 24, className }: { size?: number; className?: string }) {
  return <AlbaTecLogo variant="icon" height={size} className={className} />
}
