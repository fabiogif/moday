<?php

use App\Http\Controllers\Auth\AuthController;

// Incluir rotas de teste
require_once __DIR__ . '/test.php';
use App\Http\Controllers\{Api\Auth\AuthClientController,
    Api\Auth\RegisterApiController,
    Api\CategoryApiController,
    Api\ClientApiController,
    Api\PlanApiController,
    Api\DetailPlanApiController,
    Api\TableApiController,
    Api\OrderApiController,
    Api\ProductApiController,
    Api\EvaluationApiController,
    Api\TenantApiController,
    Api\UserApiController,
    Api\ProfileApiController,
    Api\PermissionApiController,
    Api\PermissionProfileApiController,
    Api\OrderStatsApiController,
    Api\UserStatsApiController,
    Api\PaymentMethodApiController,
    Api\DashboardApiController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * @OA\Get(
 *     path="/api/health",
 *     summary="Verificação de saúde da API",
 *     description="Retorna o status da API e informações básicas",
 *     tags={"Health Check"},
 *     @OA\Response(
 *         response=200,
 *         description="API funcionando normalmente",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="ok"),
 *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-09-28T18:30:00.000000Z"),
 *             @OA\Property(property="version", type="string", example="1.0.0")
 *         )
 *     )
 * )
 */
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0')
    ]);
});

// Rotas de autenticação públicas
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Rotas protegidas por JWT
Route::middleware(['auth:api'])->group(function () {
    // Autenticação
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});
// Rotas públicas (sem autenticação)

// Rotas protegidas por JWT e tenant
Route::middleware(['auth:api'])->group(function () {
    // Produtos
    Route::put('/product/{id}', [ProductApiController::class , 'update']);
    Route::get('/product', [ProductApiController::class , 'productsByAuthenticatedUser']);
    Route::get('/product/stats', [ProductApiController::class , 'stats']); // Added
    Route::get('/product/{identify}/similar', [ProductApiController::class , 'similarProducts']); // Produtos similares
    Route::get('/product/{identify}', [ProductApiController::class , 'show']);
    Route::post('/product', [ProductApiController::class , 'store']);
    Route::delete('/product/{identify}', [ProductApiController::class , 'delete']);

    // Pedidos
    Route::get('/order', [OrderApiController::class , 'index']);
    Route::post('/order', [OrderApiController::class , 'store']);
    Route::post('/order/{identify}/evaluations', [EvaluationApiController::class , 'store']);
    Route::get('/order/client/', [OrderApiController::class , 'orderByClient']);
    Route::get('/order/{identify}', [OrderApiController::class , 'show']);
    Route::put('/order/{identify}', [OrderApiController::class , 'update']);
    Route::delete('/order/{identify}', [OrderApiController::class , 'delete']);
    Route::post('/order/{identify}/invoice', [OrderApiController::class , 'invoice']);
    Route::get('/order/{identify}/receipt', [OrderApiController::class , 'receipt']);

    // Mesas
    Route::post('/table', [TableApiController::class , 'store']);
    Route::get('/table', [TableApiController::class , 'index']);
    Route::get('/table/stats', [TableApiController::class , 'stats']);
    Route::get('/table/{identify}', [TableApiController::class , 'show']);
    Route::put('/table/{id}', [TableApiController::class , 'update']);
    Route::delete('/table/{identify}', [TableApiController::class , 'delete']);

    // Categorias (protegidas)
    Route::get('/category', [CategoryApiController::class , 'index']);
    Route::get('/category/stats', [CategoryApiController::class , 'stats']);
    Route::post('/category', [CategoryApiController::class , 'store']);
    Route::get('/category/{identify}', [CategoryApiController::class , 'show']);
    Route::put('/category/{id}', [CategoryApiController::class , 'update']);
    Route::delete('/category/{identify}', [CategoryApiController::class , 'delete']);
    

    // Usuários
    Route::get('/user', [UserApiController::class , 'index']);
    Route::post('/user', [UserApiController::class , 'store']);
    Route::get('/user/{user}', [UserApiController::class , 'show']);
    Route::put('/user/{user}', [UserApiController::class , 'update']);
    Route::delete('/user/{user}', [UserApiController::class , 'destroy']);

    // Clientes (protegidas por autenticação)
    Route::get('/client', [ClientApiController::class, 'index']);
    Route::get('/client/stats', [ClientApiController::class, 'stats']);
    Route::post('/client', [ClientApiController::class, 'store']);
    Route::get('/client/{id}', [ClientApiController::class, 'show']);
    Route::put('/client/{id}', [ClientApiController::class, 'update']);
    Route::delete('/client/{id}', [ClientApiController::class, 'destroy']);

    // Estatísticas de pedidos
    Route::get('/order/stats', [OrderStatsApiController::class, 'stats']);

    // Estatísticas de usuários
    Route::get('/user/stats', [UserStatsApiController::class, 'stats']);


    // Dashboard
    Route::get('/dashboard', [DashboardApiController::class, 'index']);
});


// Cliente (movido para dentro do middleware auth:api)

Route::get('/tenant', [TenantApiController::class , 'index']);
Route::get('/tenant/{uuid}', [TenantApiController::class , 'show']);
Route::post('/tenant', [TenantApiController::class , 'store']);


Route::get('/plan/{id}/details', [DetailPlanApiController::class , 'index']);
Route::post('/plan/{id}/details', [DetailPlanApiController::class , 'store']);
Route::put('/plan/{url}/details/{idDetail}', [DetailPlanApiController::class , 'update']);

Route::get('/plan', [PlanApiController::class , 'index']);
Route::get('/plan/{id}', [PlanApiController::class , 'show']);
Route::post('/plan', [PlanApiController::class , 'store']);
Route::delete('/plan/{id}', [PlanApiController::class , 'delete']);
Route::put('/plan/{id}', [PlanApiController::class , 'update']);

// Rotas para gestão de usuários, perfis e permissões
Route::middleware(['auth:api'])->group(function () {
    // Usuários
    Route::prefix('users')->group(function () {
        Route::get('/', [UserApiController::class, 'index'])->middleware('acl.permission:users.index');
        Route::post('/', [UserApiController::class, 'store'])->middleware('acl.permission:users.create');
        Route::get('/{id}', [UserApiController::class, 'show'])->middleware('acl.permission:users.show');
        Route::put('/{id}', [UserApiController::class, 'update'])->middleware('acl.permission:users.update');
        Route::delete('/{id}', [UserApiController::class, 'destroy'])->middleware('acl.permission:users.delete');
        Route::post('/{id}/assign-profile', [UserApiController::class, 'assignProfile'])->middleware('acl.permission:users.update');
        Route::put('/{id}/change-password', [UserApiController::class, 'changePassword'])->middleware('acl.permission:users.update');
        Route::get('/{id}/permissions', [UserApiController::class, 'getUserPermissions'])->middleware('acl.permission:users.show');
    });

    // Perfis
    Route::prefix('profiles')->group(function () {
        Route::get('/', [ProfileApiController::class, 'index']);
        Route::post('/', [ProfileApiController::class, 'store']);
        Route::get('/{id}', [ProfileApiController::class, 'show']);
        Route::put('/{id}', [ProfileApiController::class, 'update']);
        Route::delete('/{id}', [ProfileApiController::class, 'destroy']);
        
        // Gerenciar permissões do perfil
        Route::get('/{id}/permissions', [PermissionProfileApiController::class, 'getProfilePermissions']);
        Route::get('/{id}/permissions/available', [PermissionProfileApiController::class, 'getAvailablePermissionsForProfile']);
        Route::post('/{id}/permissions', [PermissionProfileApiController::class, 'attachPermissionToProfile']);
        Route::delete('/{id}/permissions/{permissionId}', [PermissionProfileApiController::class, 'detachPermissionFromProfile']);
        Route::put('/{id}/permissions/sync', [PermissionProfileApiController::class, 'syncPermissionsForProfile']);
    });

    // Permissões
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionApiController::class, 'index']);
        Route::post('/', [PermissionApiController::class, 'store']);
        Route::get('/{id}', [PermissionApiController::class, 'show']);
        Route::put('/{id}', [PermissionApiController::class, 'update']);
        Route::delete('/{id}', [PermissionApiController::class, 'destroy']);
        Route::get('/{id}/usage', [PermissionApiController::class, 'checkUsage']);
        Route::get('/{id}/profiles', [PermissionProfileApiController::class, 'getPermissionProfiles']);
    });

    // Roles
    Route::prefix('role')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\RoleApiController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\RoleApiController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\RoleApiController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\RoleApiController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\RoleApiController::class, 'destroy']);
        Route::get('/{id}/permissions', [\App\Http\Controllers\Api\RoleApiController::class, 'getRolePermissions']);
        Route::post('/{id}/permissions', [\App\Http\Controllers\Api\RoleApiController::class, 'attachPermissionToRole']);
        Route::delete('/{id}/permissions/{permissionId}', [\App\Http\Controllers\Api\RoleApiController::class, 'detachPermissionFromRole']);
        Route::put('/{id}/permissions/sync', [\App\Http\Controllers\Api\RoleApiController::class, 'syncPermissionsForRole']);
    });

    // Profile (alias para profiles)
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileApiController::class, 'index']);
        Route::post('/', [ProfileApiController::class, 'store']);
        Route::get('/{id}', [ProfileApiController::class, 'show']);
        Route::put('/{id}', [ProfileApiController::class, 'update']);
        Route::delete('/{id}', [ProfileApiController::class, 'destroy']);
        
        // Gerenciar permissões do perfil
        Route::get('/{id}/permissions', [PermissionProfileApiController::class, 'getProfilePermissions']);
        Route::get('/{id}/permissions/available', [PermissionProfileApiController::class, 'getAvailablePermissionsForProfile']);
        Route::post('/{id}/permissions', [PermissionProfileApiController::class, 'attachPermissionToProfile']);
        Route::delete('/{id}/permissions/{permissionId}', [PermissionProfileApiController::class, 'detachPermissionFromProfile']);
        Route::put('/{id}/permissions/sync', [PermissionProfileApiController::class, 'syncPermissionsForProfile']);
    });

    // Formas de Pagamento
    Route::prefix('payment-methods')->group(function () {
        Route::get('/', [PaymentMethodApiController::class, 'index']);
        Route::post('/', [PaymentMethodApiController::class, 'store']);
        Route::get('/active', [PaymentMethodApiController::class, 'active']);
        Route::get('/{uuid}', [PaymentMethodApiController::class, 'show']);
        Route::put('/{uuid}', [PaymentMethodApiController::class, 'update']);
        Route::delete('/{uuid}', [PaymentMethodApiController::class, 'destroy']);
    });
});




