"use client"

import * as React from "react"
import { Check, ChevronsUpDown } from "lucide-react"

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"

export interface ComboboxOption {
  value: string
  label: string
  disabled?: boolean
}

export interface ComboboxProps {
  options: ComboboxOption[]
  value?: string
  onValueChange?: (value: string) => void
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  className?: string
  disabled?: boolean
  allowClear?: boolean
}

export function Combobox({
  options,
  value,
  onValueChange,
  placeholder = "Selecione uma opção...",
  searchPlaceholder = "Buscar...",
  emptyText = "Nenhuma opção encontrada.",
  className,
  disabled = false,
  allowClear = false,
}: ComboboxProps) {
  const [open, setOpen] = React.useState(false)
  const [searchValue, setSearchValue] = React.useState("")

  // Filtrar opções baseado na busca
  const filteredOptions = React.useMemo(() => {
    if (!searchValue) return options;
    return options.filter(option => 
      option.label.toLowerCase().includes(searchValue.toLowerCase()) ||
      option.value.toLowerCase().includes(searchValue.toLowerCase())
    );
  }, [options, searchValue]);

  const selectedOption = options.find((option) => option.value === value)

  const handleSelect = (currentValue: string) => {
    if (currentValue === value && allowClear) {
      onValueChange?.("")
    } else {
      onValueChange?.(currentValue)
    }
    setOpen(false)
    setSearchValue("") // Limpar busca após seleção
  }

  // Log para debug
  React.useEffect(() => {
    
  }, [options, filteredOptions, value, selectedOption, disabled, searchValue]);

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className={cn(
            "w-full justify-between",
            !selectedOption && "text-muted-foreground",
            disabled && "cursor-not-allowed opacity-50",
            className
          )}
          disabled={disabled}
        >
          {selectedOption ? selectedOption.label : placeholder}
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-full p-0" align="start">
        <Command shouldFilter={false}>
          <CommandInput 
            placeholder={searchPlaceholder} 
            className="h-9"
            value={searchValue}
            onValueChange={setSearchValue}
          />
          <CommandList>
            {filteredOptions.length === 0 ? (
              <CommandEmpty>
                {options.length === 0 ? 
                  (disabled ? "Carregando..." : "Nenhuma opção disponível") : 
                  emptyText
                }
              </CommandEmpty>
            ) : (
              <CommandGroup>
                {filteredOptions
                  .filter((option) => option.value != null && option.value !== undefined)
                  .map((option, index) => (
                  <CommandItem
                    key={option.value || `option-${index}`}
                    value={option.value}
                    onSelect={handleSelect}
                    disabled={option.disabled}
                    className={cn(
                      option.disabled && "opacity-50 cursor-not-allowed"
                    )}
                  >
                    {option.label}
                    <Check
                      className={cn(
                        "ml-auto h-4 w-4",
                        value === option.value ? "opacity-100" : "opacity-0"
                      )}
                    />
                  </CommandItem>
                ))}
              </CommandGroup>
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  )
}

// Combobox com suporte a React Hook Form
export interface ComboboxFormProps extends Omit<ComboboxProps, 'value' | 'onValueChange'> {
  field?: {
    value?: string
    onChange?: (value: string) => void
    onBlur?: () => void
    name?: string
  }
}

export function ComboboxForm({
  field,
  ...props
}: ComboboxFormProps) {
  // Melhor tratamento de valores undefined/null
  const safeValue = field?.value || "";
  const safeOnChange = field?.onChange || (() => {});

  // Log para debug do ComboboxForm
  React.useEffect(() => {
    if (process.env.NODE_ENV === 'development') {

    }
  }, [field?.value, safeValue, field?.onChange, field?.name, props.options?.length]);

  return (
    <Combobox
      {...props}
      value={safeValue}
      onValueChange={safeOnChange}
    />
  )
}
