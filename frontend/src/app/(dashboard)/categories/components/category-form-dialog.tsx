"use client"

import { useEffect, useState } from "react"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Switch } from "@/components/ui/switch"
import { Plus } from "lucide-react"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"

const categoryFormSchema = z.object({
  name: z.string().min(2, {
    message: "Nome deve ter pelo menos 2 caracteres.",
  }),
  description: z.string().max(500).optional().or(z.literal("")),
  color: z.string().optional().or(z.literal("")),
  isActive: z.boolean(),
})

export interface CategoryFormValues {
  name: string
  description?: string
  color?: string
  isActive: boolean
}

export interface EditableCategory {
  identify: string
  name: string
  description?: string
  color?: string
  isActive?: boolean
  status?: string
}

interface CategoryFormDialogProps {
  onAddCategory: (categoryData: CategoryFormValues) => void | Promise<void>
  onEditCategory?: (identify: string, categoryData: CategoryFormValues) => void | Promise<void>
  categoryToEdit?: EditableCategory | null
  open?: boolean
  onOpenChange?: (open: boolean) => void
  hideTrigger?: boolean
}

const colors = [
  { name: "Vermelho", value: "#FF6B6B" },
  { name: "Azul", value: "#4ECDC4" },
  { name: "Verde", value: "#45B7D1" },
  { name: "Amarelo", value: "#96CEB4" },
  { name: "Laranja", value: "#FFEAA7" },
  { name: "Roxo", value: "#DDA0DD" },
  { name: "Rosa", value: "#FFB6C1" },
  { name: "Cinza", value: "#D3D3D3" },
]

export function CategoryFormDialog({
  onAddCategory,
  onEditCategory,
  categoryToEdit = null,
  open: controlledOpen,
  onOpenChange,
  hideTrigger = false,
}: CategoryFormDialogProps) {
  const [uncontrolledOpen, setUncontrolledOpen] = useState(false)
  const isEdit = !!categoryToEdit
  const open = controlledOpen ?? uncontrolledOpen
  const setOpen = onOpenChange ?? setUncontrolledOpen

  const form = useForm<CategoryFormValues>({
    resolver: zodResolver(categoryFormSchema),
    defaultValues: {
      name: "",
      description: "",
      color: "",
      isActive: true,
    },
  })

  useEffect(() => {
    if (!open) return

    if (categoryToEdit) {
      form.reset({
        name: categoryToEdit.name || "",
        description: categoryToEdit.description || "",
        color: categoryToEdit.color || "",
        isActive:
          categoryToEdit.isActive ??
          (categoryToEdit.status ? categoryToEdit.status === "A" : true),
      })
    } else {
      form.reset({
        name: "",
        description: "",
        color: "",
        isActive: true,
      })
    }
  }, [open, categoryToEdit, form])

  const onSubmit = async (data: CategoryFormValues) => {
    if (isEdit && categoryToEdit && onEditCategory) {
      await onEditCategory(categoryToEdit.identify, data)
    } else {
      await onAddCategory(data)
    }
    form.reset()
    setOpen(false)
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      {!hideTrigger && !isEdit && (
        <DialogTrigger asChild>
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            Nova Categoria
          </Button>
        </DialogTrigger>
      )}
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{isEdit ? "Editar Categoria" : "Nova Categoria"}</DialogTitle>
          <DialogDescription>
            {isEdit
              ? "Atualize os dados da categoria abaixo."
              : "Adicione uma nova categoria ao sistema. Preencha os dados abaixo."}
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Nome da Categoria</FormLabel>
                  <FormControl>
                    <Input placeholder="Pizzas" {...field} />
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
                    <Input placeholder="Pizzas tradicionais e especiais" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="color"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Cor</FormLabel>
                  <Select onValueChange={field.onChange} value={field.value || undefined}>
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione uma cor" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      {colors.map((color) => (
                        <SelectItem key={color.value} value={color.value}>
                          <div className="flex items-center gap-2">
                            <div
                              className="w-4 h-4 rounded-full"
                              style={{ backgroundColor: color.value }}
                            />
                            {color.name}
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="isActive"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">Categoria Ativa</FormLabel>
                    <div className="text-sm text-muted-foreground">
                      Esta categoria estará disponível para uso
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
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                Cancelar
              </Button>
              <Button type="submit">{isEdit ? "Salvar alterações" : "Criar Categoria"}</Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
