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
        // Calcular total de pedidos
        $totalOrders = $this->orders ? $this->orders->count() : 0;
        
        // Buscar último pedido
        $lastOrder = $this->orders ? $this->orders->sortByDesc('created_at')->first() : null;
        $lastOrderDate = $lastOrder ? Carbon::parse($lastOrder->created_at)->format('d/m/Y') : null;
        
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'cpf' => $this->cpf,
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
            'last_order_raw' => $lastOrder ? $lastOrder->created_at : null,
            'is_active' => (bool) $this->is_active, // Usar o campo real do banco de dados
            
            // Datas formatadas
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
            'created_at_formatted' => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y') : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}
