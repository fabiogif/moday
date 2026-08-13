<?php

namespace App\Services\Regulatory;

use App\Exceptions\RegulatoryException;
use App\Models\Batch;
use App\Models\Product;
use App\Models\SaleOrder;

class RegulatoryValidationService
{
    public function validateSaleOrder(SaleOrder $order): void
    {
        $order->load('items.product');

        $hasControlled = $order->items->contains(
            fn ($item) => $item->product?->controlled_substance || $item->product?->requires_prescription
        );

        if ($hasControlled && !$order->prescription_verified) {
            $name = $order->items->first(
                fn ($i) => $i->product?->controlled_substance || $i->product?->requires_prescription
            )?->product?->name ?? 'medicamento controlado';

            throw RegulatoryException::controlledRequiresPrescription($name);
        }

    }

    public function validateReservedItems(SaleOrder $order): void
    {
        $order->load('items.product');

        foreach ($order->items as $item) {
            $this->validateProductWarehouseCompatibility($item->product, $item->batch_id);
        }
    }

    public function validateProductWarehouseCompatibility(?Product $product, ?int $batchId): void
    {
        if (!$product || !$batchId) {
            return;
        }

        $required = $product->storage_temperature;
        if (!in_array($required, ['refrigerated', 'frozen'], true)) {
            return;
        }

        $batch = Batch::with('warehouse')->find($batchId);
        if (!$batch?->warehouse) {
            return;
        }

        $allowed = match ($required) {
            'refrigerated' => ['refrigerated', 'frozen'],
            'frozen'       => ['frozen'],
            default        => ['ambient', 'refrigerated', 'frozen'],
        };

        if (!in_array($batch->warehouse->type, $allowed, true)) {
            throw RegulatoryException::coldChainViolation(
                $product->name,
                $required,
                $batch->warehouse->type
            );
        }
    }
}
