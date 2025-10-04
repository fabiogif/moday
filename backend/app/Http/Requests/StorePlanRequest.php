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
        $id = $this->segment(3);

        return [
            'name' => ['required', 'string', 'min:3', 'max:255',"unique:plans,name,{$id},id"],
            'price' => 'required'
        ];
    }
}
