"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { ComboboxOption } from "@/components/ui/combobox";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useAuthenticatedCategories, useMutation } from "@/hooks/use-authenticated-api";
import { useBackendValidation, commonFieldMappings } from "@/hooks/use-backend-validation";
import { endpoints } from "@/lib/api-client";
import { toast } from "sonner";
import { ProductVariation, ProductOptional } from "@/types/product-variations";
import { ProductFormWizard, ProductFormValues } from "../components/product-form-wizard";

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

export default function NewProductPage() {
  const router = useRouter();
  const [variations, setVariations] = useState<ProductVariation[]>([]);
  const [optionals, setOptionals] = useState<ProductOptional[]>([]);
  const [currentImage, setCurrentImage] = useState<string | null>(null);
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

  const handleImageChange = (file: File) => {
    form.setValue("image", file);
    const reader = new FileReader();
    reader.onload = (e) => setCurrentImage(e.target?.result as string);
    reader.readAsDataURL(file);
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

      // Variações como JSON
      if (variations.length > 0) {
        formData.append('variations', JSON.stringify(variations));
      }

      // Opcionais como JSON
      if (optionals.length > 0) {
        formData.append('optionals', JSON.stringify(optionals));
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
      const handled = handleBackendErrors(error, commonFieldMappings as any);

      if (!handled) {
        toast.error(error.message || 'Erro ao criar produto');
      }
    }
  };

  return (
    <ProductFormWizard
      mode="create"
      title="Novo Produto"
      description="Adicione um novo produto ao sistema"
      form={form}
      categoryOptions={categoryOptions}
      variations={variations}
      onVariationsChange={setVariations}
      optionals={optionals}
      onOptionalsChange={setOptionals}
      currentImage={currentImage}
      onImageChange={handleImageChange}
      submitting={creating}
      submitLabel="Criar Produto"
      onSubmit={onSubmit}
      onCancel={() => router.back()}
    />
  );
}
