<?php

namespace App\Services\Sale;

use App\Models\OfferRule;

class OfferEngineService
{
    /**
     * Avalia o carrinho contra as regras de oferta ativas do tenant: aplica
     * descontos automáticos (quantidade/combo) nos itens e retorna sugestões
     * de cross-sell para exibição na UI.
     *
     * @param array<int, array{product_id:int, quantity:float|string, discount_percent?:float|string, item_type?:string}> $items
     * @return array{items: array, suggestions: array}
     */
    public function evaluate(int $tenantId, array $items): array
    {
        if (empty($items)) {
            return ['items' => $items, 'suggestions' => []];
        }

        $rules = OfferRule::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->with('products.product:id,name')
            ->orderByDesc('priority')
            ->get();

        if ($rules->isEmpty()) {
            return ['items' => $items, 'suggestions' => []];
        }

        $quantityByProduct = [];
        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantityByProduct[$productId] = ($quantityByProduct[$productId] ?? 0) + (float) $item['quantity'];
        }

        $bestDiscount = [];
        $suggestions = [];
        $seenSuggestionKeys = [];

        foreach ($rules as $rule) {
            switch ($rule->type) {
                case 'quantity_discount':
                    $this->evaluateQuantityDiscount($rule, $quantityByProduct, $bestDiscount);
                    break;
                case 'combo':
                    $this->evaluateCombo($rule, $quantityByProduct, $bestDiscount);
                    break;
                case 'cross_sell':
                    $this->evaluateCrossSell($rule, $quantityByProduct, $suggestions, $seenSuggestionKeys);
                    break;
            }
        }

        $items = array_map(function (array $item) use ($bestDiscount) {
            if (($item['item_type'] ?? 'venda') === 'bonificacao') {
                return $item;
            }

            $productId = (int) $item['product_id'];
            if (!isset($bestDiscount[$productId])) {
                return $item;
            }

            $current = (float) ($item['discount_percent'] ?? 0);
            if ($bestDiscount[$productId]['percent'] > $current) {
                $item['discount_percent'] = $bestDiscount[$productId]['percent'];
                $item['offer_rule_id'] = $bestDiscount[$productId]['rule_id'];
            }

            return $item;
        }, $items);

        return ['items' => $items, 'suggestions' => $suggestions];
    }

    private function evaluateQuantityDiscount(OfferRule $rule, array $quantityByProduct, array &$bestDiscount): void
    {
        $trigger = $rule->products->firstWhere('role', 'trigger');
        if (!$trigger || $rule->discount_percent === null) {
            return;
        }

        $qty = $quantityByProduct[$trigger->product_id] ?? 0;
        if ($qty < $trigger->min_quantity) {
            return;
        }

        $this->applyDiscount($bestDiscount, $trigger->product_id, (float) $rule->discount_percent, $rule->id);
    }

    private function evaluateCombo(OfferRule $rule, array $quantityByProduct, array &$bestDiscount): void
    {
        $triggers = $rule->products->where('role', 'trigger');
        if ($triggers->count() < 2 || $rule->discount_percent === null) {
            return;
        }

        foreach ($triggers as $trigger) {
            $qty = $quantityByProduct[$trigger->product_id] ?? 0;
            if ($qty < $trigger->min_quantity) {
                return;
            }
        }

        foreach ($triggers as $trigger) {
            $this->applyDiscount($bestDiscount, $trigger->product_id, (float) $rule->discount_percent, $rule->id);
        }
    }

    private function evaluateCrossSell(OfferRule $rule, array $quantityByProduct, array &$suggestions, array &$seenKeys): void
    {
        $trigger = $rule->products->firstWhere('role', 'trigger');
        $result  = $rule->products->firstWhere('role', 'result');

        if (!$trigger || !$result) {
            return;
        }

        $triggerQty = $quantityByProduct[$trigger->product_id] ?? 0;
        $resultQty  = $quantityByProduct[$result->product_id] ?? 0;

        if ($triggerQty <= 0 || $resultQty > 0) {
            return;
        }

        $key = $rule->id . ':' . $result->product_id;
        if (isset($seenKeys[$key])) {
            return;
        }
        $seenKeys[$key] = true;

        $suggestions[] = [
            'rule_id'            => $rule->id,
            'rule_name'          => $rule->name,
            'trigger_product_id' => $trigger->product_id,
            'product_id'         => $result->product_id,
            'product_name'       => $result->product?->name,
        ];
    }

    private function applyDiscount(array &$bestDiscount, int $productId, float $percent, int $ruleId): void
    {
        if (!isset($bestDiscount[$productId]) || $percent > $bestDiscount[$productId]['percent']) {
            $bestDiscount[$productId] = ['percent' => $percent, 'rule_id' => $ruleId];
        }
    }
}
