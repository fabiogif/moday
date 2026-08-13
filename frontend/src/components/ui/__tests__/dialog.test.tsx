import { render, screen } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "../dialog"
import { Button } from "../button"

describe("Dialog — Modal Checklist", () => {
  test("tem título, descrição, ação e botão Fechar", async () => {
    const user = userEvent.setup()
    const onOpenChange = jest.fn()

    render(
      <Dialog open onOpenChange={onOpenChange}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Excluir produto</DialogTitle>
            <DialogDescription>
              Esta ação remove o produto do cardápio. Pedidos anteriores não são afetados.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Manter produto
            </Button>
            <Button type="button" variant="destructive">
              Confirmar exclusão
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>,
    )

    expect(screen.getByRole("heading", { name: "Excluir produto" })).toBeInTheDocument()
    expect(
      screen.getByText(/remove o produto do cardápio/i),
    ).toBeInTheDocument()
    expect(screen.getByRole("button", { name: "Manter produto" })).toBeInTheDocument()
    expect(screen.getByRole("button", { name: "Confirmar exclusão" })).toBeInTheDocument()

    await user.click(screen.getByRole("button", { name: "Fechar" }))
    expect(onOpenChange).toHaveBeenCalledWith(false)
  })

  test("conteúdo usa fullscreen no mobile e overlay com blur", () => {
    render(
      <Dialog open onOpenChange={jest.fn()}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Título</DialogTitle>
            <DialogDescription>Descrição auxiliar</DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog>,
    )

    const content = document.querySelector('[data-slot="dialog-content"]')
    expect(content?.className).toMatch(/max-sm:!h-\[100dvh\]/)
    expect(content?.className).toMatch(/max-sm:!max-w-none/)

    const overlay = document.querySelector('[data-slot="dialog-overlay"]')
    expect(overlay?.className).toMatch(/backdrop-blur-sm/)
    expect(overlay?.className).toMatch(/bg-black\/50/)
  })
})
