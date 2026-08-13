<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountReceivableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'description' => $this->description,
            'category' => new FinancialCategoryResource($this->whenLoaded('category')),
            'client' => $this->whenLoaded('client', fn() => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'email' => $this->client->email,
                'phone' => $this->client->phone,
            ]),
            'order_id' => $this->order_id,
            'order' => $this->whenLoaded('order', fn() => [
                'id' => $this->order->id,
                'identify' => $this->order->identify,
            ]),
            'payment_method' => $this->whenLoaded('paymentMethod', fn() => [
                'uuid' => $this->paymentMethod->uuid,
                'name' => $this->paymentMethod->name,
            ]),
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'issue_date_formatted' => $this->issue_date?->format('d/m/Y'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'due_date_formatted' => $this->due_date?->format('d/m/Y'),
            'receipt_date' => $this->receipt_date?->format('Y-m-d'),
            'receipt_date_formatted' => $this->receipt_date?->format('d/m/Y'),
            'amount' => (float) $this->amount,
            'amount_received' => (float) ($this->amount_received ?? 0),
            'discount' => (float) ($this->discount ?? 0),
            'interest' => (float) ($this->interest ?? 0),
            'fine' => (float) ($this->fine ?? 0),
            'total_amount' => (float) $this->total_amount,
            'total_amount_formatted' => 'R$ ' . number_format($this->total_amount, 2, ',', '.'),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'document_number' => $this->document_number,
            'installment_number' => $this->installment_number,
            'total_installments' => $this->total_installments,
            'is_overdue' => $this->is_overdue,
            'days_until_due' => $this->days_until_due,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pendente' => 'Pendente',
            'recebido' => 'Recebido',
            'parcial' => 'Recebido Parcialmente',
            'vencido' => 'Vencido',
            'cancelado' => 'Cancelado',
            default => ucfirst($this->status),
        };
    }
}

