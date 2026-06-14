<?php

namespace App\Helpers;

use App\Enums\CategoryStatus;

if(!function_exists('getStatusCategory'))
{
    function getStatusCategory(?string $status): string
    {
        if ($status === null) {
            return 'A'; // Status padrão 'Ativo'
        }
        return CategoryStatus::fromValue($status);
    }
}
