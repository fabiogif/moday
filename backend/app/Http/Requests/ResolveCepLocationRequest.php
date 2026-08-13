<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class ResolveCepLocationRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ibge' => ['nullable', 'string', 'max:10'],
            'uf' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ibge = $this->input('ibge');
            $uf = $this->input('uf');
            $city = $this->input('city');

            if (!$ibge && !($uf && $city)) {
                $validator->errors()->add(
                    'ibge',
                    'Informe o código IBGE ou a combinação de UF e cidade.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'uf.size' => 'A UF deve ter 2 caracteres',
            'city.max' => 'O nome da cidade não pode ter mais de 120 caracteres',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('uf') && is_string($this->input('uf'))) {
            $this->merge(['uf' => strtoupper($this->input('uf'))]);
        }
    }
}
