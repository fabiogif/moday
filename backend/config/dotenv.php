<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dotenv Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the vlucas/phpdotenv package to suppress deprecation
    | warnings in development and testing environments.
    |
    */

    'suppress_deprecation_warnings' => env('DOTENV_SUPPRESS_DEPRECATION_WARNINGS', true),
];
