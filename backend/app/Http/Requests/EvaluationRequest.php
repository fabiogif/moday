<?php

namespace App\Http\Requests;

use App\Repositories\contracts\OrderRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;

class EvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if(!$client = auth()->user())
        {
            return false;
        }
        $order = app(OrderRepositoryInterface::class)->getOrderByIdentify($this->identify);

        if(!$order)
        {
            return false;
        }

        return $client->id == $order->client_id;

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
           'stars' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|min:3|max:1000',
        ];
    }
}
