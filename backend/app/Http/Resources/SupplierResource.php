<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            // B2B-friendly names (with fallback to legacy column names)
            'company_name'      => $this->company_name ?? $this->name,
            'trade_name'        => $this->trade_name ?? $this->fantasy_name,
            'cnpj'              => $this->cnpj ?? ($this->document_type === 'cnpj' ? $this->document : null),
            'state_registration'=> $this->state_registration,
            // Legacy fields kept for backward compatibility
            'name'              => $this->company_name ?? $this->name,
            'fantasy_name'      => $this->trade_name ?? $this->fantasy_name,
            'document'          => $this->document,
            'document_type'     => $this->document_type ?? 'cnpj',
            // Contact
            'email'             => $this->email,
            'phone'             => $this->phone,
            'whatsapp'          => $this->whatsapp,
            'contact_name'      => $this->contact_name,
            'contact_phone'     => $this->contact_phone,
            // B2B commercial terms
            'payment_term_days' => (int) ($this->payment_term_days ?? 30),
            'lead_time_days'    => (int) ($this->lead_time_days ?? 7),
            'min_order_value'   => $this->min_order_value,
            // Address
            'address'           => $this->address,
            'number'            => $this->number,
            'complement'        => $this->complement,
            'neighborhood'      => $this->neighborhood,
            'city'              => $this->city,
            'state'             => $this->state,
            'zip_code'          => $this->zip_code,
            'full_address'      => $this->full_address,
            // Banking
            'bank_name'         => $this->bank_name,
            'bank_agency'       => $this->bank_agency,
            'bank_account'      => $this->bank_account,
            'pix_key'           => $this->pix_key,
            'notes'             => $this->notes,
            'is_active'         => (bool) $this->is_active,
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}

