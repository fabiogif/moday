<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Totais: pedidos de venda (B2B) + pedidos legados
        $saleOrdersCount = $this->sale_orders_count
            ?? ($this->relationLoaded('saleOrders') ? $this->saleOrders->count() : 0);
        $ordersCount = $this->orders_count
            ?? ($this->relationLoaded('orders') ? $this->orders->count() : 0);
        $totalOrders = (int) $saleOrdersCount + (int) $ordersCount;

        // Último pedido: prioriza sale_orders.ordered_at, depois orders.created_at
        $lastOrderAt = collect([
            $this->sale_orders_max_ordered_at ?? null,
            $this->orders_max_created_at ?? null,
        ])->filter()->map(fn ($d) => Carbon::parse($d))->sortDesc()->first();

        if (!$lastOrderAt && $this->relationLoaded('saleOrders') && $this->saleOrders->isNotEmpty()) {
            $lastSale = $this->saleOrders->sortByDesc(fn ($o) => $o->ordered_at ?? $o->created_at)->first();
            $lastOrderAt = Carbon::parse($lastSale->ordered_at ?? $lastSale->created_at);
        }

        if (!$lastOrderAt && $this->relationLoaded('orders') && $this->orders->isNotEmpty()) {
            $lastLegacy = $this->orders->sortByDesc('created_at')->first();
            $lastOrderAt = Carbon::parse($lastLegacy->created_at);
        }

        $lastOrderDate = $lastOrderAt?->format('d/m/Y');
        
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'cpf' => $this->cpf,
            'cnpj' => $this->cnpj,
            'company_name' => $this->company_name ?? $this->name,
            'trade_name' => $this->trade_name,
            'state_registration' => $this->state_registration,
            'client_type' => $this->client_type ?? 'outro',
            'credit_limit' => (float) ($this->credit_limit ?? 0),
            'payment_term_days' => (int) ($this->payment_term_days ?? 30),
            'price_table_id' => $this->price_table_id,
            'is_blocked' => (bool) ($this->is_blocked ?? false),
            'blocked_reason' => $this->blocked_reason,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'whatsapp' => $this->whatsapp,
            'notes' => $this->notes,
            'email' => $this->email,
            'phone' => $this->phone,
            
            // Campos de endereço
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'neighborhood' => $this->neighborhood,
            'number' => $this->number,
            'complement' => $this->complement,
            'full_address' => $this->full_address,
            'has_complete_address' => $this->hasCompleteAddress(),
            
            // Campos solicitados para a listagem
            'total_orders' => $totalOrders,
            'last_order' => $lastOrderDate,
            'last_order_raw' => $lastOrderAt?->toIso8601String(),
            'is_active' => (bool) $this->is_active, // Usar o campo real do banco de dados
            
            // Datas formatadas
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
            'created_at_formatted' => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y') : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}
