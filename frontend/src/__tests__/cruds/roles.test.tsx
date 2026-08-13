import { screen } from '@testing-library/react'
import { render, generateRole } from '../utils/test-utils'
import { useAuthenticatedRoles, useMutation } from '@/hooks/use-authenticated-api'

const RolesPage = () => <div>Roles Page (Mock)</div>

const mockUseAuthenticatedRoles = useAuthenticatedRoles as jest.MockedFunction<
  typeof useAuthenticatedRoles
>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

describe('Roles CRUD', () => {
  const mockRoles = [
    generateRole({ id: 1, name: 'Admin', slug: 'admin' }),
    generateRole({ id: 2, name: 'Editor', slug: 'editor' }),
  ]

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuthenticatedRoles.mockReturnValue({
      data: mockRoles,
      loading: false,
      error: null,
      refetch: jest.fn(),
      isAuthenticated: true,
    })

    mockUseMutation.mockReturnValue({
      mutate: jest.fn().mockResolvedValue({}),
      loading: false,
      error: null,
    })
  })

  it('should render roles page placeholder', () => {
    render(<RolesPage />)
    expect(screen.getByText('Roles Page (Mock)')).toBeInTheDocument()
  })

  it('should provide authenticated roles data from hook', () => {
    const { data, isAuthenticated } = mockUseAuthenticatedRoles()
    expect(isAuthenticated).toBe(true)
    expect(data).toHaveLength(2)
    expect(data[0].name).toBe('Admin')
  })
})
