import {
  KPI_CARD_STORAGE_KEY,
  parseHiddenKpiCards,
  serializeHiddenKpiCards,
} from "@/app/(dashboard)/dashboard/lib/kpi-card-visibility"

describe("parseHiddenKpiCards", () => {
  it("retorna conjunto vazio quando não há valor salvo", () => {
    expect(parseHiddenKpiCards(null).size).toBe(0)
  })

  it("ignora JSON inválido", () => {
    expect(parseHiddenKpiCards("{nao-json").size).toBe(0)
    expect(parseHiddenKpiCards("42").size).toBe(0)
  })

  it("lê apenas ids string", () => {
    const hidden = parseHiddenKpiCards(JSON.stringify(["period-revenue", 1, null, "period-orders"]))
    expect([...hidden]).toEqual(["period-revenue", "period-orders"])
  })
})

describe("serializeHiddenKpiCards", () => {
  it("persiste os ids ocultos em JSON", () => {
    expect(serializeHiddenKpiCards(new Set(["period-ticket"]))).toBe(
      JSON.stringify(["period-ticket"]),
    )
  })
})

describe("KPI_CARD_STORAGE_KEY", () => {
  it("usa uma chave estável no localStorage", () => {
    expect(KPI_CARD_STORAGE_KEY).toBe("dashboardKpiCardsHidden")
  })
})
