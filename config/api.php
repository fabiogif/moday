<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Limites de listagem
    |--------------------------------------------------------------------------
    |
    | max_per_page: teto para o parâmetro per_page informado pelo cliente.
    |
    | unpaginated_cap: teto aplicado quando o cliente não envia page/per_page.
    | Sem ele, um tenant com dezenas de milhares de registros derruba o
    | php-fpm por memory_limit antes de responder.
    |
    */
    'listing' => [
        'max_per_page' => (int) env('API_MAX_PER_PAGE', 100),
        'unpaginated_cap' => (int) env('API_UNPAGINATED_CAP', 500),
    ],
];
