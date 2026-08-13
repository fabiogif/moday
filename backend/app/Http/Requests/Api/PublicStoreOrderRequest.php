<?php

namespace App\Http\Requests\Api;

use App\Models\Tenant;
use App\Services\CouponService;
use App\Services\PublicOrderCalculationService;
use DomainException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class PublicStoreOrderRequest extends FormRequest
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
            // Client data
            'client' => 'required|array',
            'client.name' => 'required|string|max:255',
            'client.email' => 'required|email|max:255',
            'client.phone' => 'required|string|max:20',
            'client.cpf' => 'nullable|string|max:14',
            
            // Delivery data
            'delivery' => 'required|array',
            'delivery.is_delivery' => 'required|boolean',
            
            // Products
            'products' => 'required|array|min:1',
            'products.*.uuid' => 'required|string|exists:products,uuid',
            'products.*.quantity' => 'required|integer|min:1',
            
            // Payment and shipping
            'payment_method' => 'required|string|exists:payment_methods,uuid',
            'shipping_method' => 'required|string|in:delivery,pickup',

            // Coupon
            'coupon_code' => 'nullable|string|max:40',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $isDelivery = $this->input('delivery.is_delivery');
            
            // Only validate delivery fields when is_delivery is true
            if ($isDelivery) {
                $this->validateDeliveryFields($validator);
            }
            
            // Validate products exist and have stock
            $this->validateProducts($validator);
            
            // Validate payment method belongs to tenant
            $this->validatePaymentMethod($validator);

            // Validate coupon if provided
            $this->validateCouponCode($validator);
        });
    }

    /**
     * Validate delivery fields when delivery is selected
     */
    private function validateDeliveryFields($validator)
    {
        $deliveryRules = [
            'delivery.address' => 'required|string|max:255',
            'delivery.number' => 'required|string|max:20',
            'delivery.neighborhood' => 'required|string|max:100',
            'delivery.city' => 'required|string|max:100',
            'delivery.state' => 'required|string|max:2',
            'delivery.zip_code' => 'required|string|max:9',
            'delivery.complement' => 'nullable|string|max:255',
            'delivery.notes' => 'nullable|string|max:500',
        ];

        foreach ($deliveryRules as $field => $rule) {
            $validator->sometimes($field, $rule, function ($input) {
                return $input->delivery['is_delivery'] === true;
            });
        }
    }

    /**
     * Validate products exist and have sufficient stock
     */
    private function validateProducts($validator)
    {
        $products = $this->input('products', []);
        
        foreach ($products as $index => $product) {
            if (isset($product['uuid'])) {
                // Get tenant from route parameter
                $slug = $this->route('slug');
                $tenant = Tenant::where('slug', $slug)->where('is_active', true)->first();
                
                if (!$tenant) {
                    $validator->errors()->add(
                        "products.{$index}.uuid", 
                        'Loja não encontrada'
                    );
                    continue;
                }
                
                $productModel = \App\Models\Product::where('uuid', $product['uuid'])
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->first();
                
                if (!$productModel) {
                    $validator->errors()->add(
                        "products.{$index}.uuid", 
                        'Produto não encontrado nesta loja'
                    );
                    continue;
                }
                
                if ($productModel->qtd_stock < $product['quantity']) {
                    $validator->errors()->add(
                        "products.{$index}.quantity", 
                        "Estoque insuficiente para {$productModel->name}. Disponível: {$productModel->qtd_stock}"
                    );
                }
            }
        }
    }

    /**
     * Validate payment method belongs to tenant
     */
    private function validatePaymentMethod($validator)
    {
        $paymentMethodUuid = $this->input('payment_method');
        $slug = $this->route('slug');
        
        if ($paymentMethodUuid) {
            $tenant = Tenant::where('slug', $slug)->where('is_active', true)->first();
            
            if (!$tenant) {
                $validator->errors()->add('payment_method', 'Loja não encontrada');
                return;
            }
            
            $paymentMethod = \App\Models\PaymentMethod::where('uuid', $paymentMethodUuid)
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->first();
            
            if (!$paymentMethod) {
                $validator->errors()->add('payment_method', 'A forma de pagamento selecionada é inválida.');
            }
        }
    }

    /**
     * Validate coupon code when provided.
     */
    private function validateCouponCode($validator): void
    {
        $couponCode = $this->input('coupon_code');
        if (!$couponCode) {
            return;
        }

        $slug = $this->route('slug');
        $tenant = Tenant::where('slug', $slug)->where('is_active', true)->first();

        if (!$tenant) {
            $validator->errors()->add('coupon_code', 'Loja não encontrada');
            return;
        }

        try {
            $calculationService = app(PublicOrderCalculationService::class);
            $couponService = app(CouponService::class);

            $calculation = $calculationService->calculateOrder($this->input('products', []), $tenant);

            $identifier = $this->input('client.email') ?? $this->input('client.phone');

            $couponService->validateForPreview(
                $tenant,
                strtoupper(trim((string) $couponCode)),
                (float) $calculation['total'],
                null,
                $identifier
            );
        } catch (DomainException $e) {
            $validator->errors()->add('coupon_code', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao validar cupom na requisição pública', [
                'error' => $e->getMessage(),
                'slug' => $slug,
                'coupon_code' => $couponCode,
            ]);
            $validator->errors()->add('coupon_code', 'Não foi possível validar o cupom no momento.');
        }
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'client.name.required' => 'Nome do cliente é obrigatório',
            'client.email.required' => 'Email do cliente é obrigatório',
            'client.email.email' => 'Email deve ter um formato válido',
            'client.phone.required' => 'Telefone do cliente é obrigatório',
            'delivery.is_delivery.required' => 'Tipo de entrega é obrigatório',
            'delivery.address.required' => 'Endereço é obrigatório para entrega',
            'delivery.number.required' => 'Número é obrigatório para entrega',
            'delivery.neighborhood.required' => 'Bairro é obrigatório para entrega',
            'delivery.city.required' => 'Cidade é obrigatória para entrega',
            'delivery.state.required' => 'Estado é obrigatório para entrega',
            'delivery.zip_code.required' => 'CEP é obrigatório para entrega',
            'products.required' => 'Selecione pelo menos um produto',
            'products.min' => 'Selecione pelo menos um produto',
            'payment_method.required' => 'Forma de pagamento é obrigatória',
            'shipping_method.required' => 'Método de entrega é obrigatório',
            'shipping_method.in' => 'Método de entrega deve ser "delivery" ou "pickup"',
            'coupon_code.max' => 'O código do cupom pode ter no máximo 40 caracteres.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'client.name' => 'Nome',
            'client.email' => 'Email',
            'client.phone' => 'Telefone',
            'client.cpf' => 'CPF',
            'delivery.address' => 'Endereço',
            'delivery.number' => 'Número',
            'delivery.neighborhood' => 'Bairro',
            'delivery.city' => 'Cidade',
            'delivery.state' => 'Estado',
            'delivery.zip_code' => 'CEP',
            'delivery.complement' => 'Complemento',
            'delivery.notes' => 'Observações',
            'products.*.uuid' => 'Produto',
            'products.*.quantity' => 'Quantidade',
            'payment_method' => 'Forma de Pagamento',
            'shipping_method' => 'Método de Entrega',
            'coupon_code' => 'Cupom',
        ];
    }
}
