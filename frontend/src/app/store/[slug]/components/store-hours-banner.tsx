'use client'

import { useEffect, useState } from 'react'
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
}

function formatSlots(hours: StoreHourSlot[], separator = ' e ') {
  return hours.map((hour, index) => (
    <span key={`${hour.start}-${hour.end}-${index}`}>
      {index > 0 && separator}
      {hour.start}{separator === ' e ' ? ' às ' : '-'}{hour.end}
    </span>
  ))
}

export function StoreHoursBanner({ slug, onStatusChange }: StoreHoursBannerProps) {
  const [hoursData, setHoursData] = useState<StoreHoursData | null>(null)
  const [loading, setLoading] = useState(true)
  const [expanded, setExpanded] = useState(false)

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

  if (loading || !hoursData) {
    return null
  }

  const isOpen = hoursData.is_open || hoursData.is_always_open
  const allHours = hoursData.store_hours ?? {}
  const todayHours = allHours[hoursData.current_day] || []
  const hasHours = Object.keys(allHours).length > 0
  const canExpand = hasHours
  const statusLabel = isOpen ? '🟢 Restaurante aberto' : '🔴 Restaurante fechado no momento'

  const toggleExpanded = () => {
    if (!canExpand) return
    setExpanded((current) => !current)
  }

  return (
    <div
      className={cn(
        'px-4 text-white transition-[padding] duration-500 ease-in-out',
        isOpen ? 'bg-green-500' : 'bg-red-500',
        expanded ? 'py-3' : 'py-2'
      )}
    >
      <div className="container mx-auto">
        <div className="flex flex-col items-center justify-center text-center">
          {canExpand ? (
            <button
              type="button"
              onClick={toggleExpanded}
              aria-expanded={expanded}
              className="flex items-center gap-2 rounded-md font-medium outline-none transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:ring-white/70"
            >
              <span className={cn(isOpen ? 'text-base' : 'text-lg font-bold')}>
                {statusLabel}
              </span>
              <ChevronDown
                className={cn(
                  'h-4 w-4 shrink-0 transition-transform duration-300',
                  expanded && 'rotate-180'
                )}
                aria-hidden
              />
              <span className="sr-only">
                {expanded ? 'Ocultar horários' : 'Ver horários'}
              </span>
            </button>
          ) : (
            <span className={cn('font-medium', !isOpen && 'text-lg font-bold')}>
              {statusLabel}
            </span>
          )}

          <div
            className={cn(
              'grid w-full transition-[grid-template-rows] duration-500 ease-in-out',
              expanded ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'
            )}
          >
            <div className="overflow-hidden">
              <div className="flex flex-col items-center gap-3 pt-3">
                {hoursData.is_always_open && (
                  <p className="text-sm opacity-90">Aceitamos pedidos 24 horas</p>
                )}

                {isOpen && !hoursData.is_always_open && todayHours.length > 0 && (
                  <p className="text-sm">
                    Horários de hoje: {formatSlots(todayHours)}
                  </p>
                )}

                {!isOpen && (
                  <p className="text-sm opacity-90">
                    Atualmente estamos fora do horário de atendimento.
                  </p>
                )}

                {hasHours && (
                  <div className="mt-1 w-full max-w-2xl rounded-lg bg-white/10 p-3">
                    <p className="mb-2 font-semibold">Nossos horários de funcionamento:</p>
                    <div className="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                      {Object.entries(allHours).map(([day, hours]) => (
                        <div key={day} className="flex items-center justify-between gap-3">
                          <span className="font-medium">{day}:</span>
                          <span>{formatSlots(hours, ', ')}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {!isOpen && (
                  <p className="mt-1 text-xs opacity-75">
                    Você pode adicionar produtos ao carrinho, mas só poderá finalizar quando estivermos abertos.
                  </p>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
