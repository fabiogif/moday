"use client"

import { useEffect, useRef, useState, useCallback } from 'react'
import { initializeEcho, disconnectEcho } from '@/lib/echo'
import type Echo from 'laravel-echo'

interface DashboardMetrics {
  total_orders: number
  total_revenue: number
  timestamp: string
}

interface UseRealtimeDashboardOptions {
  tenantId: number
  enabled?: boolean
  onMetricsUpdate?: (metrics: DashboardMetrics) => void
}

export function useRealtimeDashboard({
  tenantId,
  enabled = true,
  onMetricsUpdate,
}: UseRealtimeDashboardOptions) {
  const [isConnected, setIsConnected] = useState(false)
  const echoRef = useRef<Echo<any> | null>(null)
  const channelRef = useRef<any>(null)

  const handleMetricsUpdate = useCallback((data: { metrics: DashboardMetrics }) => {

    onMetricsUpdate?.(data.metrics)
  }, [onMetricsUpdate])

  useEffect(() => {
    if (!enabled || !tenantId) {
      return
    }

    // Initialize Echo
    const echo = initializeEcho()
    
    if (!echo) {
      setIsConnected(false)
      return
    }

    echoRef.current = echo

    // Subscribe to private channel for tenant dashboard
    const channelName = `tenant.${tenantId}.dashboard`

    const channel = echo.private(channelName)
    channelRef.current = channel

    // Listen for metrics update event
    channel.listen('.metrics.updated', handleMetricsUpdate)

    // Handle connection status
    channel.subscribed(() => {

      setIsConnected(true)
    })

    channel.error((error: any) => {

      setIsConnected(false)
    })

    // Cleanup function
    return () => {

      if (channelRef.current) {
        echo.leave(channelName)
        channelRef.current = null
      }

      disconnectEcho()
      echoRef.current = null
      setIsConnected(false)
    }
  }, [tenantId, enabled, handleMetricsUpdate])

  return {
    isConnected,
    echo: echoRef.current,
    channel: channelRef.current,
  }
}
