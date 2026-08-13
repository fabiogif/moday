'use client'

import { useState, useEffect, ReactNode } from 'react'

/**
 * Componente que só renderiza no cliente para evitar erros de hidratação
 * Use quando o componente depende de APIs do browser (localStorage, window, etc)
 */
export function ClientOnly({ 
  children,
  fallback = null 
}: { 
  children: ReactNode
  fallback?: ReactNode
}) {
  const [hasMounted, setHasMounted] = useState(false)
  
  useEffect(() => {
    setHasMounted(true)
  }, [])
  
  if (!hasMounted) {
    return <>{fallback}</>
  }
  
  return <>{children}</>
}

/**
 * Hook que retorna true apenas após o componente estar montado no cliente
 * Use para renderizar condicionalmente conteúdo que depende do browser
 */
export function useHydrated() {
  const [hydrated, setHydrated] = useState(false)
  
  useEffect(() => {
    setHydrated(true)
  }, [])
  
  return hydrated
}

/**
 * Hook para usar localStorage de forma segura com hidratação
 */
export function useLocalStorage<T>(key: string, initialValue: T) {
  const [storedValue, setStoredValue] = useState<T>(initialValue)
  const [isLoading, setIsLoading] = useState(true)
  
  useEffect(() => {
    try {
      const item = window.localStorage.getItem(key)
      if (item) {
        setStoredValue(JSON.parse(item))
      }
    } catch (error) {
      console.error(`Error reading localStorage key "${key}":`, error)
    } finally {
      setIsLoading(false)
    }
  }, [key])
  
  const setValue = (value: T | ((val: T) => T)) => {
    try {
      const valueToStore = value instanceof Function ? value(storedValue) : value
      setStoredValue(valueToStore)
      window.localStorage.setItem(key, JSON.stringify(valueToStore))
    } catch (error) {
      console.error(`Error setting localStorage key "${key}":`, error)
    }
  }
  
  return [storedValue, setValue, isLoading] as const
}
