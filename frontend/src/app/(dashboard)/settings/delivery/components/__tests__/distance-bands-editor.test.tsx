import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import '@testing-library/jest-dom'
import { DistanceBandsEditor, DistanceBand } from '../distance-bands-editor'

const sampleBand: DistanceBand = {
  km_from: 0,
  km_to: 3,
  fee_type: 'fixed',
  fee_value: 5,
  estimated_time_minutes: 30,
}

describe('DistanceBandsEditor', () => {
  test('renders empty state with an "Adicionar Faixa" button', () => {
    render(<DistanceBandsEditor bands={[]} onChange={jest.fn()} />)

    expect(screen.getByRole('button', { name: /Adicionar Faixa/i })).toBeInTheDocument()
  })

  test('adds a new band appended after the last km_to when clicking "Adicionar Faixa"', () => {
    const handleChange = jest.fn()
    render(<DistanceBandsEditor bands={[sampleBand]} onChange={handleChange} />)

    fireEvent.click(screen.getByRole('button', { name: /Adicionar Faixa/i }))

    expect(handleChange).toHaveBeenCalledWith([
      sampleBand,
      expect.objectContaining({ km_from: 3, km_to: 6, fee_type: 'fixed' }),
    ])
  })

  test('removes a band when clicking its trash icon', () => {
    const handleChange = jest.fn()
    const bands = [sampleBand, { ...sampleBand, km_from: 3, km_to: 6 }]
    const { container } = render(<DistanceBandsEditor bands={bands} onChange={handleChange} />)

    const trashButtons = container.querySelectorAll('button svg.lucide-trash2')
    fireEvent.click(trashButtons[0].closest('button')!)

    expect(handleChange).toHaveBeenCalledWith([bands[1]])
  })

  test('updates km_from when the input changes', () => {
    const handleChange = jest.fn()
    render(<DistanceBandsEditor bands={[sampleBand]} onChange={handleChange} />)

    const kmFromInput = screen.getByDisplayValue('0')
    fireEvent.change(kmFromInput, { target: { value: '2' } })

    expect(handleChange).toHaveBeenCalledWith([
      expect.objectContaining({ km_from: 2, km_to: 3 }),
    ])
  })

  test('hides the value input when fee type is not "fixed"', () => {
    render(
      <DistanceBandsEditor
        bands={[{ ...sampleBand, fee_type: 'negotiable' }]}
        onChange={jest.fn()}
      />
    )

    expect(screen.queryByText('Valor (R$)')).not.toBeInTheDocument()
  })
})
