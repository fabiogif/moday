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

    'evolution_api' => [
        'url' => env('EVOLUTION_API_URL'),
        'key' => env('EVOLUTION_API_KEY'),
    ],

    'cosmos' => [
        'token' => env('COSMOS_API_TOKEN'),
        'base_url' => env('COSMOS_API_BASE_URL', 'https://api.cosmos.bluesoft.com.br'),
        'timeout' => (int) env('COSMOS_API_TIMEOUT', 2),
    ],

    'open_food_facts' => [
        'base_url' => env('OPENFOODFACTS_BASE_URL', 'https://world.openfoodfacts.org'),
        'timeout' => (int) env('OPENFOODFACTS_TIMEOUT', 3),
        'user_agent' => env('OPENFOODFACTS_USER_AGENT', 'DistribTec/1.0 (barcode-lookup)'),
    ],

];