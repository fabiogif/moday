import { render, screen } from '@testing-library/react'
import { StateCitySelect } from '@/components/location/state-city-select'

jest.mock('@/hooks/use-location', () => ({
  useStates: () => ({
    states: [{ id: 5, uf: 'BA', name: 'Bahia' }],
    loading: false,
    error: null,
    refresh: jest.fn(),
  }),
  useCitiesByState: () => ({
    cities: [{ id: 536, name: 'Salvador', is_capital: true }],
    loading: false,
    error: null,
    refresh: jest.fn(),
  }),
}))

describe('StateCitySelect', () => {
  it('permanece editável quando disabled é false', () => {
    render(
      <StateCitySelect
        stateValue=""
        cityValue=""
        onStateChange={jest.fn()}
        onCityChange={jest.fn()}
        disabled={false}
      />
    )

    const triggers = screen.getAllByRole('combobox')
    expect(triggers.length).toBeGreaterThan(0)
    expect(triggers[0]).not.toBeDisabled()
  })

  it('mostra o valor preenchido pelo CEP mesmo antes da lista carregar', () => {
    render(
      <StateCitySelect
        stateValue="BA"
        cityValue="Salvador"
        onStateChange={jest.fn()}
        onCityChange={jest.fn()}
        disabled={false}
      />
    )

    expect(screen.getByText('Bahia (BA)')).toBeInTheDocument()
    expect(screen.getByText('Salvador (Capital)')).toBeInTheDocument()
  })

  it('fica somente leitura quando disabled é true (CEP encontrado)', () => {
    render(
      <StateCitySelect
        stateValue="BA"
        cityValue="Salvador"
        onStateChange={jest.fn()}
        onCityChange={jest.fn()}
        disabled={true}
      />
    )

    const triggers = screen.getAllByRole('combobox')
    triggers.forEach((trigger) => {
      expect(trigger).toBeDisabled()
    })
  })
})
