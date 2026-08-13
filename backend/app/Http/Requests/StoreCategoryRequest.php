<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseRequest as BaseRequest;

class StoreCategoryRequest extends BaseRequest
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
        $tenantId = $this->user()?->tenant_id;
        $identify = $this->route('identify');

        $uniqueName = Rule::unique('categories', 'name')
            ->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'A'));

        if ($identify !== null) {
            $uniqueName = $uniqueName->ignore($identify, is_numeric($identify) ? 'id' : 'uuid');
        }

        return [
            'name'=> ['required', 'string', 'min:2', 'max:255', $uniqueName],
            'description' => 'nullable|string|max:500',
            'url' => 'nullable|string|max:255',
        ];
    }
}
