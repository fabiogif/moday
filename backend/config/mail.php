<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "log", "array", "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => in_array(env('MAIL_ENCRYPTION'), [null, '', 'null'], true) ? null : env('MAIL_ENCRYPTION', 'tls'),
            'username' => in_array(env('MAIL_USERNAME'), [null, '', 'null'], true) ? null : env('MAIL_USERNAME'),
            'password' => in_array(env('MAIL_PASSWORD'), [null, '', 'null'], true) ? null : env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'DistribTec'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand (templates de e-mail)
    |--------------------------------------------------------------------------
    |
    | Paleta alinhada ao Design System do frontend (globals.css → :root).
    | Hex estáveis para clientes de e-mail (sem oklch/CSS variables).
    |
    */
    'brand' => [
        'name' => env('MAIL_BRAND_NAME', 'Alba Tec'),
        'tagline' => env('MAIL_BRAND_TAGLINE', 'Sistema de Gestão para Restaurantes'),
        'logo_path' => '/brand/iconfundotranparente.png',
        // oklch(0.489 0.108 230.6) → primary
        'primary' => '#006A91',
        'primary_dark' => '#005577',
        // oklch(0.957 0.006 230.0)
        'secondary' => '#EDF2F4',
        // oklch(0.975 0.005 248.0)
        'muted' => '#F4F7FA',
        // oklch(0.420 0.020 230.0)
        'muted_foreground' => '#424F56',
        // oklch(0.150 0 0)
        'foreground' => '#0B0B0B',
        // oklch(0.895 0.012 248.0)
        'border' => '#D6DDE4',
        // oklch(0.793 0.094 83.2)
        'gold' => '#D9B674',
        'surface' => '#FFFFFF',
        'radius' => '8px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Provider
    |--------------------------------------------------------------------------
    |
    | Define qual provedor de e-mail será usado pelo sistema de adaptadores.
    | Opções: 'smtp', 'ses', 'postmark', 'mailchimp'
    |
    */

    'provider' => env('MAIL_PROVIDER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Contact Form Recipient
    |--------------------------------------------------------------------------
    */

    'contact_to' => env('MAIL_CONTACT_TO', 'contato@distribtec.com.br'),

    /*
    |--------------------------------------------------------------------------
    | Support & Financial Recipients
    |--------------------------------------------------------------------------
    */

    'support_to' => env('MAIL_SUPPORT_TO', 'atendimento@distribtec.com.br'),

    'pix_to' => env('MAIL_PIX_TO', 'pix@distribtec.com.br'),

];
