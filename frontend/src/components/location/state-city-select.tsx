"use client"

import { useEffect } from "react"
import { useStates, useCitiesByState } from "@/hooks/use-location"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import { cn } from "@/lib/utils"

interface StateCitySelectProps {
  stateValue?: string
  cityValue?: string
  onStateChange: (value: string) => void
  onCityChange: (value: string) => void
  stateError?: string
  cityError?: string
  disabled?: boolean
  required?: boolean
  className?: string
  fieldClassName?: string
  labelClassName?: string
  triggerClassName?: string
}

export function StateCitySelect({
  stateValue,
  cityValue,
  onStateChange,
  onCityChange,
  stateError,
  cityError,
  disabled = false,
  required = false,
  className,
  fieldClassName,
  labelClassName,
  triggerClassName,
}: StateCitySelectProps) {
  const { states, loading: loadingStates } = useStates()
  const { cities, loading: loadingCities } = useCitiesByState(stateValue || null)

  useEffect(() => {
    if (stateValue && cityValue && cities.length > 0) {
      const cityExists = cities.some((city) => city.name === cityValue)
      if (!cityExists) {
        const timer = setTimeout(() => {
          const stillNotExists = !cities.some((city) => city.name === cityValue)
          if (stillNotExists) {
            onCityChange("")
          }
        }, 100)
        return () => clearTimeout(timer)
      }
    }
  }, [stateValue, cities, cityValue, onCityChange])

  return (
    <div className={cn("grid grid-cols-1 gap-4 md:grid-cols-3", className)}>
      <div className={cn("space-y-2", fieldClassName)}>
        <label className={cn("text-sm font-medium", labelClassName)}>
          Estado {required && <span className="text-red-500">*</span>}
        </label>
        {loadingStates ? (
          <Skeleton className="h-10 w-full" />
        ) : (
          <Select
            value={stateValue}
            onValueChange={onStateChange}
            disabled={disabled || loadingStates}
          >
            <SelectTrigger className={cn(triggerClassName, stateError && "border-red-500")}>
              <SelectValue placeholder="Selecione o estado" />
            </SelectTrigger>
            <SelectContent>
              {!states || states.length === 0 ? (
                <SelectItem value="_empty" disabled>
                  Nenhum estado disponível
                </SelectItem>
              ) : (
                states.map((state) => (
                  <SelectItem key={state.id} value={state.uf}>
                    {state.name} ({state.uf})
                  </SelectItem>
                ))
              )}
            </SelectContent>
          </Select>
        )}
        {stateError && <p className="text-sm text-red-500">{stateError}</p>}
      </div>

      <div className={cn("space-y-2 md:col-span-2", fieldClassName)}>
        <label className={cn("text-sm font-medium", labelClassName)}>
          Cidade {required && <span className="text-red-500">*</span>}
        </label>
        {loadingCities ? (
          <Skeleton className="h-10 w-full" />
        ) : (
          <Select
            value={cityValue}
            onValueChange={onCityChange}
            disabled={disabled || !stateValue || loadingCities}
          >
            <SelectTrigger className={cn(triggerClassName, cityError && "border-red-500")}>
              <SelectValue
                placeholder={
                  !stateValue
                    ? "Primeiro selecione um estado"
                    : cities.length === 0
                      ? "Carregando..."
                      : "Selecione a cidade"
                }
              />
            </SelectTrigger>
            <SelectContent>
              {!cities || cities.length === 0 ? (
                <SelectItem value="_empty" disabled>
                  Nenhuma cidade disponível
                </SelectItem>
              ) : (
                cities.map((city) => (
                  <SelectItem key={city.id} value={city.name}>
                    {city.name}
                    {city.is_capital && " (Capital)"}
                  </SelectItem>
                ))
              )}
            </SelectContent>
          </Select>
        )}
        {cityError && <p className="text-sm text-red-500">{cityError}</p>}
      </div>
    </div>
  )
}
