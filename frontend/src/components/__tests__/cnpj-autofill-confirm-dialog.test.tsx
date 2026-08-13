import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { CnpjAutofillConfirmDialog } from '../cnpj-autofill-confirm-dialog'

describe('CnpjAutofillConfirmDialog', () => {
  it('renders company name and confirmation message', () => {
    render(
      <CnpjAutofillConfirmDialog
        open
        onOpenChange={jest.fn()}
        companyName="Alba Tec Ltda"
        onConfirm={jest.fn()}
      />
    )

    expect(screen.getByText(/Empresa encontrada: Alba Tec Ltda/i)).toBeInTheDocument()
    expect(screen.getByText(/Deseja preencher os dados automaticamente\?/i)).toBeInTheDocument()
  })

  it('calls onConfirm when user accepts', async () => {
    const user = userEvent.setup()
    const onConfirm = jest.fn()

    render(
      <CnpjAutofillConfirmDialog
        open
        onOpenChange={jest.fn()}
        companyName="Alba Tec Ltda"
        onConfirm={onConfirm}
      />
    )

    await user.click(screen.getByRole('button', { name: /Sim, preencher/i }))
    expect(onConfirm).toHaveBeenCalledTimes(1)
  })
})
