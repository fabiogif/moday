<?php

namespace App\Http\Resources\Integrations\Ifood;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IfoodOrderStatusLogResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'status' => $this->status,
            'sent_to_ifood_at' => optional($this->sent_to_ifood_at)->toIso8601String(),
            'response_code' => $this->response_code,
            'response_body' => $this->response_body,
            'error_message' => $this->error_message,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}

