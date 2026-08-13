"use client"

import { Input } from "@/components/ui/input"
import { Loader2 } from "lucide-react"
import { useViaCEP } from "@/hooks/use-viacep"
import { maskZipCode } from "@/lib/masks"
import { StateCitySelect } from "@/components/location/state-city-select"
import { applyCepToStateHandlers } from "@/lib/apply-cep-to-form"

interface DeliveryAddress {
  zip: string
  address: string
  number: string
  neighborhood: string
  city: string
  state: string
  complement: string
}

interface DeliveryAddressFormProps {
  address: DeliveryAddress
  onAddressChange: (address: DeliveryAddress) => void
  className?: string
  disabled?: boolean
}

export function DeliveryAddressForm({
  address,
  onAddressChange,
  className,
  disabled = false,
}: DeliveryAddressFormProps) {
  const { searchCEP, loading: loadingCEP } = useViaCEP()

  const handleZipChange = async (zip: string) => {
    const maskedZip = maskZipCode(zip)
    const newAddress = { ...address, zip: maskedZip }
    onAddressChange(newAddress)

    if (maskedZip.replace(/\D/g, "").length === 8) {
      try {
        const cepData = await searchCEP(maskedZip.replace(/\D/g, ""))
        if (cepData) {
          const next = { ...newAddress }
          applyCepToStateHandlers(cepData, {
            setAddress: (v) => {
              next.address = v
            },
            setNeighborhood: (v) => {
              next.neighborhood = v
            },
            setState: (v) => {
              next.state = v
              onAddressChange({ ...next })
            },
            setCity: (v) => {
              next.city = v
              onAddressChange({ ...next })
            },
          })
        }
      } catch (error) {
        console.error("Erro ao buscar CEP:", error)
      }
    }
  }

  return (
    <div className={className}>
      <div className="space-y-2 rounded-lg border border-gray-200 bg-gray-50/30 dark:bg-gray-900/20 p-2">
        <p className="text-xs font-medium text-gray-500 dark:text-gray-500">Endereço (CEP):</p>
        <div className="grid gap-2">
          <div className="space-y-1">
            <div className="relative">
              <Input
                placeholder="40325-465"
                value={address.zip}
                onChange={(e) => handleZipChange(e.target.value)}
                className="h-9 text-sm pr-10 bg-white dark:bg-gray-800"
                disabled={disabled || loadingCEP}
                maxLength={9}
              />
              {loadingCEP && (
                <div className="absolute right-3 top-1/2 -translate-y-1/2">
                  <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                </div>
              )}
            </div>
            {loadingCEP && (
              <p className="text-[10px] text-muted-foreground">
                Buscando endereço...
              </p>
            )}
          </div>

          <Input
            placeholder="Endereço"
            value={address.address}
            onChange={(e) =>
              onAddressChange({ ...address, address: e.target.value })
            }
            className="h-9 text-sm bg-white dark:bg-gray-800"
            disabled={disabled}
          />

          <div className="grid gap-2 sm:grid-cols-2">
            <Input
              placeholder="Número"
              value={address.number}
              onChange={(e) =>
                onAddressChange({ ...address, number: e.target.value })
              }
              className="h-9 text-sm bg-white dark:bg-gray-800"
              disabled={disabled}
            />
            <Input
              placeholder="Bairro"
              value={address.neighborhood}
              onChange={(e) =>
                onAddressChange({ ...address, neighborhood: e.target.value })
              }
              className="h-9 text-sm bg-white dark:bg-gray-800"
              disabled={disabled}
            />
          </div>

          <StateCitySelect
            stateValue={address.state}
            cityValue={address.city}
            onStateChange={(value) =>
              onAddressChange({ ...address, state: value, city: "" })
            }
            onCityChange={(value) =>
              onAddressChange({ ...address, city: value })
            }
            disabled={disabled}
            className="gap-2"
            fieldClassName="space-y-1"
            labelClassName="sr-only"
            triggerClassName="h-9 text-sm bg-white dark:bg-gray-800"
          />

          <Input
            placeholder="Complemento (opcional)"
            value={address.complement}
            onChange={(e) =>
              onAddressChange({ ...address, complement: e.target.value })
            }
            className="h-9 text-sm bg-white dark:bg-gray-800"
            disabled={disabled}
          />
        </div>
      </div>
    </div>
  )
}
