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
        // 500 (era 100): tenants de teste com milhares de clientes (ver
        // demo:seed-mass) faziam o app de campo paginar em dezenas de
        // requisições por ciclo de sync e estourar o rate limit de leitura.
        // Ver também RateLimiter 'sync' em bootstrap/app.php.
        'max_per_page' => (int) env('API_MAX_PER_PAGE', 500),
        'unpaginated_cap' => (int) env('API_UNPAGINATED_CAP', 500),
    ],
];
