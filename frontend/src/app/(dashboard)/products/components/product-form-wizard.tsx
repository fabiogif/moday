"use client";

import { useState, useEffect, useRef } from "react";
import { UseFormReturn } from "react-hook-form";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { ComboboxForm, ComboboxOption } from "@/components/ui/combobox";
import { ArrowLeft, ChevronLeft, ChevronRight, Loader2, Info, DollarSign, Package, ImageIcon, Layers } from "lucide-react";
import { OrderStepper } from "@/components/order-stepper";
import { ProductVariationsManager } from "@/components/product-variations-manager";
import { ProductOptionalsManager } from "@/components/product-optionals-manager";
import { ProductVariation, ProductOptional } from "@/types/product-variations";
import { ImageDropzone } from "@/components/image-dropzone";

export interface ProductFormValues {
  name: string;
  description: string;
  price: number;
  price_cost: number;
  promotional_price?: number;
  brand?: string;
  sku?: string;
  weight?: number;
  height?: number;
  width?: number;
  depth?: number;
  shipping_info?: string;
  warehouse_location?: string;
  categories: string[];
  qtd_stock: number;
  image?: File;
  is_active?: boolean;
}

const STEPS = [
  { label: "Informações Básicas", icon: Info },
  { label: "Preços e Estoque", icon: DollarSign },
  { label: "Logística", icon: Package },
  { label: "Imagem", icon: ImageIcon },
  { label: "Variações e Opcionais", icon: Layers },
];

const STEP_FIELDS: (keyof ProductFormValues)[][] = [
  ["name", "description", "categories"],
  ["price_cost", "price", "qtd_stock"],
  [],
  [],
  [],
];

// Passo em que cada campo aparece — usado para levar o usuário até o erro retornado pelo backend
export const PRODUCT_FIELD_STEP: Partial<Record<keyof ProductFormValues, number>> = {
  name: 0,
  description: 0,
  brand: 0,
  sku: 0,
  categories: 0,
  is_active: 0,
  price_cost: 1,
  price: 1,
  promotional_price: 1,
  qtd_stock: 1,
  weight: 2,
  height: 2,
  width: 2,
  depth: 2,
  warehouse_location: 2,
  shipping_info: 2,
  image: 3,
};

function isFilled(value: unknown) {
  return typeof value === "string" ? value.trim().length > 0 : value != null && value !== "";
}


interface ProductFormWizardProps {
  mode: "create" | "edit";
  title: string;
  description: string;
  breadcrumb?: React.ReactNode;
  form: UseFormReturn<ProductFormValues>;
  categoryOptions: ComboboxOption[];
  variations: ProductVariation[];
  onVariationsChange: (variations: ProductVariation[]) => void;
  optionals: ProductOptional[];
  onOptionalsChange: (optionals: ProductOptional[]) => void;
  currentImage: string | null;
  onImageChange: (file: File) => void;
  submitting: boolean;
  submitLabel: string;
  onSubmit: (data: ProductFormValues) => void | Promise<void>;
  onCancel: () => void;
}

export function ProductFormWizard({
  mode,
  title,
  description,
  breadcrumb,
  form,
  categoryOptions,
  variations,
  onVariationsChange,
  optionals,
  onOptionalsChange,
  currentImage,
  onImageChange,
  submitting,
  submitLabel,
  onSubmit,
  onCancel,
}: ProductFormWizardProps) {
  const [currentStep, setCurrentStep] = useState(0);
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set());
  const ignoreSaveClickRef = useRef(false);

  const name = form.watch("name");
  const description_ = form.watch("description");
  const categories = form.watch("categories");

  const canContinue =
    STEP_FIELDS[currentStep].length === 0 ||
    (currentStep === 0 ? isFilled(name) && isFilled(description_) && (categories?.length ?? 0) > 0 : true);

  const isLastStep = currentStep === STEPS.length - 1;

  const goNext = async () => {
    const fields = STEP_FIELDS[currentStep];
    const valid = fields.length === 0 || (await form.trigger(fields));
    if (!valid) return;

    const leavingPenultimate = currentStep === STEPS.length - 2;
    if (leavingPenultimate) {
      ignoreSaveClickRef.current = true;
    }

    setCompletedSteps((prev) => new Set(prev).add(currentStep));
    setCurrentStep((s) => Math.min(s + 1, STEPS.length - 1));

    if (leavingPenultimate) {
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          ignoreSaveClickRef.current = false;
        });
      });
    }
  };



  const goBack = () => setCurrentStep((s) => Math.max(s - 1, 0));

  const goToStep = (step: number) => {
    if (step <= currentStep || completedSteps.has(step)) {
      setCurrentStep(step);
    }
  };

  // Se um erro (frontend ou backend) surgir num campo de um passo anterior, leva o usuário até lá
  useEffect(() => {
    const erroredFields = Object.keys(form.formState.errors) as (keyof ProductFormValues)[];
    if (erroredFields.length === 0) return;
    const steps = erroredFields
      .map((f) => PRODUCT_FIELD_STEP[f])
      .filter((s): s is number => s !== undefined);
    if (steps.length === 0) return;
    const earliest = Math.min(...steps);
    setCurrentStep((current) => (earliest < current ? earliest : current));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [form.formState.errors]);


  return (
    <div className="flex flex-col min-h-[calc(100vh-4rem)] py-2 px-3 sm:px-6">
      {breadcrumb}

      <div className="flex items-center gap-3 mb-4">
        <Button variant="ghost" size="icon" onClick={onCancel} className="shrink-0">
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-lg sm:text-2xl font-bold">{title}</h1>
          <p className="hidden sm:block text-sm text-muted-foreground">{description}</p>
        </div>
      </div>

      <div className="mb-6 px-1 overflow-x-auto">
        <OrderStepper currentStep={currentStep} steps={STEPS} onStepClick={goToStep} completedSteps={completedSteps} />
      </div>

      <Form {...form}>
        <form
          onSubmit={(e) => {
            // Impede submit acidental (Enter / click-through ao trocar Continuar → Salvar)
            e.preventDefault();
            if (!isLastStep) {
              void goNext();
              return;
            }
            void form.handleSubmit(onSubmit)();
          }}
          className="flex-1 flex flex-col"
        >
          <div className="flex-1 min-w-0">
            {/* Passo 1: Informações Básicas */}
            {currentStep === 0 && (
              <Card>
                <CardHeader>
                  <CardTitle>Informações Básicas</CardTitle>
                  <CardDescription>Dados principais do produto</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <FormField
                    control={form.control}
                    name="name"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Nome do Produto *</FormLabel>
                        <FormControl>
                          <Input placeholder="Pizza Margherita" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="description"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Descrição *</FormLabel>
                        <FormControl>
                          <Textarea placeholder="Descrição detalhada do produto" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormField
                      control={form.control}
                      name="brand"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Marca/Fabricante</FormLabel>
                          <FormControl>
                            <Input placeholder="Nike, Samsung, etc." {...field} />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={form.control}
                      name="sku"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>SKU/Código</FormLabel>
                          <FormControl>
                            <Input placeholder="PRD-001, ABC123, etc." {...field} />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                  </div>

                  <FormField
                    control={form.control}
                    name="categories"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Categorias *</FormLabel>
                        <FormControl>
                          <div className="space-y-2">
                            <div className="flex flex-wrap gap-2">
                              {field.value?.map((categoryId: string) => {
                                const category = categoryOptions.find((c) => c.value === categoryId);
                                return (
                                  <div
                                    key={categoryId}
                                    className="flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded-md text-sm"
                                  >
                                    <span>{category?.label || categoryId}</span>
                                    <button
                                      type="button"
                                      onClick={() => {
                                        const newCategories = field.value?.filter((id) => id !== categoryId) || [];
                                        field.onChange(newCategories);
                                      }}
                                      className="text-primary hover:text-primary/80"
                                    >
                                      ×
                                    </button>
                                  </div>
                                );
                              })}
                            </div>
                            <ComboboxForm
                              field={{
                                value: "",
                                onChange: (value: string) => {
                                  if (value && !field.value?.includes(value)) {
                                    field.onChange([...(field.value || []), value]);
                                  }
                                },
                                onBlur: field.onBlur,
                                name: field.name,
                              }}
                              options={categoryOptions}
                              placeholder="Adicionar categoria"
                              searchPlaceholder="Buscar categoria..."
                            />
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  {mode === "edit" && (
                    <FormField
                      control={form.control}
                      name="is_active"
                      render={({ field }) => (
                        <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                          <div className="space-y-0.5">
                            <FormLabel className="text-base">Produto Ativo</FormLabel>
                            <div className="text-sm text-muted-foreground">Desmarque para desativar o produto</div>
                          </div>
                          <FormControl>
                            <Switch checked={field.value} onCheckedChange={field.onChange} />
                          </FormControl>
                        </FormItem>
                      )}
                    />
                  )}
                </CardContent>
              </Card>
            )}

            {/* Passo 2: Preços e Estoque */}
            {currentStep === 1 && (
              <Card>
                <CardHeader>
                  <CardTitle>Preços e Estoque</CardTitle>
                  <CardDescription>Informações financeiras e de estoque</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormField
                      control={form.control}
                      name="price_cost"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Preço de Custo (R$) *</FormLabel>
                          <FormControl>
                            <Input
                              type="number"
                              step="0.01"
                              placeholder="0.00"
                              {...field}
                              onChange={(e) => field.onChange(Number(e.target.value))}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={form.control}
                      name="price"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Preço de Venda (R$) *</FormLabel>
                          <FormControl>
                            <Input
                              type="number"
                              step="0.01"
                              placeholder="0.00"
                              {...field}
                              onChange={(e) => field.onChange(Number(e.target.value))}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                  </div>

                  <FormField
                    control={form.control}
                    name="promotional_price"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Preço Promocional (R$)</FormLabel>
                        <FormControl>
                          <Input
                            type="number"
                            step="0.01"
                            placeholder="0.00 (opcional)"
                            {...field}
                            value={field.value ?? ""}
                            onChange={(e) => {
                              const value = e.target.value;
                              field.onChange(value === "" ? undefined : Number(value));
                            }}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="qtd_stock"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Quantidade em Estoque *</FormLabel>
                        <FormControl>
                          <Input
                            type="number"
                            placeholder="0"
                            {...field}
                            onChange={(e) => field.onChange(Number(e.target.value))}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </CardContent>
              </Card>
            )}

            {/* Passo 3: Logística */}
            {currentStep === 2 && (
              <Card>
                <CardHeader>
                  <CardTitle>Logística <span className="text-sm text-muted-foreground font-normal">(opcional)</span></CardTitle>
                  <CardDescription>Peso, dimensões e localização</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <FormField
                    control={form.control}
                    name="weight"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Peso (kg)</FormLabel>
                        <FormControl>
                          <Input
                            type="number"
                            step="0.001"
                            placeholder="0.000"
                            value={field.value ?? ""}
                            onChange={(e) => {
                              const value = e.target.value;
                              field.onChange(value === "" ? undefined : Number(value));
                            }}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <FormField
                      control={form.control}
                      name="height"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Altura (cm)</FormLabel>
                          <FormControl>
                            <Input
                              type="number"
                              step="0.01"
                              placeholder="0.00"
                              value={field.value ?? ""}
                              onChange={(e) => {
                                const value = e.target.value;
                                field.onChange(value === "" ? undefined : Number(value));
                              }}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={form.control}
                      name="width"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Largura (cm)</FormLabel>
                          <FormControl>
                            <Input
                              type="number"
                              step="0.01"
                              placeholder="0.00"
                              value={field.value ?? ""}
                              onChange={(e) => {
                                const value = e.target.value;
                                field.onChange(value === "" ? undefined : Number(value));
                              }}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={form.control}
                      name="depth"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Profundidade (cm)</FormLabel>
                          <FormControl>
                            <Input
                              type="number"
                              step="0.01"
                              placeholder="0.00"
                              value={field.value ?? ""}
                              onChange={(e) => {
                                const value = e.target.value;
                                field.onChange(value === "" ? undefined : Number(value));
                              }}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                  </div>

                  <FormField
                    control={form.control}
                    name="warehouse_location"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Localização no Estoque</FormLabel>
                        <FormControl>
                          <Input placeholder="A1-B2, Setor A, etc." {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="shipping_info"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Informações de Envio</FormLabel>
                        <FormControl>
                          <Textarea placeholder="Prazo de entrega, restrições, métodos de envio, etc." {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </CardContent>
              </Card>
            )}

            {/* Passo 4: Imagem */}
            {currentStep === 3 && (
              <Card>
                <CardHeader>
                  <CardTitle>Imagem <span className="text-sm text-muted-foreground font-normal">(opcional)</span></CardTitle>
                  <CardDescription>Foto do produto</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {currentImage ? (
                    <div className="relative w-full h-48 rounded-lg overflow-hidden border">
                      <img
                        src={currentImage}
                        alt="Imagem do produto"
                        className="w-full h-full object-cover"
                        onError={(e) => {
                          e.currentTarget.src =
                            'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23ddd" width="200" height="200"/%3E%3Ctext fill="%23999" x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle"%3ESem imagem%3C/text%3E%3C/svg%3E';
                        }}
                      />
                      {mode === "edit" && (
                        <div className="absolute top-2 left-2">
                          <Badge>Imagem Atual</Badge>
                        </div>
                      )}
                    </div>
                  ) : (
                    <div className="relative w-full h-48 rounded-lg overflow-hidden border bg-muted flex items-center justify-center">
                      <p className="text-center text-muted-foreground">Sem imagem cadastrada</p>
                    </div>
                  )}

                  <FormField
                    control={form.control}
                    name="image"
                    render={() => (
                      <FormItem>
                        <FormControl>
                          <ImageDropzone
                            onFileSelect={(file) => {
                              form.setValue("image", file, { shouldDirty: true });
                              onImageChange(file);
                            }}
                            hasPreview={!!currentImage}
                            label={currentImage ? "Selecionar Nova Imagem" : "Imagem do Produto"}
                            disabled={submitting}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </CardContent>
              </Card>
            )}

            {/* Passo 5: Variações e Opcionais */}
            {currentStep === 4 && (
              <div className="space-y-6">
                <ProductVariationsManager variations={variations} onChange={onVariationsChange} />
                <ProductOptionalsManager optionals={optionals} onChange={onOptionalsChange} />
              </div>
            )}
          </div>

          {/* NAVEGAÇÃO STICKY */}
          <div className="sticky bottom-0 bg-background border-t pt-3 pb-4 mt-4 -mx-3 px-3 sm:-mx-6 sm:px-6">
            <div className="flex gap-3">
              {currentStep > 0 && (
                <Button type="button" variant="outline" onClick={goBack} disabled={submitting} className="h-12 flex-1 sm:flex-none sm:w-32">
                  <ChevronLeft className="h-4 w-4 mr-1" />
                  Voltar
                </Button>
              )}
              {currentStep === 0 && (
                <Button type="button" variant="outline" onClick={onCancel} disabled={submitting} className="h-12 flex-1 sm:flex-none sm:w-32">
                  Cancelar
                </Button>
              )}

              {!isLastStep ? (
                <Button
                  type="button"
                  disabled={submitting || !canContinue}
                  className="h-12 flex-1"
                  onPointerDown={(e) => {
                    // preventDefault evita que o click residual acione o botão Salvar
                    // que substitui Continuar no mesmo lugar ao ir para o último passo.
                    if (e.button !== 0) return;
                    e.preventDefault();
                    void goNext();
                  }}
                  onKeyDown={(e) => {
                    if (e.key === "Enter" || e.key === " ") {
                      e.preventDefault();
                      void goNext();
                    }
                  }}
                >
                  Continuar
                  <ChevronRight className="h-4 w-4 ml-1" />
                </Button>
              ) : (
                <Button
                  type="button"
                  disabled={submitting}
                  className="h-12 flex-1"
                  onClick={() => {
                    if (ignoreSaveClickRef.current) return;
                    void form.handleSubmit(onSubmit)();
                  }}
                >
                  {submitting ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Salvando...
                    </>
                  ) : (
                    submitLabel
                  )}
                </Button>
              )}
            </div>
          </div>
        </form>
      </Form>
    </div>
  );
}
