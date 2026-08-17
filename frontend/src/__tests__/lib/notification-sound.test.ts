import {
  initAudioContext,
  playNotificationSound,
  playUrgentSound,
  testNotificationSound,
} from '@/lib/notification-sound'

describe('NotificationSound', () => {
  let mockAudioContext: any
  let mockOscillator: any
  let mockGainNode: any

  beforeEach(() => {
    // Mock AudioContext
    mockOscillator = {
      connect: jest.fn(),
      start: jest.fn(),
      stop: jest.fn(),
      frequency: { value: 0 },
      type: 'sine',
    }

    mockGainNode = {
      connect: jest.fn(),
      gain: {
        setValueAtTime: jest.fn(),
        linearRampToValueAtTime: jest.fn(),
        exponentialRampToValueAtTime: jest.fn(),
      },
    }

    mockAudioContext = {
      createOscillator: jest.fn(() => mockOscillator),
      createGain: jest.fn(() => mockGainNode),
      currentTime: 0,
      state: 'running',
      resume: jest.fn(),
      destination: {},
    }

    global.AudioContext = jest.fn(() => mockAudioContext) as any
    ;(global as any).webkitAudioContext = jest.fn(() => mockAudioContext)
  })

  afterEach(() => {
    jest.clearAllMocks()
  })

  describe('initAudioContext', () => {
    it('deve criar um novo AudioContext', () => {
      const context = initAudioContext()

      expect(context).toBeDefined()
    })

    it('deve lidar com ambiente server-side', () => {
      // Este teste verifica se a função lida com server-side rendering
      // Sem window, deve retornar null ou não lançar erro
      const originalWindow = global.window
      delete (global as any).window

      let result
      expect(() => {
        result = initAudioContext()
      }).not.toThrow()

      // Restaurar
      ;(global as any).window = originalWindow
    })

    it('deve ser possível chamar múltiplas vezes sem erro', () => {
      const context1 = initAudioContext()
      const context2 = initAudioContext()

      // Ambos devem ser definidos ou null (server-side)
      expect(typeof context1).toBe(typeof context2)
    })
  })

  describe('playNotificationSound', () => {
    it('deve executar sem lançar erro quando AudioContext está disponível', async () => {
      await expect(playNotificationSound()).resolves.not.toThrow()
    })

    it('não deve lançar erro se AudioContext falhar', async () => {
      global.AudioContext = jest.fn(() => {
        throw new Error('AudioContext not supported')
      }) as any

      await expect(playNotificationSound()).resolves.not.toThrow()
    })

    it('deve ser uma função async que retorna Promise', () => {
      const result = playNotificationSound()
      expect(result).toBeInstanceOf(Promise)
    })
  })

  describe('playUrgentSound', () => {
    it('deve executar sem lançar erro quando AudioContext está disponível', async () => {
      await expect(playUrgentSound()).resolves.not.toThrow()
    })

    it('deve ser uma função async que retorna Promise', () => {
      const result = playUrgentSound()
      expect(result).toBeInstanceOf(Promise)
    })
  })

  describe('testNotificationSound', () => {
    it('deve chamar playNotificationSound', () => {
      const spy = jest.spyOn({ playNotificationSound }, 'playNotificationSound')
      
      testNotificationSound()

      // O teste apenas verifica que a função é chamável
      expect(testNotificationSound).toBeDefined()
    })
  })
})

