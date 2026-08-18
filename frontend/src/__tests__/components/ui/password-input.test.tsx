import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { PasswordInput } from '@/components/ui/password-input'

describe('PasswordInput', () => {
  it('mostra o ícone para exibir a senha e inicia oculto', () => {
    render(<PasswordInput placeholder="Senha" />)

    expect(screen.getByPlaceholderText('Senha')).toHaveAttribute('type', 'password')
    expect(screen.getByRole('button', { name: /mostrar senha/i })).toBeInTheDocument()
  })

  it('alterna entre ocultar e exibir ao clicar no ícone', async () => {
    const user = userEvent.setup()
    render(<PasswordInput placeholder="Senha" />)

    const field = screen.getByPlaceholderText('Senha')
    await user.click(screen.getByRole('button', { name: /mostrar senha/i }))

    expect(field).toHaveAttribute('type', 'text')
    expect(screen.getByRole('button', { name: /ocultar senha/i })).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: /ocultar senha/i }))

    expect(field).toHaveAttribute('type', 'password')
    expect(screen.getByRole('button', { name: /mostrar senha/i })).toBeInTheDocument()
  })
})
