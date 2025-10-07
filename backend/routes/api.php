<?php

use App\Http\Controllers\Auth\AuthController;

// Incluir rotas de teste
require_once __DIR__ . '/test.php';
use App\Http\Controllers\{Api\Auth\AuthClientController,
    Api\Auth\RegisterApiController,
    Api\CategoryApiController,
    Api\ClientApiController,
    Api\ClientAuthController,
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
    Api\DashboardApiController,
    Api\DashboardMetricsController,
    Api\CsrfTokenController,
    Api\PublicStoreController};
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

/**
 * @OA\Get(
 *     path="/api/csrf-token",
 *     summary="Obter token CSRF para proteção de requisições",
 *     description="Retorna um token CSRF válido que deve ser incluído em requisições POST, PUT, PATCH e DELETE",
 *     tags={"CSRF"},
 *     @OA\Response(
 *         response=200,
 *         description="Token CSRF gerado com sucesso"
 *     )
 * )
 */
Route::get('/csrf-token', [CsrfTokenController::class, 'getToken']);
Route::post('/csrf-token/verify', [CsrfTokenController::class, 'verifyToken']);

// Rotas de autenticação públicas com rate limiting específico
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
});

// Rotas protegidas por JWT
Route::middleware(['auth:api'])->group(function () {
    // Autenticação
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Broadcasting authentication
    Route::post('/broadcasting/auth', function (Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    });
});
// Rotas públicas (sem autenticação)

// Rotas protegidas por JWT e tenant
Route::middleware(['auth:api'])->group(function () {
    // Produtos
    Route::get('/product', [ProductApiController::class , 'productsByAuthenticatedUser'])->middleware('throttle:read');
    Route::get('/product/stats', [ProductApiController::class , 'stats'])->middleware('throttle:read');
    Route::get('/product/{identify}/similar', [ProductApiController::class , 'similarProducts'])->middleware('throttle:read');
    Route::get('/product/{identify}', [ProductApiController::class , 'show'])->middleware('throttle:read');
    Route::post('/product', [ProductApiController::class , 'store'])->middleware('throttle:critical');
    Route::put('/product/{id}', [ProductApiController::class , 'update'])->middleware('throttle:critical');
    Route::delete('/product/{identify}', [ProductApiController::class , 'delete'])->middleware('throttle:critical');

    // Pedidos
    Route::get('/order', [OrderApiController::class , 'index'])->middleware('throttle:read');
    Route::get('/order/stats', [OrderStatsApiController::class, 'stats'])->middleware('throttle:read');
    Route::get('/order/client/', [OrderApiController::class , 'orderByClient'])->middleware('throttle:read');
    Route::get('/order/{identify}', [OrderApiController::class , 'show'])->middleware('throttle:read');
    Route::get('/order/{identify}/receipt', [OrderApiController::class , 'receipt'])->middleware('throttle:read');
    Route::post('/order', [OrderApiController::class , 'store'])->middleware('throttle:critical');
    Route::put('/order/{identify}', [OrderApiController::class , 'update'])->middleware('throttle:critical');
    Route::delete('/order/{identify}', [OrderApiController::class , 'delete'])->middleware('throttle:critical');
    Route::post('/order/{identify}/evaluations', [EvaluationApiController::class , 'store'])->middleware('throttle:critical');
    Route::post('/order/{identify}/invoice', [OrderApiController::class , 'invoice'])->middleware('throttle:critical');

    // Mesas
    Route::get('/table', [TableApiController::class , 'index'])->middleware('throttle:read');
    Route::get('/table/stats', [TableApiController::class , 'stats'])->middleware('throttle:read');
    Route::get('/table/{identify}', [TableApiController::class , 'show'])->middleware('throttle:read');
    Route::post('/table', [TableApiController::class , 'store'])->middleware('throttle:critical');
    Route::put('/table/{id}', [TableApiController::class , 'update'])->middleware('throttle:critical');
    Route::delete('/table/{identify}', [TableApiController::class , 'delete'])->middleware('throttle:critical');

    // Categorias (protegidas)
    Route::get('/category', [CategoryApiController::class , 'index'])->middleware('throttle:read');
    Route::get('/category/stats', [CategoryApiController::class , 'stats'])->middleware('throttle:read');
    Route::get('/category/{identify}', [CategoryApiController::class , 'show'])->middleware('throttle:read');
    Route::post('/category', [CategoryApiController::class , 'store'])->middleware('throttle:critical');
    Route::put('/category/{id}', [CategoryApiController::class , 'update'])->middleware('throttle:critical');
    Route::delete('/category/{identify}', [CategoryApiController::class , 'delete'])->middleware('throttle:critical');
    

    // Usuários
    Route::get('/user', [UserApiController::class , 'index'])->middleware('throttle:read');
    Route::get('/user/{user}', [UserApiController::class , 'show'])->middleware('throttle:read');
    Route::post('/user', [UserApiController::class , 'store'])->middleware('throttle:critical');
    Route::put('/user/{user}', [UserApiController::class , 'update'])->middleware('throttle:critical');
    Route::delete('/user/{user}', [UserApiController::class , 'destroy'])->middleware('throttle:critical');

    // Clientes (protegidas por autenticação)
    Route::get('/client', [ClientApiController::class, 'index'])->middleware('throttle:read');
    Route::get('/client/stats', [ClientApiController::class, 'stats'])->middleware('throttle:read');
    Route::get('/client/{id}', [ClientApiController::class, 'show'])->middleware('throttle:read');
    Route::post('/client', [ClientApiController::class, 'store'])->middleware('throttle:critical');
    Route::put('/client/{id}', [ClientApiController::class, 'update'])->middleware('throttle:critical');
    Route::delete('/client/{id}', [ClientApiController::class, 'destroy'])->middleware('throttle:critical');

    // Estatísticas de usuários
    Route::get('/user/stats', [UserStatsApiController::class, 'stats'])->middleware('throttle:read');


    // Dashboard
    Route::get('/dashboard', [DashboardApiController::class, 'index'])->middleware('throttle:read');
    
    // Dashboard Metrics (Enhanced)
    Route::prefix('dashboard')->group(function () {
        Route::get('/metrics', [DashboardMetricsController::class, 'getMetricsOverview'])->middleware('throttle:read');
        Route::get('/sales-performance', [DashboardMetricsController::class, 'getSalesPerformance'])->middleware('throttle:read');
        Route::get('/recent-transactions', [DashboardMetricsController::class, 'getRecentTransactions'])->middleware('throttle:read');
        Route::get('/top-products', [DashboardMetricsController::class, 'getTopProducts'])->middleware('throttle:read');
        Route::get('/realtime-updates', [DashboardMetricsController::class, 'getRealtimeUpdates'])->middleware('throttle:read');
        Route::post('/clear-cache', [DashboardMetricsController::class, 'clearCache'])->middleware('throttle:write');
    });
});

// Cliente (movido para dentro do middleware auth:api)

Route::get('/tenant', [TenantApiController::class , 'index'])->middleware('throttle:read');
Route::get('/tenant/{uuid}', [TenantApiController::class , 'show'])->middleware('throttle:read');
Route::post('/tenant', [TenantApiController::class , 'store'])->middleware('throttle:register');


Route::get('/plan/{id}/details', [DetailPlanApiController::class , 'index'])->middleware('throttle:read');
Route::post('/plan/{id}/details', [DetailPlanApiController::class , 'store'])->middleware('throttle:critical');
Route::put('/plan/{url}/details/{idDetail}', [DetailPlanApiController::class , 'update'])->middleware('throttle:critical');

Route::get('/plan', [PlanApiController::class , 'index'])->middleware('throttle:read');
Route::get('/plan/{id}', [PlanApiController::class , 'show'])->middleware('throttle:read');
Route::post('/plan', [PlanApiController::class , 'store'])->middleware('throttle:critical');
Route::delete('/plan/{id}', [PlanApiController::class , 'delete'])->middleware('throttle:critical');
Route::put('/plan/{id}', [PlanApiController::class , 'update'])->middleware('throttle:critical');

// Rotas para gestão de usuários, perfis e permissões
Route::middleware(['auth:api'])->group(function () {
    // Usuários
    Route::prefix('users')->group(function () {
        Route::get('/', [UserApiController::class, 'index'])->middleware(['acl.permission:users.index', 'throttle:read']);
        Route::post('/', [UserApiController::class, 'store'])->middleware(['acl.permission:users.create', 'throttle:critical']);
        Route::get('/{id}', [UserApiController::class, 'show'])->middleware(['acl.permission:users.show', 'throttle:read']);
        Route::put('/{id}', [UserApiController::class, 'update'])->middleware(['acl.permission:users.update', 'throttle:critical']);
        Route::delete('/{id}', [UserApiController::class, 'destroy'])->middleware(['acl.permission:users.delete', 'throttle:critical']);
        Route::post('/{id}/assign-profile', [UserApiController::class, 'assignProfile'])->middleware(['acl.permission:users.update', 'throttle:critical']);
        Route::put('/{id}/change-password', [UserApiController::class, 'changePassword'])->middleware(['acl.permission:users.update', 'throttle:critical']);
        Route::get('/{id}/permissions', [UserApiController::class, 'getUserPermissions'])->middleware(['acl.permission:users.show', 'throttle:read']);
    });

    // Perfis
    Route::prefix('profiles')->group(function () {
        Route::get('/', [ProfileApiController::class, 'index'])->middleware('throttle:read');
        Route::post('/', [ProfileApiController::class, 'store'])->middleware('throttle:critical');
        Route::get('/{profile}', [ProfileApiController::class, 'show'])->middleware('throttle:read');
        Route::put('/{profile}', [ProfileApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{profile}', [ProfileApiController::class, 'destroy'])->middleware('throttle:critical');
        
        // Gerenciar permissões do perfil
        Route::get('/{profile}/permissions', [PermissionProfileApiController::class, 'getProfilePermissions'])->middleware('throttle:read');
        Route::get('/{profile}/permissions/available', [PermissionProfileApiController::class, 'getAvailablePermissionsForProfile'])->middleware('throttle:read');
        Route::post('/{profile}/permissions', [PermissionProfileApiController::class, 'attachPermissionToProfile'])->middleware('throttle:critical');
        Route::delete('/{profile}/permissions/{permission}', [PermissionProfileApiController::class, 'detachPermissionFromProfile'])->middleware('throttle:critical');
        Route::put('/{profile}/permissions/sync', [PermissionProfileApiController::class, 'syncPermissionsForProfile'])->middleware('throttle:critical');
    });

    // Permissões
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionApiController::class, 'index'])->middleware('throttle:read');
        Route::post('/', [PermissionApiController::class, 'store'])->middleware('throttle:critical');
        Route::get('/{id}', [PermissionApiController::class, 'show'])->middleware('throttle:read');
        Route::put('/{id}', [PermissionApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{id}', [PermissionApiController::class, 'destroy'])->middleware('throttle:critical');
        Route::get('/{id}/usage', [PermissionApiController::class, 'checkUsage'])->middleware('throttle:read');
        Route::get('/{id}/profiles', [PermissionProfileApiController::class, 'getPermissionProfiles'])->middleware('throttle:read');
    });

    // Roles - DEPRECATED: Usar Profiles
    // Rotas comentadas após migração para User -> Profile -> Permissions
    // Se precisar, use endpoints de /profile ou /profiles
    /*
    Route::prefix('role')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\RoleApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/stats', [\App\Http\Controllers\Api\RoleApiController::class, 'stats'])->middleware('throttle:read');
        Route::post('/', [\App\Http\Controllers\Api\RoleApiController::class, 'store'])->middleware('throttle:critical');
        Route::get('/{id}', [\App\Http\Controllers\Api\RoleApiController::class, 'show'])->middleware('throttle:read');
        Route::put('/{id}', [\App\Http\Controllers\Api\RoleApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{id}', [\App\Http\Controllers\Api\RoleApiController::class, 'destroy'])->middleware('throttle:critical');
        Route::get('/{id}/permissions', [\App\Http\Controllers\Api\RoleApiController::class, 'getRolePermissions'])->middleware('throttle:read');
        Route::post('/{id}/permissions', [\App\Http\Controllers\Api\RoleApiController::class, 'attachPermissionToRole'])->middleware('throttle:critical');
        Route::delete('/{id}/permissions/{permissionId}', [\App\Http\Controllers\Api\RoleApiController::class, 'detachPermissionFromRole'])->middleware('throttle:critical');
        Route::put('/{id}/permissions/sync', [\App\Http\Controllers\Api\RoleApiController::class, 'syncPermissionsForRole'])->middleware('throttle:critical');
    });
    */

    // Profile (alias para profiles)
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileApiController::class, 'index'])->middleware('throttle:read');
        Route::post('/', [ProfileApiController::class, 'store'])->middleware('throttle:critical');
        Route::get('/{id}', [ProfileApiController::class, 'show'])->middleware('throttle:read');
        Route::put('/{id}', [ProfileApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{id}', [ProfileApiController::class, 'destroy'])->middleware('throttle:critical');
        
        // Gerenciar permissões do perfil
        Route::get('/{id}/permissions', [PermissionProfileApiController::class, 'getProfilePermissions'])->middleware('throttle:read');
        Route::get('/{id}/permissions/available', [PermissionProfileApiController::class, 'getAvailablePermissionsForProfile'])->middleware('throttle:read');
        Route::post('/{id}/permissions', [PermissionProfileApiController::class, 'attachPermissionToProfile'])->middleware('throttle:critical');
        Route::delete('/{id}/permissions/{permissionId}', [PermissionProfileApiController::class, 'detachPermissionFromProfile'])->middleware('throttle:critical');
        Route::put('/{id}/permissions/sync', [PermissionProfileApiController::class, 'syncPermissionsForProfile'])->middleware('throttle:critical');
    });

    // Formas de Pagamento
    Route::prefix('payment-methods')->group(function () {
        Route::get('/', [PaymentMethodApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/active', [PaymentMethodApiController::class, 'active'])->middleware('throttle:read');
        Route::get('/{uuid}', [PaymentMethodApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [PaymentMethodApiController::class, 'store'])->middleware('throttle:critical');
        Route::put('/{uuid}', [PaymentMethodApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [PaymentMethodApiController::class, 'destroy'])->middleware('throttle:critical');
    });
});

// ============================================================================
// PUBLIC STORE ROUTES (NO AUTHENTICATION REQUIRED)
// ============================================================================

Route::prefix('store/{slug}')->group(function () {
    // Get store information
    Route::get('/info', [PublicStoreController::class, 'getStoreInfo']);
    
    // Get store products
    Route::get('/products', [PublicStoreController::class, 'getProducts']);
    
    // Client Authentication
    Route::post('/auth/register', [ClientAuthController::class, 'register'])
        ->middleware('throttle:5,1'); // 5 registrations per minute
    
    Route::post('/auth/login', [ClientAuthController::class, 'login'])
        ->middleware('throttle:10,1'); // 10 login attempts per minute
    
    Route::middleware('auth:client')->group(function () {
        Route::get('/auth/me', [ClientAuthController::class, 'me']);
        Route::post('/auth/logout', [ClientAuthController::class, 'logout']);
        Route::get('/orders', [ClientAuthController::class, 'getOrders']);
    });
    
    // Create public order
    Route::post('/orders', [PublicStoreController::class, 'createOrder'])
        ->middleware('throttle:10,1'); // 10 requests per minute
});




