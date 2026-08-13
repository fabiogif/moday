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
        $product = null;

        $uniqueRule = Rule::unique('products', 'name')
            ->where(function ($query) use ($tenantId) {
                $query->whereNull('deleted_at');
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

        $barcodeUniqueRule = Rule::unique('products', 'barcode')
            ->where(function ($query) use ($tenantId) {
                $query->whereNull('deleted_at');
                if ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                } else {
                    $query->whereNull('tenant_id');
                }
            });

        if ($isUpdate && $product) {
            $barcodeUniqueRule->ignore($product->id);
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
            'barcode' => [
                'nullable',
                'string',
                'regex:/^\d+$/',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $len = strlen((string) $value);
                    if (!in_array($len, [8, 12, 13], true)) {
                        $fail('Código de barras inválido. Use EAN-8 (8 dígitos), UPC-A (12) ou EAN-13 (13).');
                    }
                },
                $barcodeUniqueRule,
            ],
            'weight' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string|max:2048',
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
        $numericFields = ['price', 'price_cost', 'promotional_price', 'qtd_stock', 'weight', 'volume', 'height', 'width', 'depth'];
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

        if ($this->has('barcode') && is_string($this->barcode)) {
            $this->merge([
                'barcode' => trim($this->barcode) !== '' ? trim($this->barcode) : null,
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'O campo nome já está sendo utilizado.',
            'barcode.regex' => 'O código de barras deve conter apenas números.',
            'barcode.unique' => 'Já existe um produto cadastrado com este código de barras.',
            'barcode.max' => 'Código de barras inválido.',
        ];
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
