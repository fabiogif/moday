<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TrialFingerprint;
use Illuminate\Support\Facades\Log;

class TrialFraudService
{
    public function canStartTrial(Tenant $tenant): bool
    {
        $document = $this->normalizeDocument($tenant->cnpj ?? '');

        if (empty($document)) {
            // No document on file — allow trial, log warning
            Log::warning('TrialFraud: tenant sem CNPJ/CPF', ['tenant_id' => $tenant->id]);
            return true;
        }

        return !TrialFingerprint::hasUsedTrial($document);
    }

    public function registerTrialUse(Tenant $tenant): void
    {
        $document = $this->normalizeDocument($tenant->cnpj ?? '');

        if (empty($document)) {
            return;
        }

        try {
            TrialFingerprint::register($document, $tenant->id);
        } catch (\Throwable $e) {
            Log::error('TrialFraud: falha ao registrar fingerprint', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function hasFraudFlag(Tenant $tenant): bool
    {
        $document = $this->normalizeDocument($tenant->cnpj ?? '');
        if (empty($document)) {
            return false;
        }

        $fingerprint = TrialFingerprint::where('document', $document)->first();
        return $fingerprint && $fingerprint->tenant_id !== $tenant->id;
    }

    private function normalizeDocument(string $document): string
    {
        return preg_replace('/\D/', '', $document) ?? '';
    }
}
