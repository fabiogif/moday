import { screen, waitFor } from '@testing-library/react'
import { render } from '../utils/test-utils'
import TaskPage from '@/app/(dashboard)/tasks/page'

const defaultMatchMedia = window.matchMedia

describe('Tasks CRUD', () => {
  afterEach(() => {
    Object.defineProperty(window, 'matchMedia', {
      writable: true,
      value: defaultMatchMedia,
    })
  })
  it('should display loading state initially', () => {
    render(<TaskPage />)
    expect(screen.getByText('Loading tasks...')).toBeInTheDocument()
  })

  it('should render mobile placeholder after loading', async () => {
    render(<TaskPage />)

    await waitFor(() => {
      expect(screen.getByText('Painel de Tarefas')).toBeInTheDocument()
    })
  })

  it('should render desktop task list on large screens', async () => {
    Object.defineProperty(window, 'matchMedia', {
      writable: true,
      value: jest.fn().mockImplementation((query: string) => ({
        matches: query.includes('min-width'),
        media: query,
        onchange: null,
        addListener: jest.fn(),
        removeListener: jest.fn(),
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
      })),
    })

    render(<TaskPage />)

    await waitFor(() => {
      expect(
        screen.getByText(
          "You can't compress the program without quantifying the open-source SSD pixel!"
        )
      ).toBeInTheDocument()
    })
  })
})
