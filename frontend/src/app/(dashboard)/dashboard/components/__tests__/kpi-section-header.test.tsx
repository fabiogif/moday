import { render, screen } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import { KpiSectionHeader } from "../kpi-section-header"
import { PERIOD_KPI_CARDS } from "../../lib/kpi-card-visibility"

describe("KpiSectionHeader", () => {
  const cards = PERIOD_KPI_CARDS

  it("abre o menu para exibir ou ocultar cards da seção", async () => {
    const user = userEvent.setup()
    const onSetVisible = jest.fn()
    const onShowAll = jest.fn()

    render(
      <KpiSectionHeader
        label="Período selecionado · Últimos 30 dias"
        cards={cards}
        hidden={new Set()}
        onSetVisible={onSetVisible}
        onShowAll={onShowAll}
      />,
    )

    expect(screen.getByText("Período selecionado · Últimos 30 dias")).toBeInTheDocument()
    await user.click(screen.getByRole("button", { name: /exibir ou ocultar cards/i }))

    expect(await screen.findByText("Exibir cards")).toBeInTheDocument()
    expect(screen.getByRole("menuitemcheckbox", { name: "Receita" })).toHaveAttribute(
      "aria-checked",
      "true",
    )

    await user.click(screen.getByRole("menuitemcheckbox", { name: "Receita" }))
    expect(onSetVisible).toHaveBeenCalledWith("period-revenue", false)
  })

  it("oferece Exibir todos quando algum card está oculto", async () => {
    const user = userEvent.setup()
    const onShowAll = jest.fn()

    render(
      <KpiSectionHeader
        label="Este mês (vs. mês anterior)"
        cards={cards}
        hidden={new Set(["period-revenue"])}
        onSetVisible={jest.fn()}
        onShowAll={onShowAll}
      />,
    )

    await user.click(screen.getByRole("button", { name: /exibir ou ocultar cards/i }))
    await user.click(await screen.findByRole("menuitem", { name: "Exibir todos" }))

    expect(onShowAll).toHaveBeenCalledWith(cards)
  })
})
