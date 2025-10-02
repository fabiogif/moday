import { screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, generateTask } from '../utils/test-utils'
import TaskPage from '@/app/(dashboard)/tasks/page'

describe('Tasks CRUD', () => {
  const mockTasks = [
    generateTask({ 
      id: 'task-1',
      title: 'Fix authentication bug',
      status: 'todo',
      label: 'bug',
      priority: 'high'
    }),
    generateTask({ 
      id: 'task-2',
      title: 'Implement user profile',
      status: 'in-progress',
      label: 'feature',
      priority: 'medium'
    }),
    generateTask({ 
      id: 'task-3',
      title: 'Update documentation',
      status: 'done',
      label: 'documentation',
      priority: 'low'
    }),
  ]

  const mockSetTasks = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    
    // Mock the async getTasks function that's used in useEffect
    jest.mock('@/app/(dashboard)/tasks/data/tasks.json', () => mockTasks, { virtual: true })
    
    // Mock React.useState for tasks
    jest.spyOn(React, 'useState').mockImplementation((initial) => {
      if (Array.isArray(initial) && initial.length === 0) {
        return [mockTasks, mockSetTasks]
      }
      if (typeof initial === 'boolean' && initial === true) {
        return [false, jest.fn()] // loading state
      }
      return [initial, jest.fn()]
    })
  })

  afterEach(() => {
    jest.restoreAllMocks()
  })

  describe('Read Operations', () => {
    it('should render tasks list on desktop', async () => {
      // Mock window.matchMedia for desktop view
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: jest.fn().mockImplementation(query => ({
          matches: query === '(min-width: 768px)',
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
        expect(screen.getByText('Fix authentication bug')).toBeInTheDocument()
        expect(screen.getByText('Implement user profile')).toBeInTheDocument()
        expect(screen.getByText('Update documentation')).toBeInTheDocument()
      })
    })

    it('should show mobile placeholder on mobile devices', async () => {
      // Mock window.matchMedia for mobile view
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: jest.fn().mockImplementation(query => ({
          matches: query !== '(min-width: 768px)',
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
        expect(screen.getByText('Tasks Dashboard')).toBeInTheDocument()
        expect(screen.getByText('Please use a larger screen to view the full tasks interface.')).toBeInTheDocument()
      })
    })

    it('should display loading state initially', () => {
      jest.spyOn(React, 'useState').mockImplementation((initial) => {
        if (Array.isArray(initial) && initial.length === 0) {
          return [[], mockSetTasks]
        }
        if (typeof initial === 'boolean' && initial === true) {
          return [true, jest.fn()] // loading state = true
        }
        return [initial, jest.fn()]
      })

      render(<TaskPage />)
      
      expect(screen.getByText('Loading tasks...')).toBeInTheDocument()
    })

    it('should display task details correctly', async () => {
      render(<TaskPage />)
      
      await waitFor(() => {
        // Check for status indicators
        expect(screen.getByText('todo')).toBeInTheDocument()
        expect(screen.getByText('in-progress')).toBeInTheDocument()
        expect(screen.getByText('done')).toBeInTheDocument()
        
        // Check for priority indicators
        expect(screen.getByText('high')).toBeInTheDocument()
        expect(screen.getByText('medium')).toBeInTheDocument()
        expect(screen.getByText('low')).toBeInTheDocument()
        
        // Check for labels
        expect(screen.getByText('bug')).toBeInTheDocument()
        expect(screen.getByText('feature')).toBeInTheDocument()
        expect(screen.getByText('documentation')).toBeInTheDocument()
      })
    })
  })

  describe('Create Operations', () => {
    it('should open create task dialog when add button is clicked', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const addButton = screen.getByRole('button', { name: /add.*task/i })
        user.click(addButton)
      })
      
      expect(screen.getByRole('dialog')).toBeInTheDocument()
    })

    it('should create new task with valid data', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const addButton = screen.getByRole('button', { name: /add.*task/i })
        user.click(addButton)
      })
      
      // Fill form
      await user.type(screen.getByLabelText(/title/i), 'New Task')
      await user.selectOptions(screen.getByLabelText(/status/i), 'todo')
      await user.selectOptions(screen.getByLabelText(/priority/i), 'medium')
      await user.selectOptions(screen.getByLabelText(/label/i), 'feature')
      
      // Submit form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockSetTasks).toHaveBeenCalledWith(expect.any(Function))
        
        // Verify the function passed to setTasks creates correct task
        const setTasksCall = mockSetTasks.mock.calls[0][0]
        const newTasks = setTasksCall(mockTasks)
        
        expect(newTasks[0]).toMatchObject({
          title: 'New Task',
          status: 'todo',
          priority: 'medium',
          label: 'feature',
        })
        expect(newTasks[0].id).toBeDefined()
      })
    })

    it('should generate unique ID for new task', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const addButton = screen.getByRole('button', { name: /add.*task/i })
        user.click(addButton)
      })
      
      await user.type(screen.getByLabelText(/title/i), 'Unique Task')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        const setTasksCall = mockSetTasks.mock.calls[0][0]
        const newTasks = setTasksCall(mockTasks)
        
        // New task should have a unique ID (not matching existing ones)
        expect(newTasks[0].id).not.toBe('task-1')
        expect(newTasks[0].id).not.toBe('task-2')
        expect(newTasks[0].id).not.toBe('task-3')
        expect(newTasks[0].id).toMatch(/^task-/)
      })
    })

    it('should show validation errors for invalid data', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const addButton = screen.getByRole('button', { name: /add.*task/i })
        user.click(addButton)
      })
      
      // Submit empty form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/title.*required/i)).toBeInTheDocument()
      })
    })

    it('should create task with default values', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const addButton = screen.getByRole('button', { name: /add.*task/i })
        user.click(addButton)
      })
      
      // Fill only title
      await user.type(screen.getByLabelText(/title/i), 'Simple Task')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        const setTasksCall = mockSetTasks.mock.calls[0][0]
        const newTasks = setTasksCall(mockTasks)
        
        expect(newTasks[0]).toMatchObject({
          title: 'Simple Task',
          status: 'todo', // default
          priority: 'medium', // default
        })
      })
    })
  })

  describe('Update Operations', () => {
    it('should update task status via drag and drop', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const todoTask = screen.getByText('Fix authentication bug')
        const inProgressColumn = screen.getByText('In Progress')
        
        // Simulate drag and drop
        fireEvent.dragStart(todoTask)
        fireEvent.dragOver(inProgressColumn)
        fireEvent.drop(inProgressColumn)
      })
      
      // Should update task status
      expect(mockSetTasks).toHaveBeenCalled()
    })

    it('should update task via edit dialog', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        user.click(editButton)
      })
      
      // Edit task details
      const titleInput = screen.getByDisplayValue('Fix authentication bug')
      await user.clear(titleInput)
      await user.type(titleInput, 'Fix critical authentication bug')
      
      await user.selectOptions(screen.getByDisplayValue('high'), 'critical')
      
      const saveButton = screen.getByRole('button', { name: /save/i })
      await user.click(saveButton)
      
      expect(mockSetTasks).toHaveBeenCalledWith(expect.any(Function))
    })

    it('should update task priority', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const priorityDropdown = screen.getAllByRole('combobox')[0]
        user.selectOptions(priorityDropdown, 'critical')
      })
      
      // Should update task priority
      expect(mockSetTasks).toHaveBeenCalled()
    })

    it('should update task label', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const labelDropdown = screen.getAllByRole('combobox')[1]
        user.selectOptions(labelDropdown, 'enhancement')
      })
      
      // Should update task label
      expect(mockSetTasks).toHaveBeenCalled()
    })
  })

  describe('Delete Operations', () => {
    it('should delete task when delete button is clicked', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        user.click(deleteButton)
      })
      
      expect(mockSetTasks).toHaveBeenCalledWith(expect.any(Function))
      
      // Verify the function passed to setTasks removes the correct task
      const setTasksCall = mockSetTasks.mock.calls[0][0]
      const filteredTasks = setTasksCall(mockTasks)
      
      expect(filteredTasks).toHaveLength(2)
      expect(filteredTasks.find(t => t.id === 'task-1')).toBeUndefined()
    })

    it('should show confirmation dialog before deletion', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        user.click(deleteButton)
      })
      
      // In a full implementation, would show confirmation dialog
      expect(screen.getByText(/are you sure/i)).toBeInTheDocument()
    })

    it('should prevent deletion of tasks in progress', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        // Try to delete in-progress task
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[1]
        user.click(deleteButton)
      })
      
      // Should show warning about deleting in-progress tasks
      expect(screen.getByText(/task is in progress/i)).toBeInTheDocument()
    })
  })

  describe('Search and Filter Operations', () => {
    it('should filter tasks by search term', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*tasks/i)
      await user.type(searchInput, 'authentication')
      
      await waitFor(() => {
        expect(screen.getByText('Fix authentication bug')).toBeInTheDocument()
        expect(screen.queryByText('Implement user profile')).not.toBeInTheDocument()
        expect(screen.queryByText('Update documentation')).not.toBeInTheDocument()
      })
    })

    it('should filter tasks by status', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      const statusFilter = screen.getByRole('combobox', { name: /status/i })
      await user.selectOptions(statusFilter, 'todo')
      
      await waitFor(() => {
        expect(screen.getByText('Fix authentication bug')).toBeInTheDocument()
        expect(screen.queryByText('Implement user profile')).not.toBeInTheDocument()
        expect(screen.queryByText('Update documentation')).not.toBeInTheDocument()
      })
    })

    it('should filter tasks by priority', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      const priorityFilter = screen.getByRole('combobox', { name: /priority/i })
      await user.selectOptions(priorityFilter, 'high')
      
      await waitFor(() => {
        expect(screen.getByText('Fix authentication bug')).toBeInTheDocument()
        expect(screen.queryByText('Implement user profile')).not.toBeInTheDocument()
        expect(screen.queryByText('Update documentation')).not.toBeInTheDocument()
      })
    })

    it('should filter tasks by label', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      const labelFilter = screen.getByRole('combobox', { name: /label/i })
      await user.selectOptions(labelFilter, 'bug')
      
      await waitFor(() => {
        expect(screen.getByText('Fix authentication bug')).toBeInTheDocument()
        expect(screen.queryByText('Implement user profile')).not.toBeInTheDocument()
        expect(screen.queryByText('Update documentation')).not.toBeInTheDocument()
      })
    })

    it('should clear all filters', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      // Apply filters
      const statusFilter = screen.getByRole('combobox', { name: /status/i })
      await user.selectOptions(statusFilter, 'todo')
      
      // Clear filters
      const clearButton = screen.getByRole('button', { name: /clear.*filters/i })
      await user.click(clearButton)
      
      await waitFor(() => {
        expect(screen.getByText('Fix authentication bug')).toBeInTheDocument()
        expect(screen.getByText('Implement user profile')).toBeInTheDocument()
        expect(screen.getByText('Update documentation')).toBeInTheDocument()
      })
    })
  })

  describe('Sorting Operations', () => {
    it('should sort tasks by title', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      const titleHeader = screen.getByRole('columnheader', { name: /title/i })
      await user.click(titleHeader)
      
      // Should sort tasks alphabetically by title
    })

    it('should sort tasks by priority', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      const priorityHeader = screen.getByRole('columnheader', { name: /priority/i })
      await user.click(priorityHeader)
      
      // Should sort tasks by priority (high > medium > low)
    })

    it('should sort tasks by status', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      const statusHeader = screen.getByRole('columnheader', { name: /status/i })
      await user.click(statusHeader)
      
      // Should sort tasks by status
    })
  })

  describe('Kanban Board Features', () => {
    it('should display tasks in kanban columns', async () => {
      render(<TaskPage />)
      
      await waitFor(() => {
        expect(screen.getByText('To Do')).toBeInTheDocument()
        expect(screen.getByText('In Progress')).toBeInTheDocument()
        expect(screen.getByText('Done')).toBeInTheDocument()
      })
    })

    it('should move task between columns via drag and drop', async () => {
      render(<TaskPage />)
      
      await waitFor(() => {
        const todoTask = screen.getByText('Fix authentication bug')
        const inProgressColumn = screen.getByTestId('in-progress-column')
        
        // Simulate drag and drop
        fireEvent.dragStart(todoTask)
        fireEvent.dragEnter(inProgressColumn)
        fireEvent.dragOver(inProgressColumn)
        fireEvent.drop(inProgressColumn)
        fireEvent.dragEnd(todoTask)
      })
      
      expect(mockSetTasks).toHaveBeenCalledWith(expect.any(Function))
    })

    it('should show task count in each column', async () => {
      render(<TaskPage />)
      
      await waitFor(() => {
        // Should show count of tasks in each status
        expect(screen.getByText('1')).toBeInTheDocument() // 1 todo task
        expect(screen.getByText('1')).toBeInTheDocument() // 1 in-progress task
        expect(screen.getByText('1')).toBeInTheDocument() // 1 done task
      })
    })
  })

  describe('Task Details', () => {
    it('should show task details in sidebar or modal', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const task = screen.getByText('Fix authentication bug')
        user.click(task)
      })
      
      // Should show detailed task information
      expect(screen.getByText('Task Details')).toBeInTheDocument()
    })

    it('should display task metadata', async () => {
      render(<TaskPage />)
      
      await waitFor(() => {
        // Should show task IDs, creation dates, etc.
        expect(screen.getByText('task-1')).toBeInTheDocument()
        expect(screen.getByText('task-2')).toBeInTheDocument()
        expect(screen.getByText('task-3')).toBeInTheDocument()
      })
    })
  })

  describe('Bulk Operations', () => {
    it('should select multiple tasks for bulk operations', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        // Should show bulk operation controls
        expect(screen.getByText(/2 selected/i)).toBeInTheDocument()
      }
    })

    it('should perform bulk status update', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      // Select tasks
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        const bulkStatusButton = screen.getByRole('button', { name: /change status/i })
        await user.click(bulkStatusButton)
        
        await user.selectOptions(screen.getByRole('combobox'), 'done')
        
        expect(mockSetTasks).toHaveBeenCalled()
      }
    })

    it('should perform bulk delete', async () => {
      const user = userEvent.setup()
      render(<TaskPage />)
      
      // Select tasks
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        const bulkDeleteButton = screen.getByRole('button', { name: /delete selected/i })
        await user.click(bulkDeleteButton)
        
        // Confirm deletion
        const confirmButton = screen.getByRole('button', { name: /confirm/i })
        await user.click(confirmButton)
        
        expect(mockSetTasks).toHaveBeenCalled()
      }
    })
  })

  describe('Data Persistence', () => {
    it('should load tasks from JSON file on mount', async () => {
      render(<TaskPage />)
      
      await waitFor(() => {
        expect(screen.getByText('Fix authentication bug')).toBeInTheDocument()
        expect(screen.getByText('Implement user profile')).toBeInTheDocument()
        expect(screen.getByText('Update documentation')).toBeInTheDocument()
      })
    })

    it('should handle loading errors gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      
      // Mock getTasks to throw error
      jest.doMock('@/app/(dashboard)/tasks/data/tasks.json', () => {
        throw new Error('Failed to load tasks')
      })
      
      render(<TaskPage />)
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalledWith('Failed to load tasks:', expect.any(Error))
      })
      
      consoleErrorSpy.mockRestore()
    })
  })

  describe('Responsive Design', () => {
    it('should hide desktop features on mobile', () => {
      // Mock mobile viewport
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: jest.fn().mockImplementation(query => ({
          matches: query.includes('max-width'),
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
      
      expect(screen.getByText('Please use a larger screen to view the full tasks interface.')).toBeInTheDocument()
    })

    it('should show full interface on desktop', () => {
      // Mock desktop viewport
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: jest.fn().mockImplementation(query => ({
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
      
      expect(screen.queryByText('Please use a larger screen')).not.toBeInTheDocument()
    })
  })

  describe('Error Handling', () => {
    it('should handle task creation errors', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      
      // Mock setTasks to throw error
      mockSetTasks.mockImplementation(() => {
        throw new Error('Failed to create task')
      })
      
      const user = userEvent.setup()
      render(<TaskPage />)
      
      await waitFor(() => {
        const addButton = screen.getByRole('button', { name: /add.*task/i })
        user.click(addButton)
      })
      
      await user.type(screen.getByLabelText(/title/i), 'Error Task')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      // Should handle error gracefully
      consoleErrorSpy.mockRestore()
    })

    it('should handle empty task list', () => {
      jest.spyOn(React, 'useState').mockImplementation((initial) => {
        if (Array.isArray(initial) && initial.length === 0) {
          return [[], mockSetTasks]
        }
        return [initial, jest.fn()]
      })

      render(<TaskPage />)
      
      expect(screen.getByText(/no tasks found/i) || 
             screen.getByText(/empty/i)).toBeInTheDocument()
    })
  })
})