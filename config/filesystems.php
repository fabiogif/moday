<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => env('FILESYSTEM_PUBLIC_DRIVER', 'local'),
            'root' => env('FILESYSTEM_PUBLIC_DRIVER') === 's3' 
                ? '' // S3 não usa root local
                : env('FILESYSTEM_PUBLIC_ROOT', storage_path('app/public')),
            'url' => env('FILESYSTEM_PUBLIC_DRIVER') === 's3' 
                ? env('AWS_URL') 
                : env('FILESYSTEM_PUBLIC_URL', env('APP_URL').'/storage'),
            'visibility' => 'public',
            'throw' => false, // Não falhar silenciosamente para melhor debugging
            'options' => [
                'CacheControl' => 'public, max-age=31536000', // 1 ano de cache
                'Metadata' => [
                    'uploaded-by' => 'moday-app',
                    'uploaded-at' => now()->toISOString(),
                ],
            ],
            // S3/Spaces configuration
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'options' => [
                'ServerSideEncryption' => 'AES256',
                'CacheControl' => 'public, max-age=31536000',
                'Metadata' => [
                    'uploaded-by' => 'moday-app',
                ],
            ],
        ],
        'volume_nyc3_01' => [
            'driver' => 'local',
            'root' => '/mnt/volume_nyc3_01/laravel-uploads',
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => true,
        ],
            

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'spaces' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'options' => [
                'ServerSideEncryption' => 'AES256',
                'CacheControl' => 'public, max-age=31536000',
            ],
        ],

        // Disco específico para imagens de produtos
        'products' => [
            'driver' => env('FILESYSTEM_PUBLIC_DRIVER', 'local'),
            'root' => env('FILESYSTEM_PUBLIC_DRIVER') === 's3' ? '' : storage_path('app/public/products'),
            'url' => env('FILESYSTEM_PUBLIC_DRIVER') === 's3' 
                ? env('AWS_URL') 
                : env('APP_URL').'/storage/products',
            'visibility' => 'public',
            'throw' => false,
            'options' => [
                'CacheControl' => 'public, max-age=31536000',
                'Metadata' => [
                    'type' => 'product-image',
                    'uploaded-by' => 'moday-app',
                ],
            ],
            // S3/Spaces configuration
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        // Disco específico para logos de empresas
        'logos' => [
            'driver' => env('FILESYSTEM_PUBLIC_DRIVER', 'local'),
            'root' => env('FILESYSTEM_PUBLIC_DRIVER') === 's3' ? '' : storage_path('app/public/logos'),
            'url' => env('FILESYSTEM_PUBLIC_DRIVER') === 's3' 
                ? env('AWS_URL') 
                : env('APP_URL').'/storage/logos',
            'visibility' => 'public',
            'throw' => false,
            'options' => [
                'CacheControl' => 'public, max-age=31536000',
                'Metadata' => [
                    'type' => 'company-logo',
                    'uploaded-by' => 'moday-app',
                ],
            ],
            // S3/Spaces configuration
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        // Disco para arquivos temporários
        'temp' => [
            'driver' => 'local',
            'root' => storage_path('app/temp'),
            'visibility' => 'private',
            'throw' => false,
        ],

        // Disco para backups
        'backups' => [
            'driver' => env('FILESYSTEM_PUBLIC_DRIVER', 'local'),
            'root' => env('FILESYSTEM_PUBLIC_DRIVER') === 's3' ? '' : storage_path('app/backups'),
            'visibility' => 'private',
            'throw' => false,
            // S3/Spaces configuration
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => env('FILESYSTEM_PUBLIC_ROOT', storage_path('app/public')),
    ],

];
