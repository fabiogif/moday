import { render, screen } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "../tooltip"

describe("Tooltip", () => {
  test("mostra o texto no hover e fecha pelo botão de dispensar", async () => {
    const user = userEvent.setup()
    render(
      <TooltipProvider delayDuration={0}>
        <Tooltip>
          <TooltipTrigger asChild>
            <button type="button">Ajuda</button>
          </TooltipTrigger>
          <TooltipContent dismissible>Detalhe extra do indicador</TooltipContent>
        </Tooltip>
      </TooltipProvider>,
    )

    await user.hover(screen.getByRole("button", { name: "Ajuda" }))
    expect(await screen.findByText("Detalhe extra do indicador")).toBeInTheDocument()

    await user.click(screen.getByRole("button", { name: "Fechar dica" }))
    expect(screen.queryByText("Detalhe extra do indicador")).not.toBeInTheDocument()
  })
})
