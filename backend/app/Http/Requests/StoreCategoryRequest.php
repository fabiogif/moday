<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest as BaseRequest;
use Illuminate\Validation\Rule;
use App\Models\Category;

class StoreCategoryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = auth()->user()?->tenant_id;
        $categoryId = $this->route('id') ?? null;

        // Soft-delete uses status = I. Inactive names can be reused.
        $nameRule = Rule::unique('categories', 'name')
            ->where(fn ($query) => $query->where('status', 'A'));

        if ($tenantId) {
            $nameRule = $nameRule->where(fn ($query) => $query->where('tenant_id', $tenantId));
        }

        if ($categoryId) {
            if (is_numeric($categoryId)) {
                $nameRule = $nameRule->ignore((int) $categoryId);
            } else {
                $existingId = Category::query()->where('uuid', $categoryId)->value('id');
                if ($existingId) {
                    $nameRule = $nameRule->ignore((int) $existingId);
                }
            }
        }

        return [
            'name'=> [
                'required',
                'string',
                'min:2',
                'max:255',
                $nameRule,
            ],
            'description' => 'nullable|string|max:500',
            'url' => 'nullable|string|max:255',
            'status' => 'nullable|in:A,I',
            'isActive' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe uma categoria ativa com este nome.',
        ];
    }
}
