<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'account_type' => $this->account_type,
            'bank_code' => $this->bank_code,
            'bank_name' => $this->bank_name,
            'agency' => $this->agency,
            'agency_digit' => $this->agency_digit,
            'account_number' => $this->account_number,
            'account_digit' => $this->account_digit,
            'account_number_masked' => $this->masked_account_number,
            'account_holder_name' => $this->account_holder_name,
            'account_holder_document' => $this->account_holder_document,
            'account_holder_document_masked' => $this->masked_document,
            'account_holder_type' => $this->account_holder_type,
            'pix_key_type' => $this->pix_key_type,
            'pix_key' => $this->pix_key,
            'is_primary' => (bool) $this->is_primary,
            'is_active' => (bool) $this->is_active,
            'is_verified' => (bool) $this->is_verified,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

