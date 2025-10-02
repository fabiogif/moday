"use client"

import { useState } from "react"
import React from "react"
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
import { Plus } from "lucide-react"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"

const tableFormSchema = z.object({
  identify: z.string().min(1, {
    message: "Identificador da mesa é obrigatório.",
  }),
  name: z.string().min(1, {
    message: "Nome da mesa é obrigatório.",
  }),
  description: z.string().optional(),
  capacity: z.number().min(1, {
    message: "Capacidade deve ser pelo menos 1.",
  }).max(20, {
    message: "Capacidade não pode ser maior que 20.",
  }),
})

interface Table {
  id: number
  identify: string
  name: string
  description?: string
  capacity: number
  createdAt?: string
}

interface TableFormValues {
  identify: string
  name: string
  description?: string
  capacity: number
}

interface TableFormDialogProps {
  onAddTable: (tableData: TableFormValues) => void
  onEditTable?: (id: number, tableData: TableFormValues) => void
  editTable?: Table | null
}

export function TableFormDialog({ onAddTable, onEditTable, editTable }: TableFormDialogProps) {
  const [open, setOpen] = useState(false)
  const isEditing = !!editTable

  // Controlar abertura do modal quando editando
  React.useEffect(() => {
    if (editTable) {
      setOpen(true)
    }
  }, [editTable])

  const form = useForm<TableFormValues>({
    resolver: zodResolver(tableFormSchema),
    defaultValues: {
      identify: "",
      name: "",
      description: "",
      capacity: 4,
    },
  })

  // Preencher formulário quando editando
  React.useEffect(() => {
    if (editTable) {
      form.reset({
        identify: editTable.identify || "",
        name: editTable.name || "",
        description: editTable.description || "",
        capacity: editTable.capacity || 4,
      })
    }
  }, [editTable, form])

  const onSubmit = (data: TableFormValues) => {
    if (isEditing && editTable && onEditTable) {
      onEditTable(editTable.id, data)
    } else {
      onAddTable(data)
    }
    form.reset()
    setOpen(false)
  }

  const handleOpenChange = (newOpen: boolean) => {
    setOpen(newOpen)
    if (!newOpen && editTable) {
      // Se está fechando o modal de edição, limpar o estado
      // Isso será tratado pelo componente pai
    }
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          Nova Mesa
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Editar Mesa' : 'Nova Mesa'}</DialogTitle>
          <DialogDescription>
            {isEditing 
              ? 'Edite as informações da mesa abaixo.' 
              : 'Adicione uma nova mesa ao sistema. Preencha os dados abaixo.'
            }
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="identify"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Identificador da Mesa</FormLabel>
                  <FormControl>
                    <Input placeholder="MESA-001" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Nome da Mesa</FormLabel>
                  <FormControl>
                    <Input placeholder="Mesa Principal" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="capacity"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Capacidade</FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      placeholder="4"
                      {...field}
                      onChange={(e) => field.onChange(parseInt(e.target.value) || 4)}
                    />
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
                  <FormLabel>Descrição (Opcional)</FormLabel>
                  <FormControl>
                    <Input placeholder="Descrição da mesa..." {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <DialogFooter>
              <Button type="submit">{isEditing ? 'Salvar Alterações' : 'Criar Mesa'}</Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}