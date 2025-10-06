"use client"

import { useEffect, useRef, useState } from 'react'
import { initializeEcho, disconnectEcho } from '@/lib/echo'
import type Echo from 'laravel-echo'

interface UseRealtimeOrdersOptions {
  tenantId: number
  onOrderCreated?: (order: any) => void
  onOrderUpdated?: (order: any) => void
  onOrderStatusUpdated?: (data: { order: any; oldStatus: string; newStatus: string }) => void
  enabled?: boolean
}

export function useRealtimeOrders({
  tenantId,
  onOrderCreated,
  onOrderUpdated,
  onOrderStatusUpdated,
  enabled = true,
}: UseRealtimeOrdersOptions) {
  const [isConnected, setIsConnected] = useState(false)
  const echoRef = useRef<Echo<any> | null>(null)
  const channelRef = useRef<any>(null)

  useEffect(() => {
    if (!enabled || !tenantId) {
      return
    }

    // Initialize Echo
    const echo = initializeEcho()
    
    if (!echo) {
      console.warn('useRealtimeOrders: WebSocket not available (optional feature)')
      setIsConnected(false)
      return
    }

    echoRef.current = echo

    // Subscribe to private channel for tenant orders
    const channelName = `tenant.${tenantId}.orders`
    console.log(`useRealtimeOrders: Subscribing to channel: ${channelName}`)

    const channel = echo.private(channelName)
    channelRef.current = channel

    // Listen for order created event
    if (onOrderCreated) {
      channel.listen('.order.created', (data: { order: any; timestamp: string }) => {
        console.log('useRealtimeOrders: Order created event received', data)
        onOrderCreated(data.order)
      })
    }

    // Listen for order updated event
    if (onOrderUpdated) {
      channel.listen('.order.updated', (data: { order: any; timestamp: string }) => {
        console.log('useRealtimeOrders: Order updated event received', data)
        onOrderUpdated(data.order)
      })
    }

    // Listen for order status updated event
    if (onOrderStatusUpdated) {
      channel.listen('.order.status.updated', (data: { order: any; oldStatus: string; newStatus: string; timestamp: string }) => {
        console.log('useRealtimeOrders: Order status updated event received', data)
        onOrderStatusUpdated({
          order: data.order,
          oldStatus: data.oldStatus,
          newStatus: data.newStatus,
        })
      })
    }

    // Handle connection status
    channel.subscribed(() => {
      console.log(`useRealtimeOrders: Successfully subscribed to ${channelName}`)
      setIsConnected(true)
    })

    channel.error((error: any) => {
      console.error(`useRealtimeOrders: Channel error on ${channelName}`, error)
      setIsConnected(false)
    })

    // Cleanup function
    return () => {
      console.log(`useRealtimeOrders: Cleaning up channel ${channelName}`)
      
      if (channelRef.current) {
        echo.leave(channelName)
        channelRef.current = null
      }

      disconnectEcho()
      echoRef.current = null
      setIsConnected(false)
    }
  }, [tenantId, enabled, onOrderCreated, onOrderUpdated, onOrderStatusUpdated])

  return {
    isConnected,
    echo: echoRef.current,
    channel: channelRef.current,
  }
}

interface UsePresenceOptions {
  tenantId: number
  onJoin?: (user: any) => void
  onLeave?: (user: any) => void
  onHere?: (users: any[]) => void
  enabled?: boolean
}

export function usePresence({
  tenantId,
  onJoin,
  onLeave,
  onHere,
  enabled = true,
}: UsePresenceOptions) {
  const [users, setUsers] = useState<any[]>([])
  const [isConnected, setIsConnected] = useState(false)
  const echoRef = useRef<Echo<any> | null>(null)
  const channelRef = useRef<any>(null)

  useEffect(() => {
    if (!enabled || !tenantId) {
      return
    }

    // Initialize Echo
    const echo = initializeEcho()
    
    if (!echo) {
      console.warn('usePresence: WebSocket not available (optional feature)')
      setIsConnected(false)
      return
    }

    echoRef.current = echo

    // Subscribe to presence channel
    const channelName = `tenant.${tenantId}.presence`
    console.log(`usePresence: Joining presence channel: ${channelName}`)

    const channel = echo.join(channelName)
    channelRef.current = channel

    // Handle who's already in the channel
    channel.here((presentUsers: any[]) => {
      console.log('usePresence: Users already present', presentUsers)
      setUsers(presentUsers)
      setIsConnected(true)
      if (onHere) {
        onHere(presentUsers)
      }
    })

    // Handle someone joining
    channel.joining((user: any) => {
      console.log('usePresence: User joined', user)
      setUsers((prev) => [...prev, user])
      if (onJoin) {
        onJoin(user)
      }
    })

    // Handle someone leaving
    channel.leaving((user: any) => {
      console.log('usePresence: User left', user)
      setUsers((prev) => prev.filter((u) => u.id !== user.id))
      if (onLeave) {
        onLeave(user)
      }
    })

    channel.error((error: any) => {
      console.error(`usePresence: Channel error on ${channelName}`, error)
      setIsConnected(false)
    })

    // Cleanup function
    return () => {
      console.log(`usePresence: Leaving presence channel ${channelName}`)
      
      if (channelRef.current) {
        echo.leave(channelName)
        channelRef.current = null
      }

      disconnectEcho()
      echoRef.current = null
      setIsConnected(false)
      setUsers([])
    }
  }, [tenantId, enabled, onJoin, onLeave, onHere])

  return {
    users,
    isConnected,
    echo: echoRef.current,
    channel: channelRef.current,
  }
}
