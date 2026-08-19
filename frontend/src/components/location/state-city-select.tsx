"use client"

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
            value={stateValue || undefined}
            onValueChange={(value) => {
              // O <select> nativo espelhado pelo Radix pode disparar um
              // onValueChange('') espúrio quando a option selecionada é
              // trocada de identidade (ex.: item de fallback vira item real
              // assim que a lista de estados/cidades termina de carregar).
              if (!value || value === '_loading' || value === '_empty') return
              onStateChange(value)
            }}
            disabled={disabled || loadingStates}
          >
            <SelectTrigger className={cn(triggerClassName, stateError && "border-red-500")}>
              <SelectValue placeholder="Selecione o estado" />
            </SelectTrigger>
            <SelectContent>
              {stateValue && !states.some((state) => state.uf === stateValue) && (
                <SelectItem value={stateValue}>{stateValue}</SelectItem>
              )}
              {states.length > 0 ? (
                states.map((state) => (
                  <SelectItem key={state.id} value={state.uf}>
                    {state.name} ({state.uf})
                  </SelectItem>
                ))
              ) : !stateValue ? (
                <SelectItem value="_empty" disabled>
                  Nenhum estado disponível
                </SelectItem>
              ) : null}
            </SelectContent>
          </Select>
        )}
        {stateError && <p className="text-sm text-red-500">{stateError}</p>}
      </div>

      <div className={cn("space-y-2 md:col-span-2", fieldClassName)}>
        <label className={cn("text-sm font-medium", labelClassName)}>
          Cidade {required && <span className="text-red-500">*</span>}
        </label>
        {loadingCities && !cityValue ? (
          <Skeleton className="h-10 w-full" />
        ) : (
          <Select
            value={cityValue || undefined}
            onValueChange={(value) => {
              if (!value || value === '_loading' || value === '_empty') return
              onCityChange(value)
            }}
            disabled={disabled || !stateValue}
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
              {cityValue && !cities.some((city) => city.name === cityValue) && (
                <SelectItem value={cityValue}>{cityValue}</SelectItem>
              )}
              {cities.length > 0 ? (
                cities.map((city) => (
                  <SelectItem key={city.id} value={city.name}>
                    {city.name}
                    {city.is_capital && " (Capital)"}
                  </SelectItem>
                ))
              ) : !cityValue ? (
                <SelectItem value="_empty" disabled>
                  Nenhuma cidade disponível
                </SelectItem>
              ) : null}
            </SelectContent>
          </Select>
        )}
        {cityError && <p className="text-sm text-red-500">{cityError}</p>}
      </div>
    </div>
  )
}
