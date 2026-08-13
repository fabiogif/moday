import type { Metadata } from 'next'

/** Caminhos oficiais dos ícones da marca Alba Tec (símbolo "A"). */
export const SITE_ICONS = {
  symbol512: '/brand/icon-512.png',
  symbol192: '/brand/icon-192.png',
  favicon64: '/brand/favicon-64.png',
  favicon32: '/favicon-32.png',
  appleTouch: '/brand/apple-touch-icon.png',
} as const

export const SITE_ICON_METADATA: NonNullable<Metadata['icons']> = {
  icon: [
    { url: SITE_ICONS.favicon32, sizes: '32x32', type: 'image/png' },
    { url: SITE_ICONS.favicon64, sizes: '64x64', type: 'image/png' },
    { url: SITE_ICONS.symbol192, sizes: '192x192', type: 'image/png' },
    { url: SITE_ICONS.symbol512, sizes: '512x512', type: 'image/png' },
  ],
  apple: [
    { url: SITE_ICONS.appleTouch, sizes: '180x180', type: 'image/png' },
  ],
  shortcut: SITE_ICONS.favicon64,
}
