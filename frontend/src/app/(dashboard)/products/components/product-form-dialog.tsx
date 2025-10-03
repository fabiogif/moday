"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { ComboboxForm, ComboboxOption } from "@/components/ui/combobox";
import { Switch } from "@/components/ui/switch";
import { Plus } from "lucide-react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import {
  useAuthenticatedCategories,
  useMutationWithValidation,
} from "@/hooks/use-authenticated-api";
import {
  useBackendValidation,
  commonFieldMappings,
} from "@/hooks/use-backend-validation";

const productFormSchema = z.object({
  name: z.string().min(2, {
    message: "Nome deve ter pelo menos 2 caracteres.",
  }),
  description: z.string().min(5, {
    message: "Descrição deve ter pelo menos 5 caracteres.",
  }),
  price: z.number().min(0, {
    message: "Preço deve ser maior ou igual a 0.",
  }),
  price_cost: z.number().min(0, {
    message: "Preço de custo deve ser maior ou igual a 0.",
  }),
  promotional_price: z.number().min(0, {
    message: "Preço promocional deve ser maior ou igual a 0.",
  }).optional(),
  brand: z.string().optional(),
  sku: z.string().optional(),
  weight: z.number().min(0, {
    message: "Peso deve ser maior ou igual a 0.",
  }).optional(),
  height: z.number().min(0, {
    message: "Altura deve ser maior ou igual a 0.",
  }).optional(),
  width: z.number().min(0, {
    message: "Largura deve ser maior ou igual a 0.",
  }).optional(),
  depth: z.number().min(0, {
    message: "Profundidade deve ser maior ou igual a 0.",
  }).optional(),
  shipping_info: z.string().optional(),
  warehouse_location: z.string().optional(),
  variations: z.array(z.object({
    type: z.string(),
    value: z.string(),
  })).optional(),
  categories: z.array(z.string()).min(1, {
    message: "Por favor, selecione pelo menos uma categoria.",
  }),
  qtd_stock: z.number().min(0, {
    message: "Estoque deve ser maior ou igual a 0.",
  }),
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
  variations?: Array<{ type: string; value: string }>;
  categories: string[];
  qtd_stock: number;
  image?: File;
}

interface ProductFormDialogProps {
  onAddProduct: (productData: ProductFormValues) => void;
  renderAsPage?: boolean;
}

export function ProductFormDialog({ onAddProduct, renderAsPage = false }: ProductFormDialogProps) {
  const [open, setOpen] = useState(false);
  const {
    data: categories,
    loading: categoriesLoading,
    error: categoriesError,
  } = useAuthenticatedCategories();

  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productFormSchema),
    defaultValues: {
      name: "",
      description: "",
      price: 0,
      price_cost: 0,
      categories: [],
      qtd_stock: 0,
      image: undefined,
    },
    mode: "onChange", // Validar em tempo real
  });

  // Hook para tratamento de erros de validação do backend
  const { handleBackendErrors } = useBackendValidation(form.setError);

  const onSubmit = async (data: ProductFormValues) => {
    try {
      // Verificar se todos os campos obrigatórios estão preenchidos
      if (
        !data.name ||
        !data.description ||
        !data.categories?.length ||
        data.price < 0 ||
        data.price_cost < 0 ||
        data.qtd_stock < 0
      ) {
        console.error("Dados inválidos:", {
          name: data.name,
          description: data.description,
          categories: data.categories,
          price: data.price,
          price_cost: data.price_cost,
          qtd_stock: data.qtd_stock,
        });
        // TODO: Implementar toast ou alert dialog para validação
        console.warn("Por favor, preencha todos os campos obrigatórios.");
        return;
      }

      await onAddProduct(data);
      form.reset();
      setOpen(false);
    } catch (error: any) {
      console.error("Erro ao criar produto:", error);

      // Tratar erros de validação do backend
      const handled = handleBackendErrors(error, commonFieldMappings as any);

      if (!handled) {
        // Se não conseguiu mapear para campos específicos, mostrar erro geral
        form.setError("root", {
          type: "server",
          message: error.message || "Erro ao criar produto",
        });
      }
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          Novo Produto
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Novo Produto</DialogTitle>
          <DialogDescription>
            Adicione um novo produto ao sistema. Preencha os dados abaixo.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Nome do Produto</FormLabel>
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
                  <FormLabel>Descrição</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="Pizza tradicional com molho de tomate"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="price_cost"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Preço de Custo (R$)</FormLabel>
                    <FormControl>
                      <Input
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        {...field}
                        onChange={(e) =>
                          field.onChange(parseFloat(e.target.value) || 0)
                        }
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
                    <FormLabel>Preço (R$)</FormLabel>
                    <FormControl>
                      <Input
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        {...field}
                        onChange={(e) =>
                          field.onChange(parseFloat(e.target.value) || 0)
                        }
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="qtd_stock"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Estoque</FormLabel>
                    <FormControl>
                      <Input
                        type="number"
                        placeholder="0"
                        {...field}
                        onChange={(e) =>
                          field.onChange(parseInt(e.target.value) || 0)
                        }
                      />
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
                  <FormLabel>Categorias</FormLabel>
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
                        options={
                          categoriesLoading
                            ? [
                                {
                                  value: "loading",
                                  label: "Carregando categorias...",
                                  disabled: true,
                                },
                              ]
                            : Array.isArray(categories) && categories.length > 0
                            ? categories
                                .filter(
                                  (category: any) =>
                                    category && 
                                    category.identify && 
                                    category.name &&
                                    !field.value?.includes(category.identify)
                                )
                                .map((category: any) => ({
                                  value: String(category.identify),
                                  label: category.name,
                                }))
                            : [
                                {
                                  value: "no-categories",
                                  label: "Nenhuma categoria encontrada",
                                  disabled: true,
                                },
                              ]
                        }
                        placeholder="Adicionar categoria"
                        searchPlaceholder="Buscar categoria..."
                        emptyText="Nenhuma categoria encontrada"
                      />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="image"
              render={({ field: { onChange, ...field } }) => (
                <FormItem>
                  <FormLabel>Imagem do Produto (opcional)</FormLabel>
                  <FormControl>
                    <Input
                      type="file"
                      accept="image/*"
                      onChange={(e) => {
                        const file = e.target.files?.[0];
                        console.log("Arquivo selecionado:", file);
                        onChange(file || undefined);
                      }}
                      {...field}
                      value={undefined} // Input file não pode ter value controlado
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => setOpen(false)}
              >
                Cancelar
              </Button>
              <Button type="submit">Criar Produto</Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
