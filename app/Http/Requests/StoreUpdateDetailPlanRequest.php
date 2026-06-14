<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateDetailPlanRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $url = $this->segment(3);
        $idDetailPlan = $this->segment(5);
        return [
            'name' => 'required|min:3',
            'plan_url' => 'required|exists:plans,url,{$url}',
            'id' => 'required|exists:detail_plans,id, {$idDetailPlan}',
        ];
    }
}
