"use client"

import Image from 'next/image'
import { cn } from '@/lib/utils'

interface LandingScreenshotProps {
  src: string
  alt: string
  className?: string
  priority?: boolean
  glowClassName?: string
}

export function LandingScreenshot({
  src,
  alt,
  className,
  priority = false,
  glowClassName = 'from-orange-200/40 to-violet-200/30',
}: LandingScreenshotProps) {
  return (
    <div className={cn('relative', className)}>
      <div
        className={cn(
          'absolute inset-0 bg-gradient-to-r rounded-3xl blur-3xl -z-10',
          glowClassName
        )}
      />
      <div className="rounded-2xl border border-zinc-200/80 overflow-hidden bg-white shadow-lg shadow-zinc-200/50">
        <Image
          src={src}
          alt={alt}
          width={1200}
          height={900}
          priority={priority}
          loading={priority ? undefined : 'lazy'}
          className="block h-auto w-full bg-white"
        />
      </div>
    </div>
  )
}
