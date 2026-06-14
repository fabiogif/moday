<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest as BaseRequest;

class StorePlanRequest extends BaseRequest
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
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:plans,name'],
            'url' => ['required', 'string', 'max:255', 'unique:plans,url'],
            'price' => ['required', 'numeric'],
            'description' => ['nullable', 'string'],
            'max_users' => ['nullable', 'numeric'],
            'max_products' => ['nullable', 'numeric'],
            'max_orders_per_month' => ['nullable', 'numeric'],
            'has_marketing' => ['nullable', 'boolean'],
            'has_order_completion_email' => ['nullable', 'boolean'],
            'has_reports' => ['nullable', 'boolean'],
            'details' => ['nullable', 'array'],
            'details.*.name' => ['required', 'string', 'max:255'],
        ];
    }
}
