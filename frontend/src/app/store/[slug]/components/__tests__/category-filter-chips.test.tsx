import { render, screen } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import { CategoryFilterChips } from "../category-filter-chips"

describe("CategoryFilterChips", () => {
  const categories = [
    "Frango Xadrez",
    "Refrigerantes",
    "Risoto Chop Suey (Cenoura - Presunto - Cebolinha - Ovos)",
    "Yakisoba",
  ]

  it("quebra as categorias em várias linhas em vez de criar rolagem horizontal", () => {
    const { container } = render(
      <CategoryFilterChips categories={categories} selected="all" onSelect={jest.fn()} />,
    )

    const list = screen.getByLabelText("Categorias do cardápio")
    expect(list).toHaveClass("flex-wrap")
    expect(list.className).not.toMatch(/overflow-x/)
    expect(container.querySelector(".overflow-x-auto")).not.toBeInTheDocument()
  })

  it("permite selecionar uma categoria pelo nome completo", async () => {
    const user = userEvent.setup()
    const onSelect = jest.fn()

    render(
      <CategoryFilterChips categories={categories} selected="all" onSelect={onSelect} />,
    )

    await user.click(
      screen.getByRole("button", {
        name: "Risoto Chop Suey (Cenoura - Presunto - Cebolinha - Ovos)",
      }),
    )

    expect(onSelect).toHaveBeenCalledWith(
      "Risoto Chop Suey (Cenoura - Presunto - Cebolinha - Ovos)",
    )
  })
})
