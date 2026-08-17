"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { ComboboxOption } from "@/components/ui/combobox";
import { ArrowLeft, Loader2 } from "lucide-react";
import { resolveImageUrl } from "@/lib/resolve-image-url";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useAuthenticatedCategories, useMutation, useAuthenticatedApi } from "@/hooks/use-authenticated-api";
import { useBackendValidation, commonFieldMappings } from "@/hooks/use-backend-validation";
import { endpoints } from "@/lib/api-client";
import { toast } from "sonner";
import { ProductVariation, ProductOptional } from "@/types/product-variations";
import { ProductFormWizard, ProductFormValues } from "../../components/product-form-wizard";

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
  is_active: z.boolean().optional(),
});

interface Product {
  id: number;
  identify?: string;
  name: string;
  description: string;
  price: number | string;
  price_cost: number | string;
  promotional_price?: number | string;
  brand?: string;
  sku?: string;
  weight?: number | string;
  height?: number | string;
  width?: number | string;
  depth?: number | string;
  shipping_info?: string;
  warehouse_location?: string;
  categories?: Array<{
    identify: string;
    name: string;
  }>;
  qtd_stock?: number | string;
  stock?: number | string;
  is_active?: boolean;
  isActive?: boolean;
  url?: string;
  image?: string;
  variations?: ProductVariation[];
  optionals?: ProductOptional[];
}

export default function EditProductPage() {
  const router = useRouter();
  const params = useParams();
  const productId = params.id as string;

  const [variations, setVariations] = useState<ProductVariation[]>([]);
  const [optionals, setOptionals] = useState<ProductOptional[]>([]);
  const [currentImage, setCurrentImage] = useState<string | null>(null);
  const [isDirty, setIsDirty] = useState(false);

  const { data: product, loading: loadingProduct, error: productError } = useAuthenticatedApi<Product>(
    endpoints.products.getById(productId)
  );
  const { data: categories, loading: categoriesLoading } = useAuthenticatedCategories();
  const { mutate: updateProduct, loading: updating } = useMutation();

  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productFormSchema),
    defaultValues: {
      name: "",
      description: "",
      price: 0,
      price_cost: 0,
      promotional_price: undefined,
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
      is_active: true,
    },
  });

  const { handleBackendErrors } = useBackendValidation(form.setError);

  // Carregar dados do produto no formulário
  useEffect(() => {
    if (product) {
      const productData = {
        name: product.name || "",
        description: product.description || "",
        price: typeof product.price === 'string' ? parseFloat(product.price) : Number(product.price) || 0,
        price_cost: typeof product.price_cost === 'string' ? parseFloat(product.price_cost) : Number(product.price_cost) || 0,
        promotional_price: product.promotional_price ? (typeof product.promotional_price === 'string' ? parseFloat(product.promotional_price) : Number(product.promotional_price)) : undefined,
        brand: product.brand || "",
        sku: product.sku || "",
        weight: product.weight ? (typeof product.weight === 'string' ? parseFloat(product.weight) : Number(product.weight)) : undefined,
        height: product.height ? (typeof product.height === 'string' ? parseFloat(product.height) : Number(product.height)) : undefined,
        width: product.width ? (typeof product.width === 'string' ? parseFloat(product.width) : Number(product.width)) : undefined,
        depth: product.depth ? (typeof product.depth === 'string' ? parseFloat(product.depth) : Number(product.depth)) : undefined,
        shipping_info: product.shipping_info || "",
        warehouse_location: product.warehouse_location || "",
        categories: product.categories?.map(cat => cat.identify) || [],
        qtd_stock: product.qtd_stock ? (typeof product.qtd_stock === 'string' ? parseFloat(product.qtd_stock) : Number(product.qtd_stock)) : (product.stock ? (typeof product.stock === 'string' ? parseFloat(product.stock) : Number(product.stock)) : 0),
        is_active: product.is_active ?? product.isActive ?? true,
      };

      form.reset(productData);
      setVariations(Array.isArray(product.variations) ? product.variations : []);
      setOptionals(Array.isArray(product.optionals) ? product.optionals : []);

      if (product.url || product.image) {
        setCurrentImage(resolveImageUrl(product.url || product.image) || null);
      } else {
        setCurrentImage(null);
      }
    }
  }, [product, form]);

  // Monitorar alterações no formulário
  useEffect(() => {
    setIsDirty(form.formState.isDirty);
  }, [form.formState.isDirty]);

  // Avisar ao sair com alterações não salvas
  useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      if (isDirty) {
        e.preventDefault();
        e.returnValue = '';
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [isDirty]);

  const categoryOptions: ComboboxOption[] = Array.isArray(categories)
    ? categories.map((category: any) => ({
        value: category.identify,
        label: category.name,
      }))
    : [];

  const handleImageChange = (file: File) => {
    form.setValue("image", file, { shouldDirty: true });
    const reader = new FileReader();
    reader.onload = (e) => setCurrentImage(e.target?.result as string);
    reader.readAsDataURL(file);
  };

  const onSubmit = async (data: ProductFormValues) => {
    try {
      const formData = new FormData();

      // Adicionar _method para Laravel reconhecer como PUT
      formData.append('_method', 'PUT');

      // Campos obrigatórios
      formData.append('name', data.name);
      formData.append('description', data.description);
      formData.append('price', data.price.toString());
      formData.append('price_cost', data.price_cost.toString());
      formData.append('qtd_stock', data.qtd_stock.toString());

      // Status ativo
      formData.append('is_active', data.is_active !== undefined ? (data.is_active ? '1' : '0') : '1');

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

      formData.append('variations', JSON.stringify(variations));
      formData.append('optionals', JSON.stringify(optionals));

      data.categories.forEach((categoryId, index) => {
        formData.append(`categories[${index}]`, categoryId);
      });

      if (data.image && data.image instanceof File) {
        formData.append('image', data.image);
      }

      const result = await updateProduct(endpoints.products.update(productId), 'POST', formData);

      if (result) {
        toast.success('Produto atualizado com sucesso!');
        setIsDirty(false);
        router.push('/products');
      }
    } catch (error: any) {
      if (error.data?.errors?.image) {
        const imageErrors = Array.isArray(error.data.errors.image)
          ? error.data.errors.image.join(' ')
          : error.data.errors.image;
        toast.error(`Erro no upload da imagem: ${imageErrors}`);
        form.setError('image', { type: 'server', message: imageErrors });
        return;
      }

      const handled = handleBackendErrors(error, commonFieldMappings as any);

      if (!handled) {
        const errorMsg = error.data?.message || error.message || 'Erro ao atualizar produto';
        toast.error(errorMsg);
      }
    }
  };

  const handleCancel = () => {
    if (isDirty) {
      const confirmLeave = window.confirm('Você tem alterações não salvas. Deseja realmente sair?');
      if (!confirmLeave) return;
    }
    router.back();
  };

  if (loadingProduct) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] gap-4">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="text-muted-foreground">Carregando produto...</p>
      </div>
    );
  }

  if (productError || !product) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] gap-4">
        <p className="text-destructive">Erro ao carregar produto</p>
        <Button variant="outline" onClick={() => router.push('/products')}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Voltar para Produtos
        </Button>
      </div>
    );
  }

  return (
    <ProductFormWizard
      mode="edit"
      title="Editar Produto"
      description="Atualize as informações do produto"
      breadcrumb={
        <div className="flex items-center gap-2 text-sm text-muted-foreground mb-2">
          <button onClick={() => router.push('/products')} className="hover:text-foreground transition-colors">
            Produtos
          </button>
          <span>/</span>
          <button onClick={() => router.push(`/products/${productId}`)} className="hover:text-foreground transition-colors">
            {product.name}
          </button>
          <span>/</span>
          <span className="text-foreground">Editar</span>
        </div>
      }
      form={form}
      categoryOptions={categoryOptions}
      variations={variations}
      onVariationsChange={setVariations}
      optionals={optionals}
      onOptionalsChange={setOptionals}
      currentImage={currentImage}
      onImageChange={handleImageChange}
      submitting={updating}
      submitLabel="Salvar Alterações"
      onSubmit={onSubmit}
      onCancel={handleCancel}
    />
  );
}
