"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { ComboboxForm, ComboboxOption } from "@/components/ui/combobox";
import { ArrowLeft, Plus, Trash2 } from "lucide-react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useAuthenticatedCategories, useMutation } from "@/hooks/use-authenticated-api";
import { useBackendValidation, commonFieldMappings } from "@/hooks/use-backend-validation";
import { endpoints } from "@/lib/api-client";
import { toast } from "sonner";

const productFormSchema = z.object({
  name: z.string().min(2, "Nome deve ter pelo menos 2 caracteres."),
  description: z.string().min(5, "Descrição deve ter pelo menos 5 caracteres."),
  price: z.number().min(0, "Preço deve ser maior ou igual a 0."),
  price_cost: z.number().min(0, "Preço de custo deve ser maior ou igual a 0."),
  promotional_price: z.number().min(0, "Preço promocional deve ser maior ou igual a 0.").optional(),
  brand: z.string().optional(),
  sku: z.string().optional(),
  weight: z.number().min(0, "Peso deve ser maior ou igual a 0.").optional(),
  height: z.number().min(0, "Altura deve ser maior ou igual a 0.").optional(),
  width: z.number().min(0, "Largura deve ser maior ou igual a 0.").optional(),
  depth: z.number().min(0, "Profundidade deve ser maior ou igual a 0.").optional(),
  shipping_info: z.string().optional(),
  warehouse_location: z.string().optional(),
  categories: z.array(z.string()).min(1, "Por favor, selecione pelo menos uma categoria."),
  qtd_stock: z.number().min(0, "Estoque deve ser maior ou igual a 0."),
  image: z.any().optional(),
});

interface ProductFormValues {
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
}

interface Variation {
  type: string;
  value: string;
}

export default function NewProductPage() {
  const router = useRouter();
  const [variations, setVariations] = useState<Variation[]>([]);
  const { data: categories, loading: categoriesLoading } = useAuthenticatedCategories();
  const { mutate: createProduct, loading: creating } = useMutation();

  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productFormSchema),
    defaultValues: {
      name: "",
      description: "",
      price: 0,
      price_cost: 0,
      promotional_price: 0,
      brand: "",
      sku: "",
      weight: undefined,
      height: undefined,
      width: undefined,
      depth: undefined,
      shipping_info: "",
      warehouse_location: "",
      categories: [],
      qtd_stock: 0,
    },
  });

  const { handleBackendErrors } = useBackendValidation(form.setError);

  const categoryOptions: ComboboxOption[] = Array.isArray(categories)
    ? categories.map((category: any) => ({
        value: category.identify,
        label: category.name,
      }))
    : [];

  const addVariation = () => {
    setVariations([...variations, { type: "", value: "" }]);
  };

  const removeVariation = (index: number) => {
    setVariations(variations.filter((_, i) => i !== index));
  };

  const updateVariation = (index: number, field: keyof Variation, value: string) => {
    const updated = [...variations];
    updated[index][field] = value;
    setVariations(updated);
  };

  const onSubmit = async (data: ProductFormValues) => {
    try {
      const formData = new FormData();
      formData.append('name', data.name);
      formData.append('description', data.description);
      formData.append('price', data.price.toString());
      formData.append('price_cost', data.price_cost.toString());
      formData.append('qtd_stock', data.qtd_stock.toString());

      // Campos opcionais - só envia se tiver valor
      if (data.promotional_price && data.promotional_price > 0) {
        formData.append('promotional_price', data.promotional_price.toString());
      }
      if (data.brand && data.brand.trim()) {
        formData.append('brand', data.brand.trim());
      }
      if (data.sku && data.sku.trim()) {
        formData.append('sku', data.sku.trim());
      }
      if (data.weight && data.weight > 0) {
        formData.append('weight', data.weight.toString());
      }
      if (data.height && data.height > 0) {
        formData.append('height', data.height.toString());
      }
      if (data.width && data.width > 0) {
        formData.append('width', data.width.toString());
      }
      if (data.depth && data.depth > 0) {
        formData.append('depth', data.depth.toString());
      }
      if (data.shipping_info && data.shipping_info.trim()) {
        formData.append('shipping_info', data.shipping_info.trim());
      }
      if (data.warehouse_location && data.warehouse_location.trim()) {
        formData.append('warehouse_location', data.warehouse_location.trim());
      }

      // Variações como JSON - só envia se tiver variações válidas
      const validVariations = variations.filter(v => v.type.trim() && v.value.trim());
      if (validVariations.length > 0) {
        formData.append('variations', JSON.stringify(validVariations));
      }

      // Categorias
      data.categories.forEach((categoryId, index) => {
        formData.append(`categories[${index}]`, categoryId);
      });

      // Imagem
      if (data.image && data.image instanceof File) {
        formData.append('image', data.image);
      }

      const result = await createProduct(endpoints.products.create, 'POST', formData);
      
      if (result) {
        toast.success('Produto criado com sucesso!');
        router.push('/products');
      }
    } catch (error: any) {
      console.error('Erro ao criar produto:', error);
      
      const handled = handleBackendErrors(error, commonFieldMappings as any);
      
      if (!handled) {
        toast.error(error.message || 'Erro ao criar produto');
      }
    }
  };

  return (
    <div className="flex flex-col gap-6 p-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-3xl font-bold">Novo Produto</h1>
          <p className="text-muted-foreground">Adicione um novo produto ao sistema</p>
        </div>
      </div>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Informações Básicas */}
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
                        <Textarea 
                          placeholder="Descrição detalhada do produto"
                          {...field} 
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

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
                              const category = Array.isArray(categories) 
                                ? categories.find((cat: any) => cat.identify === categoryId)
                                : null;
                              return (
                                <div
                                  key={categoryId}
                                  className="flex items-center gap-1 bg-primary/10 text-primary px-2 py-1 rounded-md text-sm"
                                >
                                  <span>{category?.name || categoryId}</span>
                                  <button
                                    type="button"
                                    onClick={() => {
                                      const newCategories = field.value?.filter(id => id !== categoryId) || [];
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
                                  const newCategories = [...(field.value || []), value];
                                  field.onChange(newCategories);
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
              </CardContent>
            </Card>

            {/* Preços e Estoque */}
            <Card>
              <CardHeader>
                <CardTitle>Preços e Estoque</CardTitle>
                <CardDescription>Informações financeiras e de estoque</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
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
                          value={field.value ?? ''}
                          onChange={(e) => {
                            const value = e.target.value;
                            field.onChange(value === '' ? undefined : Number(value));
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

            {/* Logística */}
            <Card>
              <CardHeader>
                <CardTitle>Logística</CardTitle>
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
                          placeholder="0.000 (opcional)"
                          value={field.value ?? ''}
                          onChange={(e) => {
                            const value = e.target.value;
                            field.onChange(value === '' ? undefined : Number(value));
                          }}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <div className="grid grid-cols-3 gap-4">
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
                            placeholder="0.00 (opcional)"
                            {...field}
                            onChange={(e) => {
                              const value = e.target.value;
                              field.onChange(value === '' ? undefined : Number(value));
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
                            placeholder="0.00 (opcional)"
                            {...field}
                            onChange={(e) => {
                              const value = e.target.value;
                              field.onChange(value === '' ? undefined : Number(value));
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
                            placeholder="0.00 (opcional)"
                            {...field}
                            onChange={(e) => {
                              const value = e.target.value;
                              field.onChange(value === '' ? undefined : Number(value));
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
                        <Input placeholder="A1-B2, Setor A, etc. (opcional)" {...field} />
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
                        <Textarea 
                          placeholder="Prazo de entrega, restrições, métodos de envio, etc. (opcional)"
                          {...field} 
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </CardContent>
            </Card>

            {/* Variações */}
            <Card>
              <CardHeader>
                <CardTitle>Variações</CardTitle>
                <CardDescription>Cores, tamanhos, voltagem, etc.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {variations.map((variation, index) => (
                  <div key={index} className="flex gap-2 items-end">
                    <div className="flex-1">
                      <Input
                        placeholder="Tipo (cor, tamanho, voltagem)"
                        value={variation.type}
                        onChange={(e) => updateVariation(index, 'type', e.target.value)}
                      />
                    </div>
                    <div className="flex-1">
                      <Input
                        placeholder="Valor (azul, P, 110V)"
                        value={variation.value}
                        onChange={(e) => updateVariation(index, 'value', e.target.value)}
                      />
                    </div>
                    <Button
                      type="button"
                      variant="outline"
                      size="icon"
                      onClick={() => removeVariation(index)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                ))}
                
                <Button
                  type="button"
                  variant="outline"
                  onClick={addVariation}
                  className="w-full"
                >
                  <Plus className="mr-2 h-4 w-4" />
                  Adicionar Variação
                </Button>
              </CardContent>
            </Card>

            {/* Imagem */}
            <Card>
              <CardHeader>
                <CardTitle>Imagem</CardTitle>
                <CardDescription>Foto do produto</CardDescription>
              </CardHeader>
              <CardContent>
                <FormField
                  control={form.control}
                  name="image"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Imagem do Produto</FormLabel>
                      <FormControl>
                        <Input
                          type="file"
                          accept="image/*"
                          onChange={(e) => {
                            const file = e.target.files?.[0];
                            field.onChange(file);
                          }}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </CardContent>
            </Card>
          </div>

          <div className="flex justify-end gap-4">
            <Button type="button" variant="outline" onClick={() => router.back()}>
              Cancelar
            </Button>
            <Button type="submit" disabled={creating}>
              {creating ? "Criando..." : "Criar Produto"}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  );
}
