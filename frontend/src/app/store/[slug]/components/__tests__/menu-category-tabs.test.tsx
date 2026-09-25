import { render, screen, fireEvent, act } from '@testing-library/react'
import '@testing-library/jest-dom'
import { MenuCategoryTabs } from '../menu-category-tabs'

const sections = [
  { id: 'categoria-0', name: 'Entrada' },
  { id: 'categoria-1', name: 'Combos' },
  { id: 'categoria-2', name: 'Bebidas' },
]

/** Posição (top) de cada seção na tela, controlada pelo teste */
let sectionTops: Record<string, number> = {}

function renderWithSections() {
  render(
    <div>
      <MenuCategoryTabs sections={sections} stickyOffset={112} />
      {sections.map((section) => (
        <section key={section.id} id={section.id}>
          <h2>{section.name}</h2>
        </section>
      ))}
    </div>,
  )
}

function scrollTo(tops: Record<string, number>) {
  sectionTops = tops
  Object.defineProperty(window, 'scrollY', { value: 800, configurable: true })
  act(() => {
    window.dispatchEvent(new Event('scroll'))
    jest.advanceTimersByTime(50)
  })
}

describe('MenuCategoryTabs', () => {
  const originalRect = Element.prototype.getBoundingClientRect

  beforeEach(() => {
    jest.useFakeTimers()
    sectionTops = { 'categoria-0': 300, 'categoria-1': 900, 'categoria-2': 1500 }
    Element.prototype.getBoundingClientRect = function (this: Element) {
      return { top: sectionTops[this.id] ?? 0 } as DOMRect
    }
    Element.prototype.scrollIntoView = jest.fn()
    Object.defineProperty(window, 'scrollY', { value: 0, configurable: true })
    Object.defineProperty(document.documentElement, 'scrollHeight', { value: 5000, configurable: true })
  })

  afterEach(() => {
    Element.prototype.getBoundingClientRect = originalRect
    jest.useRealTimers()
  })

  it('começa na primeira categoria', () => {
    renderWithSections()
    expect(screen.getByRole('button', { name: 'Entrada' })).toHaveAttribute('aria-current', 'true')
  })

  it('clicar numa aba rola até a seção e marca a aba como atual', () => {
    renderWithSections()
    fireEvent.click(screen.getByRole('button', { name: 'Bebidas' }))

    expect(Element.prototype.scrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth', block: 'start' })
    expect((Element.prototype.scrollIntoView as jest.Mock).mock.contexts[0]).toBe(document.getElementById('categoria-2'))
    expect(screen.getByRole('button', { name: 'Bebidas' })).toHaveAttribute('aria-current', 'true')
    expect(screen.getByRole('button', { name: 'Entrada' })).not.toHaveAttribute('aria-current')
  })

  it('a aba só muda quando o título da seção chega ao header fixo', () => {
    renderWithSections()

    // Combos ainda abaixo do header (fim de Entrada visível): continua Entrada
    scrollTo({ 'categoria-0': -200, 'categoria-1': 150, 'categoria-2': 700 })
    expect(screen.getByRole('button', { name: 'Entrada' })).toHaveAttribute('aria-current', 'true')

    // Título de Combos alcançou o header
    scrollTo({ 'categoria-0': -500, 'categoria-1': 112, 'categoria-2': 500 })
    expect(screen.getByRole('button', { name: 'Combos' })).toHaveAttribute('aria-current', 'true')
  })

  it('no fim da página marca a última categoria, mesmo com o título abaixo do header', () => {
    renderWithSections()
    Object.defineProperty(window, 'innerHeight', { value: 4200, configurable: true })
    scrollTo({ 'categoria-0': -500, 'categoria-1': 112, 'categoria-2': 400 })
    expect(screen.getByRole('button', { name: 'Bebidas' })).toHaveAttribute('aria-current', 'true')
    Object.defineProperty(window, 'innerHeight', { value: 768, configurable: true })
  })

  it('ignora a rolagem logo depois de um clique', () => {
    renderWithSections()
    fireEvent.click(screen.getByRole('button', { name: 'Bebidas' }))
    scrollTo({ 'categoria-0': -500, 'categoria-1': 100, 'categoria-2': 400 })
    expect(screen.getByRole('button', { name: 'Bebidas' })).toHaveAttribute('aria-current', 'true')
  })

  it('o menu ☰ lista as categorias e navega até a escolhida', () => {
    renderWithSections()
    fireEvent.click(screen.getByRole('button', { name: 'Ver todas as categorias' }))
    const dialog = screen.getByRole('dialog')
    fireEvent.click(Array.from(dialog.querySelectorAll('button')).find((b) => b.textContent === 'Combos')!)
    act(() => {
      jest.advanceTimersByTime(300)
    })
    expect((Element.prototype.scrollIntoView as jest.Mock).mock.contexts.at(-1)).toBe(document.getElementById('categoria-1'))
  })
})
