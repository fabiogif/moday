<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
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
        $tenantId = $this->route('uuid') ? 
            \App\Models\Tenant::where('uuid', $this->route('uuid'))->first()?->id : null;

        return [
            'name' => 'sometimes|required|string|max:255',
            'cnpj' => 'sometimes|nullable|string|max:18',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:100',
            'state' => 'sometimes|nullable|string|max:2',
            'zipcode' => 'sometimes|nullable|string|max:10',
            'country' => 'sometimes|nullable|string|max:100',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120|dimensions:max_width=1024,max_height=1024', // 5MB, 1024x1024
            'remove_logo' => 'sometimes|boolean',
            'settings' => 'sometimes|array',
            'settings.delivery_pickup' => 'sometimes|array',
            'settings.delivery_pickup.pickup_enabled' => 'sometimes|boolean',
            'settings.delivery_pickup.pickup_time_minutes' => 'sometimes|integer|min:0',
            'settings.delivery_pickup.pickup_discount_enabled' => 'sometimes|boolean',
            'settings.delivery_pickup.pickup_discount_percent' => 'sometimes|numeric|min:0|max:100',
            'settings.delivery_pickup.delivery_enabled' => 'sometimes|boolean',
            'settings.delivery_pickup.delivery_minimum_order_enabled' => 'sometimes|boolean',
            'settings.delivery_pickup.delivery_minimum_order_value' => 'sometimes|numeric|min:0',
            'settings.delivery_pickup.delivery_free_above_enabled' => 'sometimes|boolean',
            'settings.delivery_pickup.delivery_free_above_value' => 'sometimes|numeric|min:0',
        ];
    }
}
