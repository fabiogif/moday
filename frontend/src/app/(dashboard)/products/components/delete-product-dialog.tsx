"use client";

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Trash2 } from "lucide-react";

interface Product {
  id: number;
  name: string;
  description: string;
  price: number | string;
  categories: Array<{
    identify: string;
    name: string;
  }>;
  price_cost: number | string;
  qtd_stock?: number | string;
  is_active: boolean;
  created_at: string;
  createdAt: string;
  url?: string;
}

interface DeleteProductDialogProps {
  product: Product;
  onDeleteProduct: (id: number) => void;
}

export function DeleteProductDialog({ product, onDeleteProduct }: DeleteProductDialogProps) {
  return (
    <AlertDialog>
      <AlertDialogTrigger asChild>
        <Button variant="ghost" size="sm" className="w-full justify-start text-red-600">
          <Trash2 className="mr-2 h-4 w-4" />
          Excluir
        </Button>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Excluir Produto</AlertDialogTitle>
          <AlertDialogDescription>
            Tem certeza de que deseja excluir o produto <strong>"{product.name}"</strong>? 
            Esta ação não pode ser desfeita.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancelar</AlertDialogCancel>
          <AlertDialogAction
            onClick={() => onDeleteProduct(product.id)}
            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Excluir Produto
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}