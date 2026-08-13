"use client"

import { ReactNode } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { ProductOptional, ProductVariation } from "@/types/product-variations"
import { ComboboxOption } from "@/components/ui/combobox"
import type { ProductFormValues } from "./product-form-wizard"

function formatMoney(value: number | undefined) {
  return new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(value ?? 0)
}

function display(value: string | number | undefined | null) {
  if (value === undefined || value === null || value === "") return "—"
  return String(value)
}

function ReviewRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 py-1">
      <span className="text-muted-foreground">{label}</span>
      <span className="font-medium text-right break-words max-w-[60%]">{value}</span>
    </div>
  )
}

function ReviewSection({
  title,
  onEdit,
  children,
}: {
  title: string
  onEdit: () => void
  children: ReactNode
}) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-start justify-between space-y-0 gap-2">
        <CardTitle className="text-base">{title}</CardTitle>
        <Button type="button" variant="link" className="h-auto p-0 shrink-0" onClick={onEdit}>
          Editar
        </Button>
      </CardHeader>
      <CardContent className="text-sm space-y-0.5">{children}</CardContent>
    </Card>
  )
}

interface ProductFormReviewProps {
  values: ProductFormValues
  categoryOptions: ComboboxOption[]
  variations: ProductVariation[]
  optionals: ProductOptional[]
  currentImage: string | null
  onEditStep: (step: number) => void
}

export function ProductFormReview({
  values,
  categoryOptions,
  variations,
  optionals,
  currentImage,
  onEditStep,
}: ProductFormReviewProps) {
  const categoryLabels =
    values.categories
      ?.map((id) => categoryOptions.find((option) => option.value === id)?.label || id)
      .join(", ") || "—"

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle>Revisão</CardTitle>
          <CardDescription>
            Confira os dados antes de salvar. Use Editar para voltar a qualquer seção sem perder o restante.
          </CardDescription>
        </CardHeader>
      </Card>

      <ReviewSection title="Informações Básicas" onEdit={() => onEditStep(0)}>
        <ReviewRow label="Nome" value={display(values.name)} />
        <ReviewRow label="Descrição" value={display(values.description)} />
        <ReviewRow label="Marca" value={display(values.brand)} />
        <ReviewRow label="SKU" value={display(values.sku)} />
        <ReviewRow label="Categorias" value={categoryLabels} />
      </ReviewSection>

      <ReviewSection title="Preços e Estoque" onEdit={() => onEditStep(1)}>
        <ReviewRow label="Custo" value={formatMoney(values.price_cost)} />
        <ReviewRow label="Venda" value={formatMoney(values.price)} />
        <ReviewRow label="Promocional" value={values.promotional_price ? formatMoney(values.promotional_price) : "—"} />
        <ReviewRow label="Estoque" value={display(values.qtd_stock)} />
      </ReviewSection>

      <ReviewSection title="Logística" onEdit={() => onEditStep(2)}>
        <ReviewRow label="Peso" value={values.weight != null ? `${values.weight} kg` : "—"} />
        <ReviewRow label="Dimensões" value={
          values.height || values.width || values.depth
            ? `${display(values.height)} × ${display(values.width)} × ${display(values.depth)} cm`
            : "—"
        } />
        <ReviewRow label="Localização" value={display(values.warehouse_location)} />
        <ReviewRow label="Envio" value={display(values.shipping_info)} />
      </ReviewSection>

      <ReviewSection title="Imagem" onEdit={() => onEditStep(3)}>
        <ReviewRow label="Arquivo" value={currentImage || values.image ? "Imagem selecionada" : "Sem imagem"} />
      </ReviewSection>

      <ReviewSection title="Variações e Opcionais" onEdit={() => onEditStep(4)}>
        <ReviewRow
          label="Variações"
          value={variations.length ? variations.map((item) => item.name).join(", ") : "Nenhuma"}
        />
        <ReviewRow
          label="Opcionais"
          value={optionals.length ? optionals.map((item) => item.name).join(", ") : "Nenhum"}
        />
      </ReviewSection>
    </div>
  )
}
