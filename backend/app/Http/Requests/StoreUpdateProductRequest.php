<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest as BaseRequest;

class StoreUpdateProductRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->segment(3);

        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:255',"unique:products,name,{$id},id,tenant_id," . auth()->user()->tenant_id],
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'image' => [  'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'price' => "required|regex:/^\d+(\.\d{1,2})?$/",
            'price_cost' => "nullable|regex:/^\d+(\.\d{1,2})?$/",
            'qtd_stock' => 'required|integer|min:0',
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', 'exists:categories,uuid'],
            // Novos campos
            'promotional_price' => "nullable|regex:/^\d+(\.\d{1,2})?$/",
            'brand' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'shipping_info' => 'nullable|string|max:1000',
            'warehouse_location' => 'nullable|string|max:255',
            'variations' => 'nullable|array',
            'variations.*.type' => 'nullable|string|max:100',
            'variations.*.value' => 'nullable|string|max:255',
        ];
        if($this->method() == 'PUT'){
            $rules['image'] = ['nullable','image'];
        }

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
        
        // Se variations vier como string JSON, validar se é um JSON válido
        if ($this->has('variations') && is_string($this->variations)) {
            $variations = json_decode($this->variations, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($variations)) {
                $this->merge([
                    'variations' => $variations
                ]);
            }
        }
    }


}
