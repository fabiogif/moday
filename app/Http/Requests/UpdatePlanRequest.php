<?php

namespace App\Http\Requests;
use App\Http\Requests\BaseRequest as BaseRequest;


class UpdatePlanRequest extends BaseRequest
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
        $id = $this->route('id') ?? $this->segment(3);
        
        return [
            'name' => ['required', 'string', 'min:3', 'max:255', "unique:plans,name,{$id},id"],
            'url' => ['required', 'string', 'max:255', "unique:plans,url,{$id},id"],
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
