import { render, screen, waitFor } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import { CancelDialog } from "../cancel-dialog"

const cancelSubscription = jest.fn()
const clearError = jest.fn()

jest.mock("@/hooks/use-subscription", () => ({
  useSubscription: () => ({
    cancelSubscription,
    loading: false,
    error: null,
    clearError,
  }),
}))

describe("CancelDialog", () => {
  beforeEach(() => {
    cancelSubscription.mockReset()
    clearError.mockReset()
  })

  test("confirma cancelamento, envia motivo opcional e mostra acesso até a data", async () => {
    const user = userEvent.setup()
    const onSuccess = jest.fn()
    cancelSubscription.mockResolvedValue({
      access_until: "20/09/2026",
      message: "Cancelamento solicitado com sucesso",
    })

    render(
      <CancelDialog
        open
        onOpenChange={jest.fn()}
        currentPeriodEnd="20/09/2026"
        planName="Pro"
        onSuccess={onSuccess}
      />,
    )

    expect(screen.getByText(/Tem certeza que deseja cancelar/i)).toBeInTheDocument()
    expect(screen.getByText("20/09/2026")).toBeInTheDocument()

    await user.click(screen.getByLabelText(/Está caro demais/i))
    await user.click(screen.getByRole("button", { name: /Confirmar cancelamento/i }))

    await waitFor(() => {
      expect(cancelSubscription).toHaveBeenCalledWith({
        reason: "too_expensive",
        reason_detail: undefined,
      })
    })

    expect(await screen.findByText(/Cancelamento confirmado/i)).toBeInTheDocument()
    expect(screen.getByText(/20\/09\/2026/)).toBeInTheDocument()
    expect(onSuccess).toHaveBeenCalledWith("20/09/2026")
  })

  test("permite manter a assinatura sem chamar a API", async () => {
    const user = userEvent.setup()
    const onOpenChange = jest.fn()

    render(
      <CancelDialog
        open
        onOpenChange={onOpenChange}
        currentPeriodEnd="20/09/2026"
        onSuccess={jest.fn()}
      />,
    )

    await user.click(screen.getByRole("button", { name: /Manter assinatura/i }))
    expect(cancelSubscription).not.toHaveBeenCalled()
    expect(onOpenChange).toHaveBeenCalledWith(false)
  })
})
