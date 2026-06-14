<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado para pedidos de um tenant específico
Broadcast::channel('tenant.{tenantId}.orders', function ($user, $tenantId) {
    // Verifica se o usuário pertence ao tenant
    return $user->tenant_id === (int) $tenantId;
});

// Canal privado para dashboard de um tenant específico
Broadcast::channel('tenant.{tenantId}.dashboard', function ($user, $tenantId) {
    // Verifica se o usuário pertence ao tenant
    return $user->tenant_id === (int) $tenantId;
});

// Canal de presença para ver quem está online no dashboard
Broadcast::channel('tenant.{tenantId}.presence', function ($user, $tenantId) {
    if ($user->tenant_id === (int) $tenantId) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
    return false;
});
