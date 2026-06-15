<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ifood' => [
        'client_id' => env('IFOOD_CLIENT_ID'),
        'client_secret' => env('IFOOD_CLIENT_SECRET'),
        'merchant_id' => env('IFOOD_MERCHANT_ID'),
        'oauth_url' => env('IFOOD_OAUTH_URL', 'https://merchant-api.ifood.com.br/authentication/v1.0'),
        'base_url' => env('IFOOD_BASE_URL', 'https://merchant-api.ifood.com.br'),
        'catalog_base_url' => env('IFOOD_CATALOG_BASE_URL', 'https://merchant-api.ifood.com.br/catalog/v2.0'),
        'scope' => env('IFOOD_SCOPE'),
        'webhook_secret' => env('IFOOD_WEBHOOK_SECRET'),
        'authorization_code' => env('IFOOD_AUTHORIZATION_CODE'),

    ],

    'mailchimp' => [
        'api_key' => env('MAILCHIMP_API_KEY'),
        'api_url' => env('MAILCHIMP_API_URL', 'https://mandrillapp.com/api/1.0'),
    ],

    'mercadopago' => [
        'access_token'   => env('MERCADOPAGO_ACCESS_TOKEN'),
        'public_key'     => env('MERCADOPAGO_PUBLIC_KEY'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        'billing_mode'   => env('MP_BILLING_MODE', 'legacy'),
    ],

    'google_maps' => [
        'api_key'              => env('GOOGLE_MAPS_API_KEY'),
        'service_time_minutes' => (int) env('GOOGLE_MAPS_SERVICE_TIME_MINUTES', 10),
    ],

];