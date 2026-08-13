<?php

return [
    'cache' => [
        'enabled' => env('ACL_CACHE_ENABLED', true),
        'ttl' => env('ACL_CACHE_TTL', 86400), // 24 horas
    ],
    'check_method' => env('ACL_CHECK_METHOD', 'both'), // 'roles', 'permissions', 'both'
    'admin_emails' => [
        'admin@moday.com',
        'superuser@moday.com',
    ],
    'permissions' => [
        'users' => [
            'index' => 'users.index',
            'create' => 'users.create',
            'show' => 'users.show',
            'update' => 'users.update',
            'delete' => 'users.delete',
            'manage' => 'users.manage',
        ],
        'profiles' => [
            'index' => 'profiles.index',
            'create' => 'profiles.create',
            'show' => 'profiles.show',
            'update' => 'profiles.update',
            'delete' => 'profiles.delete',
            'permissions' => 'profiles.permissions',
        ],
        'permissions' => [
            'index' => 'permissions.index',
            'create' => 'permissions.create',
            'show' => 'permissions.show',
            'update' => 'permissions.update',
            'delete' => 'permissions.delete',
        ],
        'admin' => [
            'access' => 'admin.access',
            'dashboard' => 'admin.dashboard',
            'settings' => 'admin.settings',
        ],
        'products' => [
            'index' => 'products.index',
            'create' => 'products.create',
            'show' => 'products.show',
            'update' => 'products.update',
            'delete' => 'products.delete',
        ],
        'orders' => [
            'index' => 'orders.index',
            'create' => 'orders.create',
            'show' => 'orders.show',
            'update' => 'orders.update',
            'delete' => 'orders.delete',
        ],
        'categories' => [
            'index' => 'categories.index',
            'create' => 'categories.create',
            'show' => 'categories.show',
            'update' => 'categories.update',
            'delete' => 'categories.delete',
        ],
        'tables' => [
            'index' => 'tables.index',
            'create' => 'tables.create',
            'show' => 'tables.show',
            'update' => 'tables.update',
            'delete' => 'tables.delete',
        ],
        'clients' => [
            'index' => 'clients.index',
            'create' => 'clients.create',
            'show' => 'clients.show',
            'update' => 'clients.update',
            'delete' => 'clients.delete',
        ],
        'reports' => [
            'index' => 'reports.index',
            'create' => 'reports.create',
            'show' => 'reports.show',
            'export' => 'reports.export',
        ],
    ],
];
