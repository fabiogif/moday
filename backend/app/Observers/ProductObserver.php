<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Audit\PriceHistoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductObserver
{
    public function __construct(private readonly PriceHistoryService $priceHistoryService) {}

    public function creating(Product $product): void
    {
        $product->flag = Str::kebab($product->name);
        $product->uuid = Str::uuid();
    }

    public function updating(Product $product): void
    {
        $product->flag = Str::kebab($product->name);
    }

    public function updated(Product $product): void
    {
        $this->priceHistoryService->recordChanges(
            $product,
            changedBy: Auth::id(),
        );
    }
}
