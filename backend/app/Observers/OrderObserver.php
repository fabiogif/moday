<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CacheService;

class OrderObserver
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->invalidateCache($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        $this->invalidateCache($order);
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        $this->invalidateCache($order);
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        $this->invalidateCache($order);
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        $this->invalidateCache($order);
    }

    /**
     * Invalidate cache for the order's tenant
     */
    protected function invalidateCache(Order $order): void
    {
        if ($order->tenant_id) {
            $this->cacheService->invalidateOrderCache($order->tenant_id);
            $this->cacheService->invalidateDashboardCache($order->tenant_id);
        }
    }
}
