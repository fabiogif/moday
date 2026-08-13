import { render, screen } from '@testing-library/react'
import { StatCard } from '@/components/admin/stat-card'
import { Building2, DollarSign } from 'lucide-react'

describe('StatCard Component', () => {
  it('should render title and value', () => {
    render(<StatCard title="Total Empresas" value={150} />)

    expect(screen.getByText('Total Empresas')).toBeInTheDocument()
    expect(screen.getByText('150')).toBeInTheDocument()
  })

  it('should render description when provided', () => {
    render(
      <StatCard
        title="Empresas Ativas"
        value={120}
        description="Últimos 30 dias"
      />
    )

    expect(screen.getByText('Últimos 30 dias')).toBeInTheDocument()
  })

  it('should render icon when provided', () => {
    render(
      <StatCard title="Empresas" value={150} icon={Building2} />
    )

    // Verifica se o SVG foi renderizado
    const svg = document.querySelector('svg')
    expect(svg).toBeInTheDocument()
  })

  it('should render positive trend', () => {
    render(
      <StatCard
        title="MRR"
        value="R$ 45.000"
        trend={{ value: 15, isPositive: true }}
      />
    )

    expect(screen.getByText(/\+15%/)).toBeInTheDocument()
    expect(screen.getByText(/\+15%/).className).toContain('text-green')
  })

  it('should render negative trend', () => {
    render(
      <StatCard
        title="Churn"
        value="2.5%"
        trend={{ value: -5, isPositive: false }}
      />
    )

    expect(screen.getByText(/-5%/)).toBeInTheDocument()
    expect(screen.getByText(/-5%/).className).toContain('text-red')
  })

  it('should apply custom className', () => {
    const { container } = render(
      <StatCard
        title="Test"
        value={100}
        className="custom-class"
      />
    )

    const card = container.firstChild
    expect(card).toHaveClass('custom-class')
  })

  it('should handle string and number values', () => {
    const { rerender } = render(
      <StatCard title="Test" value={150} />
    )

    expect(screen.getByText('150')).toBeInTheDocument()

    rerender(<StatCard title="Test" value="R$ 1.500,00" />)

    expect(screen.getByText('R$ 1.500,00')).toBeInTheDocument()
  })

  it('should render multiple stat cards', () => {
    render(
      <div>
        <StatCard title="Card 1" value={100} />
        <StatCard title="Card 2" value={200} />
        <StatCard title="Card 3" value={300} />
      </div>
    )

    expect(screen.getByText('Card 1')).toBeInTheDocument()
    expect(screen.getByText('Card 2')).toBeInTheDocument()
    expect(screen.getByText('Card 3')).toBeInTheDocument()
    expect(screen.getByText('100')).toBeInTheDocument()
    expect(screen.getByText('200')).toBeInTheDocument()
    expect(screen.getByText('300')).toBeInTheDocument()
  })
})

