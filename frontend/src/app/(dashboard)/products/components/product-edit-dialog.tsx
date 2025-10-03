"use client";

import { useState, useEffect } from "react";
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
import { Pencil } from "lucide-react";
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
import { toast } from "sonner";

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
  categories: z.array(z.string()).min(1, {
    message: "Por favor, selecione pelo menos uma categoria.",
  }),
  qtd_stock: z.number().min(0, {
    message: "Estoque deve ser maior ou igual a 0.",
  }),
  image: z.any().optional(),
  is_active: z.boolean(),
});

interface ProductFormValues {
  name: string;
  description: string;
  price: number;
  price_cost: number;
  categories: string[];
  qtd_stock: number;
  image?: File;
  is_active: boolean;
}

interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  categories: Array<{
    identify: string;
    name: string;
  }>;
  price_cost: number;
  is_active: boolean;
  created_at: string;
  createdAt: string;
  url?: string;
}

interface ProductEditDialogProps {
  product: Product;
  onEditProduct: (product: Product) => void;
}

export function ProductEditDialog({ product, onEditProduct }: ProductEditDialogProps) {
  const [open, setOpen] = useState(false);
  const [currentImage, setCurrentImage] = useState<string | null>(product.url || null);
  
  const {
    data: categories,
    loading: categoriesLoading,
    error: categoriesError,
  } = useAuthenticatedCategories();

  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productFormSchema),
    defaultValues: {
      name: product.name,
      description: product.description,
      price: product.price,
      price_cost: product.price_cost,
      categories: product.categories?.map(cat => cat.identify).filter(Boolean) || [],
      qtd_stock: product.price_cost, // Assumindo que price_cost é o estoque
      image: undefined,
      is_active: product.is_active,
    },
    mode: "onChange",
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
        toast.error("Por favor, preencha todos os campos obrigatórios.");
        return;
      }

      // Criar objeto produto atualizado
      const updatedProduct: Product = {
        ...product,
        name: data.name,
        description: data.description,
        price: data.price,
        price_cost: data.price_cost,
        categories: data.categories?.map(catId => {
          const categoryList = Array.isArray(categories) ? categories : [];
          const category = categoryList.find((cat: any) => cat.identify === catId);
          return {
            identify: catId,
            name: category?.name || catId || 'Categoria desconhecida'
          };
        }).filter(Boolean) || [],
        is_active: data.is_active,
        url: currentImage || product.url
      };
      
      await onEditProduct(updatedProduct);
      toast.success("Produto alterado com sucesso!");
      setOpen(false);
    } catch (error: any) {
      console.error("Erro ao editar produto:", error);

      // Tratar erros de validação do backend
      const handled = handleBackendErrors(error, commonFieldMappings as any);

      if (!handled) {
        // Se não conseguiu mapear para campos específicos, mostrar erro geral
        form.setError("root", {
          type: "server",
          message: error.message || "Erro ao editar produto",
        });
        toast.error(error.message || "Erro ao editar produto");
      }
    }
  };

  const handleImageChange = (file: File | undefined) => {
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        setCurrentImage(e.target?.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm" className="w-full justify-start">
          <Pencil className="mr-2 h-4 w-4" />
          Editar
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Editar Produto</DialogTitle>
          <DialogDescription>
            Edite as informações do produto. Preencha os dados abaixo.
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
            <div className="grid ">
            <FormField
                control={form.control}
                name="is_active"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                      <FormLabel className="text-base">Produto Ativo</FormLabel>
                      <div className="text-sm text-muted-foreground">
                        Desmarque para desativar o produto
                      </div>
                    </div>
                    <FormControl>
                      <Switch
                        checked={field.value}
                        onCheckedChange={field.onChange}
                      />
                    </FormControl>
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
                  <FormLabel>Imagem do Produto</FormLabel>
                  <FormControl>
                    <div className="space-y-2">
                      {currentImage && (
                        <div className="flex items-center gap-2">
                          <img
                            src={currentImage}
                            alt="Imagem atual"
                            className="w-16 h-16 object-cover rounded-md border"
                          />
                          <span className="text-sm text-muted-foreground">
                            Imagem atual
                          </span>
                        </div>
                      )}
                      <Input
                        type="file"
                        accept="image/*"
                        onChange={(e) => {
                          const file = e.target.files?.[0];
                          if (file) {
                            handleImageChange(file);
                            onChange(file);
                          }
                        }}
                        {...field}
                        value={undefined}
                      />
                    </div>
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
              <Button type="submit">Salvar Alterações</Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
