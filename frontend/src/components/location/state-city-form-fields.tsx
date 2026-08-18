"use client"

import { Control, FieldValues, Path, useWatch } from "react-hook-form"
import { useEffect } from "react"
import { useStates, useCitiesByState } from "@/hooks/use-location"
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

interface StateCityFormFieldsProps<T extends FieldValues> {
  control: Control<T>
  stateFieldName: Path<T>
  cityFieldName: Path<T>
  stateLabel?: string
  cityLabel?: string
  required?: boolean
  disabled?: boolean
  onStateChange?: (value: string) => void
  gridCols?: "equal" | "state-small"
}

export function StateCityFormFields<T extends FieldValues>({
  control,
  stateFieldName,
  cityFieldName,
  stateLabel = "Estado",
  cityLabel = "Cidade",
  required = false,
  disabled = false,
  onStateChange,
  gridCols = "state-small",
}: StateCityFormFieldsProps<T>) {
  const { states, loading: loadingStates } = useStates()

  const stateField = useWatch({
    control,
    name: stateFieldName,
  })

  const cityField = useWatch({
    control,
    name: cityFieldName,
  })

  const { cities, loading: loadingCities } = useCitiesByState(stateField || null)

  useEffect(() => {
    if (stateField && onStateChange) {
      onStateChange(stateField)
    }
  }, [stateField, onStateChange])

  useEffect(() => {
    if (stateField && cityField && cities.length > 0) {
      const cityExists = cities.some((city) => city.name === cityField)
      if (!cityExists) {
        ;(control._formState.dirtyFields as Record<string, boolean>)[cityFieldName as string] = true
      }
    }
  }, [stateField, cities, cityField, cityFieldName, control])

  return (
    <>
      <FormField
        control={control}
        name={stateFieldName}
        render={({ field }) => (
          <FormItem className={gridCols === "state-small" ? "" : ""}>
            <FormLabel>
              {stateLabel} {required && <span className="text-red-500">*</span>}
            </FormLabel>
            <Select
              value={field.value || undefined}
              onValueChange={(value) => {
                if (value === '_loading' || value === '_empty') return
                field.onChange(value)
              }}
              disabled={disabled || loadingStates}
            >
              <FormControl>
                <SelectTrigger>
                  <SelectValue placeholder="Selecione" />
                </SelectTrigger>
              </FormControl>
              <SelectContent className="max-h-[300px]">
                {loadingStates && (
                  <SelectItem value="_loading" disabled>
                    Carregando...
                  </SelectItem>
                )}
                {field.value && !states.some((state) => state.uf === field.value) && (
                  <SelectItem value={field.value}>{field.value}</SelectItem>
                )}
                {states.map((state) => (
                  <SelectItem key={state.id} value={state.uf}>
                    {state.name} ({state.uf})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <FormMessage />
          </FormItem>
        )}
      />

      <FormField
        control={control}
        name={cityFieldName}
        render={({ field }) => (
          <FormItem className={gridCols === "state-small" ? "md:col-span-2" : ""}>
            <FormLabel>
              {cityLabel} {required && <span className="text-red-500">*</span>}
            </FormLabel>
            <Select
              value={field.value || undefined}
              onValueChange={(value) => {
                if (value === '_loading' || value === '_empty') return
                field.onChange(value)
              }}
              disabled={disabled || !stateField}
            >
              <FormControl>
                <SelectTrigger>
                  <SelectValue
                    placeholder={
                      !stateField
                        ? "Selecione um estado primeiro"
                        : loadingCities
                          ? "Carregando..."
                          : "Selecione"
                    }
                  />
                </SelectTrigger>
              </FormControl>
              <SelectContent className="max-h-[300px]">
                {field.value && !cities.some((city) => city.name === field.value) && (
                  <SelectItem value={field.value}>{field.value}</SelectItem>
                )}
                {loadingCities && cities.length === 0 && !field.value && (
                  <SelectItem value="_loading" disabled>
                    Carregando...
                  </SelectItem>
                )}
                {cities.map((city) => (
                  <SelectItem key={city.id} value={city.name}>
                    {city.name}
                    {city.is_capital && " (Capital)"}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <FormMessage />
          </FormItem>
        )}
      />
    </>
  )
}
