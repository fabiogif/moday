<?php

namespace App\Http\Requests\Api\Visit;

use App\Models\VisitMedia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Limites espelham App\Services\FileUploadService::UPLOAD_CONFIGS
     * (visit_photo/visit_document/visit_audio) para rejeitar arquivo fora do
     * padrão já na validação (422), sem depender do catch genérico do
     * controller (que devolveria 500 via ApiResponseClass::rollback).
     */
    public function rules(): array
    {
        $limits = [
            'photo' => ['max' => 5120, 'mimes' => 'jpg,jpeg,png,webp'],
            'document' => ['max' => 10240, 'mimes' => 'pdf,doc,docx'],
            'audio' => ['max' => 15360, 'mimes' => 'mp3,m4a,wav,aac,ogg'],
        ];
        $limit = $limits[$this->input('type')] ?? ['max' => 5120, 'mimes' => null];

        return [
            'type' => ['required', 'string', Rule::in(VisitMedia::TYPES)],
            'file' => array_filter([
                'required',
                'file',
                'max:' . $limit['max'],
                $limit['mimes'] ? 'mimes:' . $limit['mimes'] : null,
            ]),
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'O tipo de mídia é obrigatório.',
            'type.in' => 'Tipo de mídia inválido.',
            'file.required' => 'O arquivo é obrigatório.',
            'file.file' => 'Envie um arquivo válido.',
        ];
    }
}
