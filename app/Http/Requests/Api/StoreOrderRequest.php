<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseRequest;

class StoreOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
           'token_company' => ['required', 'string'],
           'client_id' => ['nullable', 'string'],
           'table' => ['nullable', 'string'],
           'comment' => ['nullable', 'max:1000'],
           'products' => ['required', 'array'],
           'products.*.identify'=> ['required', 'string'],
           'products.*.qty'=> ['required', 'integer', 'min:1'],
           'products.*.price'=> ['nullable', 'numeric', 'min:0'],
           
           // Payment method - obrigatório se não usar split_payments
           'payment_method_id' => ['required_without:split_payments', 'nullable', 'string'],
           
           // Split payments - alternativa ao payment_method_id
           'split_payments' => ['required_without:payment_method_id', 'nullable', 'array', 'min:1'],
           'split_payments.*.payment_method_id' => ['required', 'string'],
           'split_payments.*.amount' => ['required', 'numeric', 'min:0.01'],
           'split_payments.*.needs_change' => ['nullable', 'boolean'],
           
           // Change fields (for cash payment)
           'precisa_troco' => ['nullable', 'boolean'],
           'valor_recebido' => ['nullable', 'numeric', 'min:0'],
           
           // Delivery fields
           'is_delivery' => ['boolean'],
           'use_client_address' => ['boolean'],
           'delivery_address' => ['nullable', 'string', 'max:255'],
           'delivery_city' => ['nullable', 'string', 'max:100'],
           'delivery_state' => ['nullable', 'string', 'max:50'],
           'delivery_zip_code' => ['nullable', 'string', 'max:20'],
           'delivery_neighborhood' => ['nullable', 'string', 'max:100'],
           'delivery_number' => ['nullable', 'string', 'max:20'],
           'delivery_complement' => ['nullable', 'string', 'max:100'],
           'delivery_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom validation rules
     */
    public function withValidator($validator)
    {
        $validator->after(function($validator) {
            // Custom validation for exists checks
            $this->validateExists($validator);
            
            // Validação de pagamento: deve ter payment_method_id OU split_payments
            $hasPaymentMethod = !empty($this->input('payment_method_id'));
            $hasSplitPayments = !empty($this->input('split_payments')) && is_array($this->input('split_payments')) && count($this->input('split_payments')) > 0;
            
            if (!$hasPaymentMethod && !$hasSplitPayments) {
                $validator->errors()->add('payment_method_id', 'O campo payment method id é obrigatório quando não há pagamentos divididos.');
            }
            
            // Validar split_payments se fornecido
            if ($hasSplitPayments) {
                $splitPayments = $this->input('split_payments');
                $tenantId = $this->getTenantId();
                
                foreach ($splitPayments as $index => $payment) {
                    if (empty($payment['payment_method_id'])) {
                        $validator->errors()->add("split_payments.{$index}.payment_method_id", 'O método de pagamento é obrigatório para cada pagamento dividido.');
                    } elseif ($tenantId) {
                        // Validar se o método de pagamento existe e pertence ao tenant
                        $paymentMethodExists = \App\Models\PaymentMethod::where('uuid', $payment['payment_method_id'])
                            ->where('tenant_id', $tenantId)
                            ->exists();
                        if (!$paymentMethodExists) {
                            $validator->errors()->add("split_payments.{$index}.payment_method_id", 'A forma de pagamento selecionada é inválida.');
                        }
                    }
                    
                    if (empty($payment['amount']) || $payment['amount'] <= 0) {
                        $validator->errors()->add("split_payments.{$index}.amount", 'O valor do pagamento deve ser maior que zero.');
                    }
                }
            }
            
            // Se é delivery e não usa endereço do cliente, deve ter endereço de entrega
            if ($this->input('is_delivery') && !$this->input('use_client_address')) {
                if (empty($this->input('delivery_address')) || empty($this->input('delivery_city'))) {
                    $validator->errors()->add('delivery_address', 'Endereço de entrega é obrigatório quando não usar endereço do cliente.');
                }
            }
            
            // Se não é delivery, deve ter mesa
            if (!$this->input('is_delivery') && empty($this->input('table'))) {
                $validator->errors()->add('table', 'Mesa é obrigatória para pedidos que não são delivery.');
            }
            
            // Validação de troco: se precisa_troco é true, valor_recebido é obrigatório
            if ($this->input('precisa_troco') === true || $this->input('precisa_troco') === 'true' || $this->input('precisa_troco') === 1) {
                $valorRecebido = $this->input('valor_recebido');
                if (empty($valorRecebido) || $valorRecebido <= 0) {
                    $validator->errors()->add('valor_recebido', 'O valor recebido é obrigatório quando precisa de troco.');
                }
            }
        });
    }
    
    /**
     * Get tenant ID from request
     */
    private function getTenantId(): ?int
    {
        if ($this->input('token_company')) {
            $tenant = \App\Models\Tenant::where('uuid', $this->input('token_company'))->first();
            return $tenant?->id;
        }
        
        return auth()->user()?->tenant_id;
    }
    
    /**
     * Custom exists validation
     */
    private function validateExists($validator)
    {
        $tenantId = null;
        
        // Validate tenant and get tenant_id
        if ($this->input('token_company')) {
            \Log::info('Validating token_company', ['token_company' => $this->input('token_company')]);
            
            $tenant = \App\Models\Tenant::where('uuid', $this->input('token_company'))->first();
            if (!$tenant) {
                \Log::error('Tenant not found', ['token_company' => $this->input('token_company')]);
                $validator->errors()->add('token_company', 'O tenant selecionado é inválido.');
                return; // Stop validation if tenant is invalid
            }
            $tenantId = $tenant->id;
            \Log::info('Tenant found', ['tenant_id' => $tenantId, 'tenant_name' => $tenant->name]);
        } else {
            // If no token_company, try to get from authenticated user
            $tenantId = auth()->user()?->tenant_id;
            \Log::info('Using auth user tenant_id', ['tenant_id' => $tenantId]);
        }
        
        // If we still don't have tenant_id, we can't validate properly
        if (!$tenantId) {
            $validator->errors()->add('token_company', 'Tenant é obrigatório para validação.');
            return;
        }
        
        // Validate client with tenant scope - TEMPORARILY DISABLED FOR DEBUG
        /*
        if ($this->input('client_id')) {
            \Log::info('Validating client_id', [
                'client_id' => $this->input('client_id'),
                'tenant_id' => $tenantId
            ]);
            
            $clientQuery = \App\Models\Client::where('uuid', $this->input('client_id'))
                ->where('tenant_id', $tenantId);
            
            $client = $clientQuery->first();
            
            if (!$client) {
                // Debug: Check if client exists in any tenant
                $clientAnyTenant = \App\Models\Client::where('uuid', $this->input('client_id'))->first();
                \Log::error('Client validation failed', [
                    'client_id' => $this->input('client_id'),
                    'expected_tenant_id' => $tenantId,
                    'client_exists_globally' => $clientAnyTenant ? true : false,
                    'client_actual_tenant_id' => $clientAnyTenant ? $clientAnyTenant->tenant_id : null,
                    'client_name' => $clientAnyTenant ? $clientAnyTenant->name : null
                ]);
                $validator->errors()->add('client_id', 'O cliente selecionado é inválido.');
            } else {
                \Log::info('Client validation passed', [
                    'client_name' => $client->name,
                    'client_tenant_id' => $client->tenant_id
                ]);
            }
        }
        */
        
        // Let the OrderService handle client validation for now
        if ($this->input('client_id')) {
            \Log::info('Client validation bypassed - will be handled by OrderService', [
                'client_id' => $this->input('client_id'),
                'tenant_id' => $tenantId
            ]);
        }
        
        // Validate table with tenant scope
        if ($this->input('table')) {
            $tableExists = \App\Models\Table::where('uuid', $this->input('table'))
                ->where('tenant_id', $tenantId)
                ->exists();
            if (!$tableExists) {
                $validator->errors()->add('table', 'A mesa selecionada é inválida.');
            }
        }
        
        // Validate payment method with tenant scope (apenas se não usar split_payments)
        if ($this->input('payment_method_id') && empty($this->input('split_payments'))) {
            $paymentMethodExists = \App\Models\PaymentMethod::where('uuid', $this->input('payment_method_id'))
                ->where('tenant_id', $tenantId)
                ->exists();
            if (!$paymentMethodExists) {
                $validator->errors()->add('payment_method_id', 'A forma de pagamento selecionada é inválida.');
            }
        }
        
        // Validate products with tenant scope
        if ($this->input('products')) {
            foreach ($this->input('products') as $index => $product) {
                if (isset($product['identify'])) {
                    $productExists = \App\Models\Product::where('uuid', $product['identify'])
                        ->where('tenant_id', $tenantId)
                        ->exists();
                    if (!$productExists) {
                        $validator->errors()->add("products.{$index}.identify", 'O produto selecionado é inválido.');
                    }
                }
            }
        }
    }
}
