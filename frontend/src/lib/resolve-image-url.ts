import { getApiBaseUrl } from "@/lib/api-config"

const STALE_HOSTS = new Set(["localhost", "127.0.0.1"])

/**
 * Converte URLs absolutas com host de dev (localhost) em caminho relativo /storage/...
 */
function rewriteStaleAbsoluteUrl(url: string): string {
  if (!url.startsWith("http://") && !url.startsWith("https://")) {
    return url
  }

  try {
    const parsed = new URL(url)
    if (STALE_HOSTS.has(parsed.hostname)) {
      return parsed.pathname + parsed.search
    }
  } catch {
    // mantém URL original se não for parseável
  }

  return url
}

/**
 * Converte path relativo do banco (ex.: tenants/{uuid}/products/foo.png) em URL pública /storage/...
 */
function toPublicStoragePath(value: string): string | null {
  const normalized = value.replace(/^\/+/, "")

  if (normalized.startsWith("storage/products/")) {
    return `/${normalized}`
  }
  if (normalized.startsWith("storage/logos/")) {
    return `/${normalized}`
  }
  // URL antiga incorreta: /storage/tenants/{uuid}/products/...
  if (/^storage\/tenants\/[^/]+\/products\//.test(normalized)) {
    return `/storage/products/${normalized.replace(/^storage\//, "")}`
  }
  if (/^storage\/tenants\/[^/]+\/logos\//.test(normalized)) {
    return `/storage/logos/${normalized.replace(/^storage\//, "")}`
  }
  if (normalized.startsWith("storage/")) {
    return `/${normalized}`
  }

  if (/^tenants\/[^/]+\/products\//.test(normalized)) {
    return `/storage/products/${normalized}`
  }
  if (/^tenants\/[^/]+\/logos\//.test(normalized)) {
    return `/storage/logos/${normalized}`
  }

  // Cupons e anexos no disco public (storage/app/public/coupons, etc.)
  if (
    normalized.startsWith("coupons/") ||
    normalized.startsWith("expenses/") ||
    normalized.startsWith("documents/")
  ) {
    return `/storage/${normalized}`
  }

  return null
}

function resolveAssetBase(): string {
  const apiBase = getApiBaseUrl()
  if (apiBase) {
    return apiBase
  }
  if (typeof window !== "undefined") {
    return window.location.origin
  }
  return ""
}

/**
 * Normaliza URLs de imagens vindas da API.
 * Aceita URLs absolutas, relativas ou apenas nomes de arquivos.
 */
export function resolveImageUrl(image?: string | null): string | null {
  if (!image) {
    return null
  }

  let trimmed = image.trim()
  if (trimmed === "") {
    return null
  }

  trimmed = rewriteStaleAbsoluteUrl(trimmed)

  if (trimmed.startsWith("data:")) {
    return trimmed
  }

  const publicPath = toPublicStoragePath(trimmed)
  if (publicPath) {
    trimmed = publicPath
  }

  const assetBase = resolveAssetBase()

  if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) {
    return trimmed
  }

  if (trimmed.startsWith("/storage/") || trimmed.startsWith("/")) {
    return assetBase ? `${assetBase}${trimmed}` : trimmed
  }

  if (!assetBase) {
    return trimmed.startsWith("storage/") ? `/${trimmed}` : `/storage/${trimmed}`
  }

  const sanitized = trimmed.replace(/^\/+/, "")

  if (sanitized.startsWith("storage/")) {
    return `${assetBase}/${sanitized}`
  }

  return `${assetBase}/storage/${sanitized}`
}
