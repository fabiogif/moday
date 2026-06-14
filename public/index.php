<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// CORS handled by Digital Ocean App Platform (app.yaml)
// No need to handle OPTIONS here anymore

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
