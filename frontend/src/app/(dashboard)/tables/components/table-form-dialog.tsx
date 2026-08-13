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
import { TableData, TableFormValues } from "../types"

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

interface TableFormDialogProps {
  onAddTable: (tableData: TableFormValues) => void
  onEditTable?: (id: number, tableData: TableFormValues) => void
  editTable?: TableData | null
  onStartNew?: () => void
}

export function TableFormDialog({ onAddTable, onEditTable, editTable, onStartNew }: TableFormDialogProps) {
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
      capacity: undefined,
    },
  })

  // Reset form when closing or changing between add/edit modes
  React.useEffect(() => {
    if (open && !editTable) {
      // Creating new table - reset to empty values
      setTimeout(() => {
        form.reset({
          identify: "",
          name: "",
          description: "",
          capacity: undefined,
        })
      }, 50)
    } else if (open && editTable) {
      // Editing existing table - fill with table data  
      form.reset({
        identify: editTable.identify || "",
        name: editTable.name || "",
        description: editTable.description || "",
        capacity: editTable.capacity || 4,
      })
    }
  }, [open, editTable, form])

  const onSubmit = (data: TableFormValues) => {
    if (isEditing && editTable && onEditTable) {
      onEditTable(editTable.id, data)
    } else {
      onAddTable(data)
    }
    form.reset({
      identify: "",
      name: "",
      description: "",
      capacity: undefined,
    })
    setOpen(false)
    
    // Limpar estado de edição após submissão
    if (onStartNew) {
      onStartNew()
    }
  }

  const handleOpenChange = (newOpen: boolean) => {
    setOpen(newOpen)
    if (!newOpen) {
      // Quando fechando o modal, resetar formulário
      setTimeout(() => {
        form.reset({
          identify: "",
          name: "",
          description: "",
          capacity: undefined,
        })
      }, 100)
    }
  }

  // Handler para quando clicar no botão "Nova Mesa"
  const handleOpenForNew = () => {
    // Limpar estado de edição se existir
    if (onStartNew) {
      onStartNew()
    }
    // Forçar reset do formulário
    form.reset({
      identify: "",
      name: "",
      description: "",
      capacity: undefined,
    })
    // Abrir modal
    setOpen(true)
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button onClick={handleOpenForNew}>
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
                      placeholder="Digite a capacidade..."
                      {...field}
                      onChange={(e) => field.onChange(e.target.value ? parseInt(e.target.value) : undefined)}
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
              <Button 
                type="button" 
                variant="outline" 
                onClick={() => setOpen(false)}
              >
                Cancelar
              </Button>
              <Button type="submit">{isEditing ? 'Salvar Alterações' : 'Criar Mesa'}</Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}