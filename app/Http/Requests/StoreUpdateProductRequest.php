<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest as BaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUpdateProductRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('product');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch') || !empty($id);

        $tenantId = Auth::user()?->tenant_id;

        $uniqueRule = Rule::unique('products', 'name')
            ->where(function ($query) use ($tenantId) {
                if ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                } else {
                    $query->whereNull('tenant_id');
                }
            });

        if ($isUpdate && $id) {
            $productQuery = \App\Models\Product::query();
            if (is_string($id) && Str::isUuid($id)) {
                $productQuery->where('uuid', $id);
            } elseif (is_numeric($id)) {
                $productQuery->where('id', (int) $id);
            } else {
                $productQuery->where('uuid', $id);
            }

            $product = $productQuery->first();
            if ($product) {
                $uniqueRule->ignore($product->id);
            }
        }

        $rules = [
            'name' => ['string', 'min:3', 'max:255', $uniqueRule],
            'description' => ['string', 'min:3', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'price' => ["regex:/^\d+(\.\d{1,2})?$/"],
            'price_cost' => ["nullable", "regex:/^\d+(\.\d{1,2})?$/"],
            'qtd_stock' => ['integer', 'min:0'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'exists:categories,uuid'],
            'promotional_price' => ["nullable", "regex:/^\d+(\.\d{1,2})?$/"],
            'brand' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'shipping_info' => 'nullable|string|max:1000',
            'warehouse_location' => 'nullable|string|max:255',
            'variations' => 'nullable|array',
            'variations.*.id' => 'required|string',
            'variations.*.name' => 'required|string|max:100',
            'variations.*.price' => 'required|numeric',
            'optionals' => 'nullable|array',
            'optionals.*.id' => 'required|string',
            'optionals.*.name' => 'required|string|max:100',
            'optionals.*.price' => 'required|numeric',
        ];

        if ($isUpdate) {
            foreach ($rules as $field => &$rule) {
                if (is_string($rule)) {
                    $rule = explode('|', $rule);
                }
                array_unshift($rule, 'sometimes');
            }
            return $rules;
        }

        // For creation, most fields are required
        $rules['name'] = ['required', 'string', 'min:3', 'max:255', $uniqueRule];
        $rules['description'] = ['required', 'string', 'min:3', 'max:255'];
        $rules['price'] = ['required', "regex:/^\d+(\.\d{1,2})?$/"];
        $rules['qtd_stock'] = ['required', 'integer', 'min:0'];
        $rules['categories'] = ['nullable', 'array'];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Se categories vier como string JSON, converter para array
        if ($this->has('categories') && is_string($this->categories)) {
            $this->merge([
                'categories' => json_decode($this->categories, true) ?? []
            ]);
        }

        // Se optionals vier como string JSON, converter para array
        if ($this->has('optionals')) {
            if (is_string($this->optionals)) {
                $decodedOptionals = json_decode($this->optionals, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOptionals)) {
                    $this->merge([
                        'optionals' => $decodedOptionals
                    ]);
                } else {
                    $this->merge([
                        'optionals' => []
                    ]);
                }
            } elseif (!is_array($this->optionals)) {
                $this->merge([
                    'optionals' => []
                ]);
            }
        }
        
        // Se variations vier como string JSON, validar se é um JSON válido
        if ($this->has('variations') && is_string($this->variations)) {
            $variations = json_decode($this->variations, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($variations)) {
                $this->merge([
                    'variations' => $variations
                ]);
            }
        }
        
        // Converter campos numéricos de string para número quando necessário
        $numericFields = ['price', 'price_cost', 'promotional_price', 'qtd_stock', 'weight', 'height', 'width', 'depth'];
        $data = [];
        
        foreach ($numericFields as $field) {
            if ($this->has($field) && is_string($this->$field)) {
                // Remover vírgulas e converter para float/int
                $value = str_replace(',', '.', $this->$field);
                $data[$field] = $field === 'qtd_stock' ? (int) $value : (float) $value;
            }
        }
        
        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('variations')) {
                $variations = $this->input('variations', []);
                if (!is_array($variations) || $this->hasInvalidEntries($variations, ['id', 'name', 'price'])) {
                    $validator->errors()->add('variations', 'A estrutura de variations é inválida.');
                }
            }

            if ($this->has('optionals')) {
                $optionals = $this->input('optionals', []);
                if (!is_array($optionals) || $this->hasInvalidEntries($optionals, ['id', 'name', 'price'])) {
                    $validator->errors()->add('optionals', 'A estrutura de optionals é inválida.');
                }
            }
        });
    }

    private function hasInvalidEntries(array $items, array $requiredKeys): bool
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                return true;
            }

            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $item)) {
                    return true;
                }
            }
        }

        return false;
    }
}
