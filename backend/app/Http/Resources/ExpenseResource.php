<?php

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'description' => $this->description,
            'category' => new FinancialCategoryResource($this->whenLoaded('category')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'payment_method' => $this->whenLoaded('paymentMethod', fn() => [
                'uuid' => $this->paymentMethod->uuid,
                'name' => $this->paymentMethod->name,
            ]),
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'issue_date_formatted' => $this->issue_date?->format('d/m/Y'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'due_date_formatted' => $this->due_date?->format('d/m/Y'),
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'payment_date_formatted' => $this->payment_date?->format('d/m/Y'),
            'amount' => (float) $this->amount,
            'amount_formatted' => 'R$ ' . number_format($this->amount, 2, ',', '.'),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'recurrence' => $this->recurrence,
            'recurrence_label' => $this->getRecurrenceLabel(),
            'is_overdue' => $this->is_overdue,
            'days_until_due' => $this->days_until_due,
            'notes' => $this->notes,
            'attachment_path' => $this->attachment_path,
            'attachment_url' => ImageHelper::publicAssetPath($this->attachment_path, 'public'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pendente' => 'Pendente',
            'pago' => 'Pago',
            'cancelado' => 'Cancelado',
            default => $this->status,
        };
    }

    private function getRecurrenceLabel(): string
    {
        if (!$this->recurrence) {
            return 'Único';
        }
        
        return match($this->recurrence) {
            'unico' => 'Único',
            'mensal' => 'Mensal',
            'trimestral' => 'Trimestral',
            'anual' => 'Anual',
            default => ucfirst($this->recurrence),
        };
    }
}

