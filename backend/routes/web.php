<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rota para documentação da API (Swagger)
Route::get('/api/documentation', function () {
    return view('vendor.l5-swagger.index');
});
