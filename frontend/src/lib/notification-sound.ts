/**
 * Utilitário para reproduzir sons de notificação usando Web Audio API
 */

let audioContext: AudioContext | null = null

// Inicializar AudioContext (precisa ser criado após interação do usuário)
export const initAudioContext = () => {
  if (typeof window === 'undefined') return null
  
  if (!audioContext) {
    audioContext = new (window.AudioContext || (window as any).webkitAudioContext)()
  }
  
  return audioContext
}

/**
 * Reproduz um som de notificação agradável (2 tons)
 */
export const playNotificationSound = async () => {
  try {
    const context = initAudioContext()
    if (!context) return

    // Retomar contexto se estiver suspenso
    if (context.state === 'suspended') {
      await context.resume()
    }

    const now = context.currentTime

    // Criar oscilador para primeiro tom
    const oscillator1 = context.createOscillator()
    const gainNode1 = context.createGain()
    
    oscillator1.connect(gainNode1)
    gainNode1.connect(context.destination)
    
    // Configurar primeiro tom (nota mais alta - 880Hz = A5)
    oscillator1.frequency.value = 880
    oscillator1.type = 'sine'
    
    // Envelope ADSR para som mais suave
    gainNode1.gain.setValueAtTime(0, now)
    gainNode1.gain.linearRampToValueAtTime(0.3, now + 0.05)
    gainNode1.gain.exponentialRampToValueAtTime(0.01, now + 0.2)
    
    oscillator1.start(now)
    oscillator1.stop(now + 0.2)

    // Criar oscilador para segundo tom (após pequeno delay)
    const oscillator2 = context.createOscillator()
    const gainNode2 = context.createGain()
    
    oscillator2.connect(gainNode2)
    gainNode2.connect(context.destination)
    
    // Configurar segundo tom (nota mais baixa - 660Hz = E5)
    oscillator2.frequency.value = 660
    oscillator2.type = 'sine'
    
    // Envelope ADSR para segundo tom
    const delay = 0.15
    gainNode2.gain.setValueAtTime(0, now + delay)
    gainNode2.gain.linearRampToValueAtTime(0.3, now + delay + 0.05)
    gainNode2.gain.exponentialRampToValueAtTime(0.01, now + delay + 0.25)
    
    oscillator2.start(now + delay)
    oscillator2.stop(now + delay + 0.25)

  } catch (error) {

  }
}

/**
 * Reproduz um som de alerta mais urgente (3 tons rápidos)
 */
export const playUrgentSound = async () => {
  try {
    const context = initAudioContext()
    if (!context) return

    if (context.state === 'suspended') {
      await context.resume()
    }

    const now = context.currentTime
    const beeps = [0, 0.15, 0.3] // 3 beeps

    beeps.forEach((delay) => {
      const oscillator = context.createOscillator()
      const gainNode = context.createGain()
      
      oscillator.connect(gainNode)
      gainNode.connect(context.destination)
      
      oscillator.frequency.value = 1000 // 1kHz
      oscillator.type = 'sine'
      
      gainNode.gain.setValueAtTime(0, now + delay)
      gainNode.gain.linearRampToValueAtTime(0.2, now + delay + 0.02)
      gainNode.gain.exponentialRampToValueAtTime(0.01, now + delay + 0.1)
      
      oscillator.start(now + delay)
      oscillator.stop(now + delay + 0.1)
    })

  } catch (error) {

  }
}

/**
 * Testa o som de notificação
 */
export const testNotificationSound = () => {
  playNotificationSound()
}

