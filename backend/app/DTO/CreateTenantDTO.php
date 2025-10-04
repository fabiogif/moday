<?php

namespace App\DTO;

use App\Http\Requests\StoreTenantRequest;

class CreateTenantDTO
{
    public function __construct(public string $cnpj,
                                public string $nome,
                                public string $email,
                                public string $subscription,
                                public string $expires_at)
    {
    }

    public static function makeRequestDTO(StoreTenantRequest $request): self
    {
        return new self($request->cnpj,
                        $request->nome,
                        $request->email,
                        $request->subscription,
                        $request->expires_at);
    }

}
