'use client'

import { useEffect, useRef, useState } from 'react'
import { ChevronDown } from 'lucide-react'
import { cn } from '@/lib/utils'
import { buildApiUrl } from '@/lib/api-config'

interface StoreHourSlot {
  start: string
  end: string
  delivery_type: string
}

interface StoreHoursData {
  is_open: boolean
  is_always_open: boolean
  current_time: string
  current_day: string
  store_hours: Record<string, StoreHourSlot[]>
}

interface StoreHoursBannerProps {
  slug: string
  onStatusChange?: (isOpen: boolean) => void
  className?: string
}

function formatSlots(hours: StoreHourSlot[], separator = ' e ') {
  return hours.map((hour, index) => (
    <span key={`${hour.start}-${hour.end}-${index}`}>
      {index > 0 && separator}
      {hour.start}{separator === ' e ' ? ' às ' : '-'}{hour.end}
    </span>
  ))
}

export function StoreHoursBanner({ slug, onStatusChange, className }: StoreHoursBannerProps) {
  const [hoursData, setHoursData] = useState<StoreHoursData | null>(null)
  const [loading, setLoading] = useState(true)
  const [expanded, setExpanded] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const fetchStoreHours = async () => {
      try {
        const response = await fetch(buildApiUrl(`/api/store/${slug}/is-open`))
        const data = await response.json()

        if (data.success) {
          setHoursData(data.data)
          const storeHours = data.data.store_hours ?? {}
          const hasNoHoursConfigured = Object.keys(storeHours).length === 0
          if (onStatusChange) {
            onStatusChange(
              Boolean(data.data.is_open || data.data.is_always_open || hasNoHoursConfigured)
            )
          }
        }
      } catch {
        if (onStatusChange) {
          onStatusChange(true)
        }
      } finally {
        setLoading(false)
      }
    }

    fetchStoreHours()

    const interval = setInterval(fetchStoreHours, 5 * 60 * 1000)
    return () => clearInterval(interval)
  }, [slug])

  useEffect(() => {
    if (!expanded) return
    const handleClickOutside = (event: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setExpanded(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [expanded])

  if (loading || !hoursData) {
    return null
  }

  const isOpen = hoursData.is_open || hoursData.is_always_open
  const allHours = hoursData.store_hours ?? {}
  const todayHours = allHours[hoursData.current_day] || []
  const hasHours = Object.keys(allHours).length > 0
  const canExpand = hasHours

  const toggleExpanded = () => {
    if (!canExpand) return
    setExpanded((current) => !current)
  }

  return (
    <div ref={containerRef} className={cn('relative inline-block', className)}>
      <button
        type="button"
        onClick={toggleExpanded}
        aria-expanded={canExpand ? expanded : undefined}
        disabled={!canExpand}
        className={cn(
          'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
          isOpen
            ? 'border-green-600/30 bg-green-50 text-green-700 dark:border-green-500/30 dark:bg-green-950/30 dark:text-green-400'
            : 'border-red-600/30 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-950/30 dark:text-red-400',
          canExpand ? 'cursor-pointer hover:opacity-80' : 'cursor-default'
        )}
      >
        <span className={cn('h-1.5 w-1.5 shrink-0 rounded-full', isOpen ? 'bg-green-500' : 'bg-red-500')} aria-hidden />
        <span>{isOpen ? 'Aberto' : 'Fechado'}</span>
        {canExpand && (
          <ChevronDown
            className={cn('h-3 w-3 shrink-0 transition-transform duration-200', expanded && 'rotate-180')}
            aria-hidden
          />
        )}
        {canExpand && (
          <span className="sr-only">{expanded ? 'Ocultar horários' : 'Ver horários'}</span>
        )}
      </button>

      {expanded && canExpand && (
        <div className="absolute left-0 top-full z-50 mt-2 w-72 max-w-[90vw] rounded-xl border bg-popover p-3 text-sm text-popover-foreground shadow-lg">
          {hoursData.is_always_open && (
            <p className="text-muted-foreground">Aceitamos pedidos 24 horas</p>
          )}

          {isOpen && !hoursData.is_always_open && todayHours.length > 0 && (
            <p className="text-muted-foreground">
              Horários de hoje: {formatSlots(todayHours)}
            </p>
          )}

          {!isOpen && (
            <p className="text-muted-foreground">
              Atualmente estamos fora do horário de atendimento.
            </p>
          )}

          <div className="mt-2 rounded-lg bg-muted/50 p-2.5">
            <p className="mb-1.5 font-semibold">Horários de funcionamento:</p>
            <div className="space-y-1 text-xs">
              {Object.entries(allHours).map(([day, hours]) => (
                <div key={day} className="flex items-center justify-between gap-3">
                  <span className="font-medium">{day}:</span>
                  <span className="text-muted-foreground">{formatSlots(hours, ', ')}</span>
                </div>
              ))}
            </div>
          </div>

          {!isOpen && (
            <p className="mt-2 text-xs text-muted-foreground">
              Você pode adicionar produtos ao carrinho, mas só poderá finalizar quando estivermos abertos.
            </p>
          )}
        </div>
      )}
    </div>
  )
}
