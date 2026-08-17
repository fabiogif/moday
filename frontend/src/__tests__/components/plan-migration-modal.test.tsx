/**
 * Testes para o componente PlanMigrationModal
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { PlanMigrationModal } from '@/components/plan-migration-modal'

const mockPlan = {
  id: 2,
  name: 'Premium',
}

describe('PlanMigrationModal', () => {
  it('não deve renderizar quando planToMigrate é null', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={null}
        onConfirm={onConfirm}
        loading={false}
      />
    )

    expect(screen.queryByText('Confirmar Migração de Plano')).not.toBeInTheDocument()
  })

  it('deve renderizar modal quando planToMigrate está definido', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={false}
      />
    )

    expect(screen.getByText('Confirmar Migração de Plano')).toBeInTheDocument()
    expect(screen.getByText(/Premium/)).toBeInTheDocument()
  })

  it('deve permitir adicionar observações', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={false}
      />
    )

    const textarea = screen.getByPlaceholderText(/Adicione uma observação/)
    fireEvent.change(textarea, { target: { value: 'Migração de teste' } })

    expect(textarea).toHaveValue('Migração de teste')
  })

  it('deve chamar onConfirm com planId e notes ao confirmar', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={false}
      />
    )

    const textarea = screen.getByPlaceholderText(/Adicione uma observação/)
    fireEvent.change(textarea, { target: { value: 'Migração de teste' } })

    const confirmButton = screen.getByText('Confirmar Migração')
    fireEvent.click(confirmButton)

    expect(onConfirm).toHaveBeenCalledWith(mockPlan.id, 'Migração de teste')
  })

  it('deve chamar onConfirm sem notes se textarea estiver vazio', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={false}
      />
    )

    const confirmButton = screen.getByText('Confirmar Migração')
    fireEvent.click(confirmButton)

    expect(onConfirm).toHaveBeenCalledWith(mockPlan.id, undefined)
  })

  it('deve chamar onOpenChange(false) ao cancelar', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={false}
      />
    )

    const cancelButton = screen.getByText('Cancelar')
    fireEvent.click(cancelButton)

    expect(onOpenChange).toHaveBeenCalledWith(false)
  })

  it('deve limpar textarea ao cancelar', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    const { rerender } = render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={false}
      />
    )

    const textarea = screen.getByPlaceholderText(/Adicione uma observação/)
    fireEvent.change(textarea, { target: { value: 'Teste' } })

    const cancelButton = screen.getByText('Cancelar')
    fireEvent.click(cancelButton)

    // Reabrir modal via rerender para evitar múltiplos elementos
    rerender(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={false}
      />
    )

    const newTextarea = screen.getByPlaceholderText(/Adicione uma observação/)
    expect(newTextarea).toHaveValue('')
  })

  it('deve desabilitar botões durante loading', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={true}
      />
    )

    const confirmButton = screen.getByText('Migrando...')
    const cancelButton = screen.getByText('Cancelar')

    expect(confirmButton.closest('button')).toBeDisabled()
    expect(cancelButton.closest('button')).toBeDisabled()
  })

  it('deve exibir texto de loading no botão de confirmar', () => {
    const onOpenChange = jest.fn()
    const onConfirm = jest.fn()

    render(
      <PlanMigrationModal
        open={true}
        onOpenChange={onOpenChange}
        planToMigrate={mockPlan}
        onConfirm={onConfirm}
        loading={true}
      />
    )

    expect(screen.getByText('Migrando...')).toBeInTheDocument()
  })
})

