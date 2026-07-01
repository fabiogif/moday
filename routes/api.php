<?php

use App\Http\Controllers\Auth\AuthController;

// Incluir rotas de teste
require_once __DIR__ . '/test.php';
use App\Http\Controllers\{Api\Auth\AuthClientController,
    Api\Auth\RegisterApiController,
    Api\BankAccountController,
    Api\CategoryApiController,
    Api\ClientApiController,
    Api\ClientAuthController,
    Api\PlanApiController,
    Api\DetailPlanApiController,
    Api\TableApiController,
    Api\ServiceTypeApiController,
    Api\OrderApiController,
    Api\ProductApiController,
    Api\EvaluationApiController,
    Api\TenantApiController,
    Api\UserApiController,
    Api\ProfileApiController,
    Api\PermissionApiController,
    Api\PermissionProfileApiController,
    Api\OrderStatsApiController,
    Api\OrderStatusApiController,
    Api\UserStatsApiController,
    Api\PaymentMethodApiController,
    Api\DashboardApiController,
    Api\DashboardMetricsController,
    Api\SalesPerformanceController,
    Api\CsrfTokenController,
    Api\PublicStoreController,
    Api\ContactApiController,
    Api\NewsletterApiController,
    Api\ReportController,
    Api\DistribtecReportController,
    Api\CashFlowReportController,
    Api\AgingReportController,
    Api\AbcClientsReportController,
    Api\AbcSuppliersReportController,
    Api\ExecutiveDashboardController,
    Api\ReorderSuggestionController,
    Api\FiscalCorrectionController,
    Api\NotificationController,
    Api\LocationController,
    Api\RegisterController,
    Api\ReviewApiController,
    Api\SubscriptionApiController,
    Api\CouponApiController,
    Api\PDVFeedbackController,
    Api\PlanLimitApiController,
    Api\PlanMigrationApiController};
use App\Http\Controllers\Api\Integrations\Ifood\IfoodAuthController;
use App\Http\Controllers\Api\Integrations\Ifood\IfoodOrderController;
use App\Http\Controllers\Api\Integrations\Ifood\IfoodWebhookController;
use App\Http\Controllers\Api\OfflineSyncController;
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

// Webhook público do Mercado Pago — legacy (Orders API v1)
Route::post('/webhooks/mercadopago', [SubscriptionApiController::class, 'webhook'])
    ->middleware('throttle:60,1');

// Webhook público do Mercado Pago — Preapproval v2
Route::post('/webhooks/mercadopago/preapproval', \App\Http\Controllers\Api\MercadoPagoWebhookController::class)
    ->middleware(['throttle:60,1', 'mp.signature']);

// Analytics da landing page — aceita e descarta silenciosamente
Route::post('/landing-analytics', fn() => response()->json(['ok' => true]))
    ->middleware('throttle:120,1');

// POD Digital — Comprovante de Entrega (público, sem JWT)
Route::prefix('delivery')->group(function () {
    Route::get('/{token}', [\App\Http\Controllers\Api\PodController::class, 'show'])
        ->middleware('throttle:read');
    Route::post('/{token}/orders/{orderId}/pod', [\App\Http\Controllers\Api\PodController::class, 'submitPod'])
        ->middleware('throttle:critical');
});


// Route OPTIONS removida - deixar o GlobalCorsMiddleware tratar

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

// ============================================================================
// PUBLIC REGISTRATION AND PLANS ROUTES
// ============================================================================

// Rotas públicas de registro de novo tenant
Route::post('/register', [RegisterController::class, 'register'])
    ->middleware('throttle:register');

// Lista de planos disponíveis (público)
Route::get('/plans', [RegisterController::class, 'plans']);

// Rota pública de contato
Route::post('/contact', [ContactApiController::class, 'send'])
    ->middleware('throttle:10,1'); // 10 mensagens por minuto

Route::post('/newsletter/subscribe', [NewsletterApiController::class, 'subscribe'])
    ->middleware('throttle:10,1');

// Webhook do iFood (assinatura validada via header)
Route::post('/webhooks/fiscal/{tenantId}', [\App\Http\Controllers\Api\FiscalWebhookController::class, '__invoke'])
    ->middleware('throttle:critical');

Route::post('/integrations/ifood/webhook', IfoodWebhookController::class)
    ->name('integrations.ifood.webhook');

// ============================================================================
// LOCATION API (PUBLIC) - Estados e Municípios
// ============================================================================

// Listar todos os estados
Route::get('/states', [LocationController::class, 'getStates'])
    ->middleware('throttle:read');

// Listar cidades por estado (UF)
Route::get('/states/{uf}/cities', [LocationController::class, 'getCitiesByState'])
    ->middleware('throttle:read');

// Listar todas as cidades (paginado)
Route::get('/cities', [LocationController::class, 'getAllCities'])
    ->middleware('throttle:read');

// Listar capitais
Route::get('/cities/capitals', [LocationController::class, 'getCapitals'])
    ->middleware('throttle:read');

// Pesquisar cidades por nome
Route::get('/cities/search', [LocationController::class, 'searchCities'])
    ->middleware('throttle:read');

// Rotas de autenticação públicas com rate limiting específico
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
});

// Rotas protegidas por JWT específicas para autenticação do usuário
Route::prefix('auth')->middleware([
    \App\Http\Middleware\JwtMiddleware::class,
    'tenant.blocked',
])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('throttle:critical');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// Rotas protegidas por JWT
Route::middleware(['auth:api', 'tenant.blocked'])->group(function () {
    // Trial e Subscription
    Route::prefix('subscription')->group(function () {
        Route::get('/trial-status', [SubscriptionApiController::class, 'trialStatus'])->middleware('throttle:read');
        Route::get('/plans', [SubscriptionApiController::class, 'plans'])->middleware('throttle:read');
        Route::post('/activate', [SubscriptionApiController::class, 'activate'])->middleware('throttle:critical');
        Route::post('/payment', [SubscriptionApiController::class, 'payment'])->middleware('throttle:critical');
        Route::post('/cancel', [SubscriptionApiController::class, 'cancel'])->middleware('throttle:critical');
        Route::post('/reactivate', [SubscriptionApiController::class, 'reactivate'])->middleware('throttle:critical');
        // v2 Preapproval endpoints
        Route::post('/upgrade', [SubscriptionApiController::class, 'upgrade'])->middleware('throttle:critical');
        Route::post('/downgrade', [SubscriptionApiController::class, 'scheduleDowngrade'])->middleware('throttle:critical');
        Route::delete('/downgrade', [SubscriptionApiController::class, 'cancelDowngrade'])->middleware('throttle:critical');
        Route::put('/card', [SubscriptionApiController::class, 'updateCard'])->middleware('throttle:critical');
        Route::get('/invoices', [SubscriptionApiController::class, 'invoices'])->middleware('throttle:read');
    });

    Route::prefix('integrations/ifood')->group(function () {
        Route::post('/token', [IfoodAuthController::class, 'store'])->middleware('throttle:critical');
        Route::post('/token/refresh', [IfoodAuthController::class, 'refresh'])->middleware('throttle:critical');
        Route::get('/token', [IfoodAuthController::class, 'show'])->middleware('throttle:read');

        Route::post('/oauth/user-code', [\App\Http\Controllers\Api\Integrations\Ifood\IfoodOAuthController::class, 'requestUserCode'])->middleware('throttle:critical');

        Route::get('/orders', [IfoodOrderController::class, 'index'])->middleware('throttle:read');
        Route::get('/orders/{externalOrderId}', [IfoodOrderController::class, 'show'])->middleware('throttle:read');
        Route::post('/orders/{externalOrderId}/status', [IfoodOrderController::class, 'resendStatus'])->middleware('throttle:critical');
        Route::post('/orders/{externalOrderId}/confirm', [IfoodOrderController::class, 'confirm'])->middleware('throttle:critical');

        Route::get('/catalogs', [\App\Http\Controllers\Api\Integrations\Ifood\IfoodCatalogController::class, 'catalogs'])->middleware('throttle:read');
        Route::get('/catalogs/{catalogId}/categories', [\App\Http\Controllers\Api\Integrations\Ifood\IfoodCatalogController::class, 'categories'])->middleware('throttle:read');
        Route::get('/catalogs/{catalogId}/groups', [\App\Http\Controllers\Api\Integrations\Ifood\IfoodCatalogController::class, 'groups'])->middleware('throttle:read');
        Route::get('/catalogs/{catalogId}/unsellable-items', [\App\Http\Controllers\Api\Integrations\Ifood\IfoodCatalogController::class, 'unsellableItems'])->middleware('throttle:read');
        Route::get('/catalog-groups/{groupId}/sellable-items', [\App\Http\Controllers\Api\Integrations\Ifood\IfoodCatalogController::class, 'sellableItems'])->middleware('throttle:read');
        Route::get('/catalog/version', [\App\Http\Controllers\Api\Integrations\Ifood\IfoodCatalogController::class, 'version'])->middleware('throttle:read');
        Route::get('/catalog/snapshots', [\App\Http\Controllers\Api\Integrations\Ifood\IfoodCatalogController::class, 'snapshots'])->middleware('throttle:read');
    });

    // Broadcasting authentication
    Route::post('/broadcasting/auth', function (Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    });
});

// Rotas públicas (sem autenticação)
Route::get('/public/plans', [PlanApiController::class, 'index'])->middleware('throttle:read');
Route::get('/public/plans/{id}', [PlanApiController::class, 'show'])->middleware('throttle:read');

// Verificar horário de funcionamento da loja (público)
Route::get('/store/{slug}/is-open', [\App\Http\Controllers\Api\PublicStoreController::class, 'checkStoreHours'])->middleware('throttle:read');
Route::get('/store/{slug}/coupons/validate', [\App\Http\Controllers\Api\PublicStoreController::class, 'validateCoupon'])->middleware('throttle:read');
Route::get('/store/{slug}/promotions', [\App\Http\Controllers\Api\PublicStoreController::class, 'getPromotions'])->middleware('throttle:read');

// Tipos de atendimento do cardápio público (sem JWT)
Route::get('/service-type/menu', [ServiceTypeApiController::class, 'menu'])->middleware('throttle:read');

// Rotas protegidas por JWT e tenant
Route::middleware(['auth:api', 'tenant.blocked', 'trial.check'])->group(function () {
    // Produtos
    Route::get('/product', [ProductApiController::class , 'productsByAuthenticatedUser'])->middleware('throttle:read');
    Route::get('/product/catalog', [ProductApiController::class , 'catalogProductsByAuthenticatedUser'])->middleware('throttle:read');
    Route::get('/product/by-code/{code}', [ProductApiController::class , 'showByCode'])->middleware('throttle:read')->where('code', '.+');
    Route::get('/product/stats', [ProductApiController::class , 'stats'])->middleware('throttle:read');
    Route::get('/product/{identify}/similar', [ProductApiController::class , 'similarProducts'])->middleware('throttle:read');
    Route::get('/product/{identify}', [ProductApiController::class , 'show'])->middleware('throttle:read');
    Route::post('/product', [ProductApiController::class , 'store'])->middleware('throttle:critical');
    Route::put('/product/{id}', [ProductApiController::class , 'update'])->middleware('throttle:critical');
    Route::post('/product/{id}', [ProductApiController::class , 'update'])->middleware('throttle:critical'); // Para upload de imagem com _method=PUT
    Route::delete('/product/{identify}', [ProductApiController::class , 'delete'])->middleware('throttle:critical');

    // Offline Sync (app mobile / campo)
    Route::prefix('offline')->group(function () {
        Route::get('/snapshot', [OfflineSyncController::class, 'snapshot'])->middleware('throttle:read');
        Route::post('/orders', [OfflineSyncController::class, 'pushOrders'])->middleware('throttle:critical');
        // B2B field app sync
        Route::get('/status', [OfflineSyncController::class, 'status'])->middleware('throttle:read');
        Route::post('/sync', [OfflineSyncController::class, 'sync'])->middleware(['acl.permission:sale-orders.store', 'throttle:critical']);
    });

    // Pedidos
    Route::get('/order', [OrderApiController::class , 'index'])->middleware('throttle:read');
    Route::get('/order/stats', [OrderStatsApiController::class, 'stats'])->middleware('throttle:read');
    Route::get('/order/search-by-number', [OrderApiController::class , 'searchByNumber'])->middleware('throttle:read');
    Route::get('/order/client/', [OrderApiController::class , 'orderByClient'])->middleware('throttle:read');
    Route::get('/order/by-table', [OrderApiController::class , 'getOpenOrdersByTable'])->middleware('throttle:read');
    Route::get('/order/today', [OrderApiController::class , 'getTodayOrders'])->middleware('throttle:read');
    Route::get('/order/recommendations', [OrderApiController::class , 'getProductRecommendations'])->middleware('throttle:read');
    Route::get('/order/{identify}', [OrderApiController::class , 'show'])->middleware('throttle:read');
    Route::get('/order/{identify}/receipt', [OrderApiController::class , 'receipt'])->middleware('throttle:read');
    Route::post('/order/{identify}/receipt/email', [OrderApiController::class, 'sendReceiptEmail'])->middleware('throttle:critical');
    Route::get('/order/{orderId}/details', [OrderApiController::class , 'getOrderDetails'])->middleware('throttle:read');
    Route::post('/order', [OrderApiController::class , 'store'])->middleware(['throttle:critical', 'plan.order_limit']);
    Route::put('/order/{identify}', [OrderApiController::class , 'update'])->middleware('throttle:critical');
    Route::post('/order/{identify}/advance-status', [OrderApiController::class , 'advanceStatus'])->middleware('throttle:critical');
    Route::patch('/order/{identify}/archive', [OrderApiController::class , 'archive'])->middleware('throttle:critical');
    Route::delete('/order/{identify}', [OrderApiController::class , 'delete'])->middleware('throttle:critical');
    Route::post('/order/{identify}/evaluations', [EvaluationApiController::class , 'store'])->middleware('throttle:critical');
    Route::post('/order/{identify}/invoice', [OrderApiController::class , 'invoice'])->middleware('throttle:critical');
    Route::post('/orders/bulk-delete', [OrderApiController::class , 'bulkDelete'])->middleware('throttle:critical');
    Route::post('/orders/bulk-update-status', [OrderApiController::class , 'bulkUpdateStatus'])->middleware('throttle:critical');

    // Notificações
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index'])->middleware('throttle:read');
    Route::get('/notifications/unread', [\App\Http\Controllers\Api\NotificationController::class, 'unread'])->middleware('throttle:read');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount'])->middleware('throttle:read');
    Route::post('/notifications/{uuid}/mark-as-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])->middleware('throttle:critical');
    Route::post('/notifications/mark-all-as-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])->middleware('throttle:critical');
    Route::delete('/notifications/{uuid}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy'])->middleware('throttle:critical');
    Route::get('/notifications/preferences', [\App\Http\Controllers\Api\NotificationController::class, 'preferences'])->middleware('throttle:read');
    Route::put('/notifications/preferences', [\App\Http\Controllers\Api\NotificationController::class, 'updatePreferences'])->middleware('throttle:critical');

    // Mesas
    Route::get('/table', [TableApiController::class , 'index'])->middleware('throttle:read');
    Route::get('/table/stats', [TableApiController::class , 'stats'])->middleware('throttle:read');
    Route::get('/table/{identify}', [TableApiController::class , 'show'])->middleware('throttle:read');
    Route::post('/table', [TableApiController::class , 'store'])->middleware('throttle:critical');
    Route::put('/table/{id}', [TableApiController::class , 'update'])->middleware('throttle:critical');
    Route::delete('/table/{identify}', [TableApiController::class , 'delete'])->middleware('throttle:critical');

    // Tipos de Atendimento
    Route::get('/service-type', [ServiceTypeApiController::class, 'index'])->middleware('throttle:read');
    Route::get('/service-type/active', [ServiceTypeApiController::class, 'active'])->middleware('throttle:read');
    Route::get('/service-type/{identify}', [ServiceTypeApiController::class, 'show'])->middleware('throttle:read');
    Route::post('/service-type', [ServiceTypeApiController::class, 'store'])->middleware('throttle:critical');
    Route::put('/service-type/{id}', [ServiceTypeApiController::class, 'update'])->middleware('throttle:critical');
    Route::delete('/service-type/{identify}', [ServiceTypeApiController::class, 'delete'])->middleware('throttle:critical');

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
    Route::post('/user', [UserApiController::class , 'store'])->middleware(['throttle:critical', 'plan.user_limit']);
    Route::put('/user/{user}', [UserApiController::class , 'update'])->middleware('throttle:critical');
    Route::delete('/user/{user}', [UserApiController::class , 'destroy'])->middleware('throttle:critical');

    // Clientes (protegidas por autenticação)
    Route::get('/client', [ClientApiController::class, 'index'])->middleware('throttle:read');
    Route::get('/client/stats', [ClientApiController::class, 'stats'])->middleware('throttle:read');
    Route::get('/client/check-cpf', [ClientApiController::class, 'checkCpf'])->middleware('throttle:read');
    Route::get('/client/{id}', [ClientApiController::class, 'show'])->middleware('throttle:read');
    Route::post('/client', [ClientApiController::class, 'store'])->middleware('throttle:critical');
    Route::put('/client/{id}', [ClientApiController::class, 'update'])->middleware('throttle:critical');
    Route::delete('/client/{id}', [ClientApiController::class, 'destroy'])->middleware('throttle:critical');

    // Eventos - com rate limiting específico
    Route::prefix('events')->middleware('throttle:events')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\EventApiController::class, 'index']);
        Route::get('/stats', [\App\Http\Controllers\Api\EventApiController::class, 'stats']);
        Route::get('/upcoming', [\App\Http\Controllers\Api\EventApiController::class, 'upcoming']);
        Route::get('/{uuid}', [\App\Http\Controllers\Api\EventApiController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\EventApiController::class, 'store']);
        Route::put('/{uuid}', [\App\Http\Controllers\Api\EventApiController::class, 'update']);
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\EventApiController::class, 'destroy']);
    });

    // Marketing - Cupons
    Route::prefix('marketing/coupons')->group(function () {
        Route::get('/', [CouponApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/stats', [CouponApiController::class, 'stats'])->middleware('throttle:read');
        Route::post('/', [CouponApiController::class, 'store'])->middleware('throttle:critical');
        Route::get('/{uuid}', [CouponApiController::class, 'show'])->middleware('throttle:read');
        Route::put('/{uuid}', [CouponApiController::class, 'update'])->middleware('throttle:critical');
        Route::patch('/{uuid}/toggle', [CouponApiController::class, 'toggle'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [CouponApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Módulo Financeiro - Categorias
    Route::prefix('financial-categories')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\FinancialCategoryApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/{uuid}', [\App\Http\Controllers\Api\FinancialCategoryApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [\App\Http\Controllers\Api\FinancialCategoryApiController::class, 'store'])->middleware('throttle:critical');
        Route::put('/{uuid}', [\App\Http\Controllers\Api\FinancialCategoryApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\FinancialCategoryApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    Route::prefix('job-positions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\JobPositionApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/{uuid}', [\App\Http\Controllers\Api\JobPositionApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [\App\Http\Controllers\Api\JobPositionApiController::class, 'store'])->middleware('throttle:critical');
        Route::put('/{uuid}', [\App\Http\Controllers\Api\JobPositionApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\JobPositionApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Módulo Financeiro - Fornecedores
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SupplierApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/check-document', [\App\Http\Controllers\Api\SupplierApiController::class, 'checkDocument'])->middleware('throttle:read');
        Route::get('/{uuid}', [\App\Http\Controllers\Api\SupplierApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [\App\Http\Controllers\Api\SupplierApiController::class, 'store'])->middleware('throttle:critical');
        Route::put('/{uuid}', [\App\Http\Controllers\Api\SupplierApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\SupplierApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Módulo Financeiro - Despesas
    Route::prefix('expenses')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ExpenseApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/stats', [\App\Http\Controllers\Api\ExpenseApiController::class, 'stats'])->middleware('throttle:read');
        Route::get('/{uuid}', [\App\Http\Controllers\Api\ExpenseApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [\App\Http\Controllers\Api\ExpenseApiController::class, 'store'])->middleware('throttle:critical');
        Route::post('/{uuid}/attachment', [\App\Http\Controllers\Api\ExpenseApiController::class, 'uploadAttachment'])->middleware('throttle:critical');
        Route::put('/{uuid}', [\App\Http\Controllers\Api\ExpenseApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\ExpenseApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Módulo Financeiro - Contas a Pagar
    Route::prefix('accounts-payable')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AccountPayableApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/stats', [\App\Http\Controllers\Api\AccountPayableApiController::class, 'stats'])->middleware('throttle:read');
        Route::get('/alerts', [\App\Http\Controllers\Api\AccountPayableApiController::class, 'alerts'])->middleware('throttle:read');
        Route::get('/{uuid}', [\App\Http\Controllers\Api\AccountPayableApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [\App\Http\Controllers\Api\AccountPayableApiController::class, 'store'])->middleware('throttle:critical');
        Route::post('/{uuid}/pay', [\App\Http\Controllers\Api\AccountPayableApiController::class, 'pay'])->middleware('throttle:critical');
        Route::put('/{uuid}', [\App\Http\Controllers\Api\AccountPayableApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\AccountPayableApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Módulo Financeiro - Contas a Receber
    Route::prefix('accounts-receivable')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AccountReceivableApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/stats', [\App\Http\Controllers\Api\AccountReceivableApiController::class, 'stats'])->middleware('throttle:read');
        Route::get('/from-order/{orderId}', [\App\Http\Controllers\Api\AccountReceivableApiController::class, 'fromOrder'])->middleware('throttle:read');
        Route::get('/{uuid}', [\App\Http\Controllers\Api\AccountReceivableApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [\App\Http\Controllers\Api\AccountReceivableApiController::class, 'store'])->middleware('throttle:critical');
        Route::post('/{uuid}/receive', [\App\Http\Controllers\Api\AccountReceivableApiController::class, 'receive'])->middleware('throttle:critical');
        Route::put('/{uuid}', [\App\Http\Controllers\Api\AccountReceivableApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\AccountReceivableApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Horários de Funcionamento
    Route::prefix('store-hours')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StoreHourApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/stats', [\App\Http\Controllers\Api\StoreHourApiController::class, 'stats'])->middleware('throttle:read');
        Route::get('/check-is-open', [\App\Http\Controllers\Api\StoreHourApiController::class, 'checkIsOpen'])->middleware('throttle:read');
        Route::post('/set-always-open', [\App\Http\Controllers\Api\StoreHourApiController::class, 'setAlwaysOpen'])->middleware('throttle:critical');
        Route::post('/remove-always-open', [\App\Http\Controllers\Api\StoreHourApiController::class, 'removeAlwaysOpen'])->middleware('throttle:critical');
        Route::get('/{uuid}', [\App\Http\Controllers\Api\StoreHourApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [\App\Http\Controllers\Api\StoreHourApiController::class, 'store'])->middleware('throttle:critical');
        Route::put('/{uuid}', [\App\Http\Controllers\Api\StoreHourApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\StoreHourApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Programa de Fidelidade
    Route::prefix('loyalty')->group(function () {
        // Programa
        Route::get('/program', [\App\Http\Controllers\Api\LoyaltyProgramApiController::class, 'index'])->middleware('throttle:read');
        Route::post('/program', [\App\Http\Controllers\Api\LoyaltyProgramApiController::class, 'store'])->middleware('throttle:critical');
        Route::put('/program/{uuid}', [\App\Http\Controllers\Api\LoyaltyProgramApiController::class, 'update'])->middleware('throttle:critical');
        
        // Recompensas
        Route::get('/rewards', [\App\Http\Controllers\Api\LoyaltyRewardApiController::class, 'index'])->middleware('throttle:read');
        Route::post('/rewards', [\App\Http\Controllers\Api\LoyaltyRewardApiController::class, 'store'])->middleware('throttle:critical');
        Route::get('/rewards/{uuid}', [\App\Http\Controllers\Api\LoyaltyRewardApiController::class, 'show'])->middleware('throttle:read');
        Route::put('/rewards/{uuid}', [\App\Http\Controllers\Api\LoyaltyRewardApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/rewards/{uuid}', [\App\Http\Controllers\Api\LoyaltyRewardApiController::class, 'destroy'])->middleware('throttle:critical');
        
        // Cliente - Saldo e Histórico
        Route::get('/client/{clientId}/balance', [\App\Http\Controllers\Api\LoyaltyClientApiController::class, 'balance'])->middleware('throttle:read');
        Route::get('/client/{clientId}/transactions', [\App\Http\Controllers\Api\LoyaltyClientApiController::class, 'transactions'])->middleware('throttle:read');
        Route::get('/client/{clientId}/redemptions', [\App\Http\Controllers\Api\LoyaltyClientApiController::class, 'redemptions'])->middleware('throttle:read');
        Route::post('/client/{clientId}/adjust-points', [\App\Http\Controllers\Api\LoyaltyClientApiController::class, 'adjustPoints'])->middleware('throttle:critical');
        
        // Resgate de Recompensas
        Route::post('/redeem', [\App\Http\Controllers\Api\LoyaltyClientApiController::class, 'redeem'])->middleware('throttle:critical');
    });

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
        Route::get('/profit', [DashboardMetricsController::class, 'getProfit'])->middleware('throttle:read');
        Route::get('/realtime-updates', [DashboardMetricsController::class, 'getRealtimeUpdates'])->middleware('throttle:read');
        Route::post('/clear-cache', [DashboardMetricsController::class, 'clearCache'])->middleware('throttle:write');
    });

    // Sales Performance (Desempenho/Vendas)
    Route::prefix('sales-performance')->group(function () {
        Route::get('/', [SalesPerformanceController::class, 'index'])->middleware('throttle:read');
        Route::get('/export', [SalesPerformanceController::class, 'export'])->middleware('throttle:read');
        Route::post('/refresh', [SalesPerformanceController::class, 'refresh'])->middleware('throttle:critical');
    });

    // Bank Accounts (Contas Bancárias)
    Route::prefix('bank-accounts')->group(function () {
        Route::get('/', [BankAccountController::class, 'index'])->middleware('throttle:read');
        Route::post('/', [BankAccountController::class, 'store'])->middleware('throttle:critical');
        Route::get('/banks', [BankAccountController::class, 'banks'])->middleware('throttle:read');
        Route::get('/{uuid}', [BankAccountController::class, 'show'])->middleware('throttle:read');
        Route::put('/{uuid}', [BankAccountController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [BankAccountController::class, 'destroy'])->middleware('throttle:critical');
        Route::post('/{uuid}/set-primary', [BankAccountController::class, 'setPrimary'])->middleware('throttle:critical');
        Route::post('/{uuid}/verify', [BankAccountController::class, 'verify'])->middleware('throttle:critical');
        Route::get('/{uuid}/logs', [BankAccountController::class, 'logs'])->middleware('throttle:read');
    });
});

// Cliente (movido para dentro do middleware auth:api)

Route::get('/tenant', [TenantApiController::class , 'index'])->middleware('throttle:read');
Route::get('/tenant/{uuid}', [TenantApiController::class , 'show'])->middleware('throttle:read');
Route::post('/tenant', [TenantApiController::class , 'store'])->middleware('throttle:register');
Route::put('/tenant/{uuid}', [TenantApiController::class , 'update'])->middleware(['auth:api', 'tenant.blocked', 'trial.check', 'throttle:critical']);
Route::post('/tenant/{uuid}', [TenantApiController::class , 'update'])->middleware(['auth:api', 'tenant.blocked', 'trial.check', 'throttle:critical']); // Para upload de arquivo com _method=PUT


// Rotas de verificação de limites e migração de planos (DEVEM VIR ANTES DE /plan/{id})
Route::middleware(['auth:api'])->group(function () {
    // Verificação de limites
    Route::get('/plan/limits/check', [PlanLimitApiController::class, 'checkLimits'])->middleware('throttle:read');
    Route::get('/plan/current-usage', [PlanLimitApiController::class, 'getCurrentUsage'])->middleware('throttle:read');
    
    // Migração de plano
    Route::post('/plan/migrate', [PlanMigrationApiController::class, 'migrate'])->middleware('throttle:critical');
    Route::get('/plan/migrations/history', [PlanMigrationApiController::class, 'history'])->middleware('throttle:read');
});

Route::get('/plan/{id}/details', [DetailPlanApiController::class , 'index'])->middleware('throttle:read');
Route::post('/plan/{id}/details', [DetailPlanApiController::class , 'store'])->middleware('throttle:critical');
Route::put('/plan/{url}/details/{idDetail}', [DetailPlanApiController::class , 'update'])->middleware('throttle:critical');

Route::get('/plan', [PlanApiController::class , 'index'])->middleware('throttle:read');
Route::get('/plan/{id}', [PlanApiController::class , 'show'])->middleware('throttle:read');
Route::post('/plan', [PlanApiController::class , 'store'])->middleware('throttle:critical');
Route::delete('/plan/{id}', [PlanApiController::class , 'delete'])->middleware('throttle:critical');
Route::put('/plan/{id}', [PlanApiController::class , 'update'])->middleware('throttle:critical');

// Rotas para gestão de usuários, perfis e permissões
Route::middleware(['auth:api', 'tenant.blocked', 'trial.check'])->group(function () {
    // Usuários
    Route::prefix('users')->group(function () {
        Route::get('/', [UserApiController::class, 'index'])->middleware(['acl.permission:users.index', 'throttle:read']);
        Route::post('/', [UserApiController::class, 'store'])->middleware(['acl.permission:users.create', 'throttle:critical', 'plan.user_limit']);
        Route::get('/{id}', [UserApiController::class, 'show'])->middleware(['acl.permission:users.show', 'throttle:read']);
        Route::put('/{id}', [UserApiController::class, 'update'])->middleware(['acl.permission:users.update', 'throttle:critical']);
        Route::delete('/{id}', [UserApiController::class, 'destroy'])->middleware(['acl.permission:users.delete', 'throttle:critical']);
        Route::post('/{id}/assign-profile', [UserApiController::class, 'assignProfile'])->middleware(['acl.permission:users.update', 'throttle:critical']);
        Route::delete('/{id}/remove-profile/{profileId}', [UserApiController::class, 'removeProfile'])->middleware(['acl.permission:users.update', 'throttle:critical']);
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

    // Status de Pedidos
    Route::prefix('order-statuses')->group(function () {
        Route::get('/', [OrderStatusApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/{uuid}', [OrderStatusApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [OrderStatusApiController::class, 'store'])->middleware('throttle:critical');
        Route::put('/{uuid}', [OrderStatusApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{uuid}', [OrderStatusApiController::class, 'destroy'])->middleware('throttle:critical');
        Route::post('/reorder', [OrderStatusApiController::class, 'reorder'])->middleware('throttle:critical');
    });

    // Notificações
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->middleware('throttle:read');
        Route::get('/unread', [NotificationController::class, 'unread'])->middleware('throttle:read');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->middleware('throttle:read');
        Route::post('/{uuid}/read', [NotificationController::class, 'markAsRead'])->middleware('throttle:write');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('throttle:write');
        Route::delete('/{uuid}', [NotificationController::class, 'destroy'])->middleware('throttle:critical');
        
        // Preferências
        Route::get('/preferences', [NotificationController::class, 'preferences'])->middleware('throttle:read');
        Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->middleware('throttle:critical');
    });

    // PDV Feedback
    Route::post('/pdv/feedback', [PDVFeedbackController::class, 'store'])->middleware('throttle:critical');

    // =========================================================================
    // DISTRIBTEC — Módulos de distribuição B2B
    // =========================================================================

    // Clientes B2B (alias plural para o ClientApiController existente)
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientApiController::class, 'index'])->middleware('throttle:read');
        Route::get('/stats', [ClientApiController::class, 'stats'])->middleware('throttle:read');
        Route::get('/check-cpf', [ClientApiController::class, 'checkCpf'])->middleware('throttle:read');
        Route::get('/{id}', [ClientApiController::class, 'show'])->middleware('throttle:read');
        Route::post('/', [ClientApiController::class, 'store'])->middleware('throttle:critical');
        Route::put('/{id}', [ClientApiController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/{id}', [ClientApiController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Pedidos de Venda
    Route::prefix('sale-orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'index'])->middleware(['acl.permission:sale-orders.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'store'])->middleware(['acl.permission:sale-orders.store', 'throttle:critical']);
        Route::get('/{id}', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'show'])->middleware(['acl.permission:sale-orders.index', 'throttle:read']);
        Route::put('/{id}', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'update'])->middleware(['acl.permission:sale-orders.update', 'throttle:critical']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'destroy'])->middleware(['acl.permission:sale-orders.destroy', 'throttle:critical']);
        Route::post('/{id}/advance-status', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'advanceStatus'])->middleware(['acl.permission:sale-orders.update', 'throttle:critical']);
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'cancel'])->middleware(['acl.permission:sale-orders.update', 'throttle:critical']);
        Route::post('/{id}/return', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'returnItems'])->middleware(['acl.permission:sale-orders.update', 'throttle:critical']);
        Route::post('/{id}/fiscal/request', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'requestFiscalEmission'])->middleware(['acl.permission:sale-orders.fiscal.request', 'throttle:critical']);
        Route::post('/{id}/fiscal/correction', [FiscalCorrectionController::class, 'correction'])->middleware(['acl.permission:sale-orders.fiscal.request', 'plan.feature:nfe_correction', 'throttle:critical']);
        Route::post('/{id}/payment-link', [\App\Http\Controllers\Api\B2BPaymentLinkController::class, 'generate'])->middleware(['plan.feature:b2b_payment_link', 'throttle:critical']);
        Route::get('/{id}/payment-link/status', [\App\Http\Controllers\Api\B2BPaymentLinkController::class, 'status'])->middleware('throttle:read');
        Route::get('/{id}/picking', [\App\Http\Controllers\Api\PickingApiController::class, 'show'])->middleware(['acl.permission:sale-orders.index', 'throttle:read']);
        Route::post('/{id}/picking/confirm', [\App\Http\Controllers\Api\PickingApiController::class, 'confirm'])->middleware(['acl.permission:picking.confirm', 'throttle:critical']);
        Route::post('/{id}/schedule', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'schedule'])->middleware(['acl.permission:sale-orders.update', 'throttle:critical']);
        Route::delete('/{id}/schedule', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'cancelSchedule'])->middleware(['acl.permission:sale-orders.update', 'throttle:critical']);
        Route::get('/{id}/calendar-link', [\App\Http\Controllers\Api\SaleOrderApiController::class, 'calendarLink'])->middleware(['acl.permission:sale-orders.index', 'throttle:read']);
    });

    // Sugestões de Reposição (P1)
    Route::prefix('reorder')->middleware('plan.feature:reorder_suggestions')->group(function () {
        Route::get('/suggestions', [ReorderSuggestionController::class, 'index'])->middleware('throttle:read');
        Route::post('/suggestions/generate', [ReorderSuggestionController::class, 'generate'])->middleware('throttle:critical');
        Route::post('/suggestions/{id}/create-purchase-order', [ReorderSuggestionController::class, 'createPurchaseOrder'])->middleware('throttle:critical');
        Route::patch('/suggestions/{id}/dismiss', [ReorderSuggestionController::class, 'dismiss'])->middleware('throttle:critical');
    });

    // Dashboard Executivo (P1)
    Route::get('/dashboard/executive', [ExecutiveDashboardController::class, 'index'])
        ->middleware(['throttle:read', 'plan.feature:executive_dashboard']);

    // Pedidos de Compra
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'index'])->middleware(['acl.permission:purchase-orders.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'store'])->middleware(['acl.permission:purchase-orders.store', 'throttle:critical']);
        Route::get('/{id}', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'show'])->middleware(['acl.permission:purchase-orders.index', 'throttle:read']);
        Route::put('/{id}', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'update'])->middleware(['acl.permission:purchase-orders.update', 'throttle:critical']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'destroy'])->middleware(['acl.permission:purchase-orders.destroy', 'throttle:critical']);
        Route::post('/{id}/advance-status', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'advanceStatus'])->middleware(['acl.permission:purchase-orders.update', 'throttle:critical']);
        Route::post('/{id}/receive', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'receive'])->middleware(['acl.permission:purchase-orders.update', 'throttle:critical']);
    });

    // Armazéns
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\WarehouseApiController::class, 'index'])->middleware(['acl.permission:warehouses.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\WarehouseApiController::class, 'store'])->middleware(['acl.permission:warehouses.store', 'throttle:critical']);
        Route::get('/{id}', [\App\Http\Controllers\Api\WarehouseApiController::class, 'show'])->middleware(['acl.permission:warehouses.index', 'throttle:read']);
        Route::put('/{id}', [\App\Http\Controllers\Api\WarehouseApiController::class, 'update'])->middleware(['acl.permission:warehouses.update', 'throttle:critical']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\WarehouseApiController::class, 'destroy'])->middleware(['acl.permission:warehouses.destroy', 'throttle:critical']);
    });

    // Lotes (FEFO)
    Route::prefix('batches')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\BatchApiController::class, 'index'])->middleware(['acl.permission:batches.index', 'throttle:read']);
        Route::get('/expiring', [\App\Http\Controllers\Api\BatchApiController::class, 'expiring'])->middleware(['acl.permission:batches.index', 'throttle:read']);
        Route::get('/expired', [\App\Http\Controllers\Api\BatchApiController::class, 'expired'])->middleware(['acl.permission:batches.index', 'throttle:read']);
        Route::get('/{id}', [\App\Http\Controllers\Api\BatchApiController::class, 'show'])->middleware(['acl.permission:batches.index', 'throttle:read']);
        Route::post('/{id}/quarantine', [\App\Http\Controllers\Api\BatchApiController::class, 'quarantine'])->middleware(['acl.permission:batches.quarantine', 'throttle:critical']);
        Route::post('/{id}/recall', [\App\Http\Controllers\Api\BatchApiController::class, 'recall'])->middleware(['acl.permission:batches.recall', 'throttle:critical']);
    });

    // Movimentações de Estoque
    Route::prefix('stock-movements')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StockMovementApiController::class, 'index'])->middleware(['acl.permission:stock-movements.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\StockMovementApiController::class, 'store'])->middleware(['acl.permission:stock-movements.store', 'throttle:critical']);
        Route::get('/{id}', [\App\Http\Controllers\Api\StockMovementApiController::class, 'show'])->middleware(['acl.permission:stock-movements.index', 'throttle:read']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\StockMovementApiController::class, 'destroy'])->middleware(['acl.permission:stock-movements.store', 'throttle:critical']);
    });

    // Produtos — estoque crítico
    Route::get('/products/low-stock', [\App\Http\Controllers\Api\ProductLowStockController::class, 'index'])->middleware('throttle:read');

    // Logística — Romaneios / Expedição
    Route::prefix('shipments')->group(function () {
        Route::get('/pending-orders', [\App\Http\Controllers\Api\ShipmentApiController::class, 'pendingOrders'])->middleware(['acl.permission:shipments.index', 'throttle:read']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ShipmentApiController::class, 'show'])->middleware(['acl.permission:shipments.index', 'throttle:read']);
        Route::get('/', [\App\Http\Controllers\Api\ShipmentApiController::class, 'index'])->middleware(['acl.permission:shipments.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\ShipmentApiController::class, 'store'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
        Route::put('/{id}', [\App\Http\Controllers\Api\ShipmentApiController::class, 'update'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
        Route::post('/{id}/dispatch', [\App\Http\Controllers\Api\ShipmentApiController::class, 'dispatch'])->middleware(['acl.permission:shipments.dispatch', 'throttle:critical']);
        Route::post('/{id}/send-delivery-link', [\App\Http\Controllers\Api\ShipmentApiController::class, 'sendDeliveryLink'])->middleware(['acl.permission:shipments.dispatch', 'throttle:critical']);
        Route::post('/{id}/deliver', [\App\Http\Controllers\Api\ShipmentApiController::class, 'deliver'])->middleware(['acl.permission:shipments.deliver', 'throttle:critical']);
        Route::post('/{id}/occurrences', [\App\Http\Controllers\Api\ShipmentApiController::class, 'storeOccurrence'])->middleware(['acl.permission:shipments.dispatch', 'throttle:critical']);
        Route::get('/{id}/pdf', [\App\Http\Controllers\Api\ShipmentPdfController::class, 'show'])->middleware(['acl.permission:shipments.index', 'throttle:read']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\ShipmentApiController::class, 'destroy'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
    });

    // Logística — Veículos
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VehicleApiController::class, 'index'])->middleware(['acl.permission:shipments.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\VehicleApiController::class, 'store'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
        Route::put('/{id}', [\App\Http\Controllers\Api\VehicleApiController::class, 'update'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\VehicleApiController::class, 'destroy'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
    });

    // Logística — Motoristas
    Route::prefix('drivers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DriverApiController::class, 'index'])->middleware(['acl.permission:shipments.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\DriverApiController::class, 'store'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
        Route::put('/{id}', [\App\Http\Controllers\Api\DriverApiController::class, 'update'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\DriverApiController::class, 'destroy'])->middleware(['acl.permission:shipments.store', 'throttle:critical']);
    });

    // Logística — Métricas e KPIs
    Route::get('/logistics/metrics', [\App\Http\Controllers\Api\LogisticsMetricsController::class, 'index'])
        ->middleware(['acl.permission:shipments.index', 'throttle:read']);

    // Logística — Roteirização de Entregas
    Route::prefix('deliveries')->middleware('plan.feature:delivery_routing')->group(function () {
        Route::get('/suggest-groups', [\App\Http\Controllers\Api\DeliveryRouteController::class, 'suggestGroups'])->middleware('throttle:read');
        Route::post('/{shipmentId}/optimize-route', [\App\Http\Controllers\Api\DeliveryRouteController::class, 'optimizeRoute'])->middleware('throttle:critical');
        Route::get('/{shipmentId}/cost', [\App\Http\Controllers\Api\DeliveryRouteController::class, 'calculateCost'])->middleware('throttle:read');
        Route::patch('/{shipmentId}/orders/{orderId}/window', [\App\Http\Controllers\Api\DeliveryRouteController::class, 'setDeliveryWindow'])->middleware('throttle:critical');
    });

    // Inventário cíclico
    Route::prefix('inventory-counts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\InventoryCountApiController::class, 'index'])->middleware(['acl.permission:inventory-counts.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\InventoryCountApiController::class, 'store'])->middleware(['acl.permission:inventory-counts.store', 'throttle:critical']);
        Route::post('/{id}/items', [\App\Http\Controllers\Api\InventoryCountApiController::class, 'addItem'])->middleware(['acl.permission:inventory-counts.update', 'throttle:critical']);
        Route::post('/{id}/close', [\App\Http\Controllers\Api\InventoryCountApiController::class, 'close'])->middleware(['acl.permission:inventory-counts.update', 'throttle:critical']);
    });

    // Auditoria — Logs de alterações
    Route::get('/audit-logs', [\App\Http\Controllers\Api\AuditLogApiController::class, 'index'])->middleware(['acl.permission:audit-logs.index', 'throttle:read']);

    // Auditoria — Histórico de preços
    Route::prefix('price-history')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PriceHistoryController::class, 'index'])->middleware(['acl.permission:audit-logs.index', 'throttle:read']);
        Route::get('/volatility', [\App\Http\Controllers\Api\PriceHistoryController::class, 'volatility'])->middleware(['acl.permission:audit-logs.index', 'throttle:read']);
        Route::get('/products/{productId}', [\App\Http\Controllers\Api\PriceHistoryController::class, 'product'])->middleware(['acl.permission:audit-logs.index', 'throttle:read']);
    });

    // Auditoria — Aprovações de desconto
    Route::prefix('discount-approvals')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DiscountApprovalController::class, 'index'])->middleware(['acl.permission:discount-approvals.index', 'throttle:read']);
        Route::get('/pending-count', [\App\Http\Controllers\Api\DiscountApprovalController::class, 'pendingCount'])->middleware('throttle:read');
        Route::patch('/{id}/approve', [\App\Http\Controllers\Api\DiscountApprovalController::class, 'approve'])->middleware(['acl.permission:discount-approvals.approve', 'throttle:critical']);
        Route::patch('/{id}/reject', [\App\Http\Controllers\Api\DiscountApprovalController::class, 'reject'])->middleware(['acl.permission:discount-approvals.approve', 'throttle:critical']);
    });

    // Pedido de aprovação de desconto (a partir do pedido de venda)
    Route::post('/sale-orders/{saleOrderId}/request-discount-approval', [\App\Http\Controllers\Api\DiscountApprovalController::class, 'requestApproval'])->middleware('throttle:critical');

    // Sazonalidade — Datas comemorativas
    Route::prefix('seasonal')->group(function () {
        // Alertas automáticos
        Route::get('/alerts', [\App\Http\Controllers\Api\SeasonalAlertsController::class, 'index'])->middleware('throttle:read');
        Route::get('/alerts/summary', [\App\Http\Controllers\Api\SeasonalAlertsController::class, 'summary'])->middleware('throttle:read');
        Route::post('/alerts/generate', [\App\Http\Controllers\Api\SeasonalAlertsController::class, 'generate'])->middleware('throttle:critical');
        Route::patch('/alerts/{id}/acknowledge', [\App\Http\Controllers\Api\SeasonalAlertsController::class, 'acknowledge'])->middleware('throttle:critical');
        Route::post('/alerts/acknowledge-all', [\App\Http\Controllers\Api\SeasonalAlertsController::class, 'acknowledgeAll'])->middleware('throttle:critical');

        // Tendências de consumo e sazonalidade
        Route::get('/trends', [\App\Http\Controllers\Api\SeasonalAlertsController::class, 'trends'])->middleware('throttle:read');
        Route::get('/trends/month/{month}', [\App\Http\Controllers\Api\SeasonalAlertsController::class, 'monthDemand'])->middleware('throttle:read');

        // Calendário de datas comemorativas
        Route::get('/holidays', [\App\Http\Controllers\Api\HolidayCalendarController::class, 'upcoming'])->middleware('throttle:read');
        Route::get('/holidays/all', [\App\Http\Controllers\Api\HolidayCalendarController::class, 'index'])->middleware('throttle:read');
        Route::post('/holidays', [\App\Http\Controllers\Api\HolidayCalendarController::class, 'store'])->middleware('throttle:critical');
        Route::put('/holidays/{id}', [\App\Http\Controllers\Api\HolidayCalendarController::class, 'update'])->middleware('throttle:critical');
        Route::delete('/holidays/{id}', [\App\Http\Controllers\Api\HolidayCalendarController::class, 'destroy'])->middleware('throttle:critical');
    });

    // Integração fiscal terceira (sem emissão SEFAZ)
    Route::prefix('settings/fiscal')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\FiscalIntegrationApiController::class, 'show'])->middleware('throttle:read');
        Route::put('/', [\App\Http\Controllers\Api\FiscalIntegrationApiController::class, 'update'])->middleware(['acl.permission:settings.fiscal.update', 'throttle:critical']);
    });

    // Inutilização de faixa de NF-e
    Route::post('/fiscal/nfe/void-range', [FiscalCorrectionController::class, 'voidRange'])
        ->middleware(['acl.permission:sale-orders.fiscal.request', 'plan.feature:nfe_correction', 'throttle:critical']);

    // Tabelas de Preço
    Route::prefix('price-tables')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PriceTableApiController::class, 'index'])->middleware(['acl.permission:price-tables.index', 'throttle:read']);
        Route::post('/', [\App\Http\Controllers\Api\PriceTableApiController::class, 'store'])->middleware(['acl.permission:price-tables.store', 'throttle:critical']);
        Route::get('/lookup', [\App\Http\Controllers\Api\PriceTableApiController::class, 'lookup'])->middleware(['acl.permission:price-tables.index', 'throttle:read']);
        Route::get('/{id}', [\App\Http\Controllers\Api\PriceTableApiController::class, 'show'])->middleware(['acl.permission:price-tables.index', 'throttle:read']);
        Route::put('/{id}', [\App\Http\Controllers\Api\PriceTableApiController::class, 'update'])->middleware(['acl.permission:price-tables.update', 'throttle:critical']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\PriceTableApiController::class, 'destroy'])->middleware(['acl.permission:price-tables.destroy', 'throttle:critical']);
    });
});

// ============================================================================
// REPORTS ROUTES (AUTHENTICATED)
// ============================================================================

Route::prefix('reports')->middleware(['auth:api', 'tenant.blocked', 'trial.check', 'plan.feature:reports'])->group(function () {
    // Lista relatórios disponíveis
    Route::get('/', [ReportController::class, 'index']);
    
    // Relatório de Vendas Diário
    Route::post('/daily-sales', [ReportController::class, 'dailySales']);
    
    // Relatório de Clientes
    Route::post('/clients', [ReportController::class, 'clients']);
    
    // Relatório de Produtos Mais Vendidos
    Route::post('/top-products', [ReportController::class, 'topProducts']);
    
    // Relatório Financeiro Mensal
    Route::post('/monthly-financial', [ReportController::class, 'monthlyFinancial']);
    
    // Relatório de Ocupação de Mesas
    Route::post('/table-occupancy', [ReportController::class, 'tableOccupancy']);

    // Relatórios DistribTec (B2B)
    Route::get('/sales', [DistribtecReportController::class, 'salesPreview']);
    Route::post('/sales', [DistribtecReportController::class, 'salesExport']);
    Route::get('/sales-by-client', [DistribtecReportController::class, 'salesByClientPreview']);
    Route::post('/sales-by-client', [DistribtecReportController::class, 'salesByClientExport']);
    Route::get('/abc-products', [DistribtecReportController::class, 'abcProductsPreview']);
    Route::post('/abc-products', [DistribtecReportController::class, 'abcProductsExport']);
    Route::get('/stock-turnover', [DistribtecReportController::class, 'stockTurnoverPreview']);
    Route::post('/stock-turnover', [DistribtecReportController::class, 'stockTurnoverExport']);

    // Fluxo de Caixa Projetado (P0)
    Route::get('/cash-flow', [CashFlowReportController::class, 'index'])
        ->middleware('plan.feature:cash_flow_report');

    // Aging / Inadimplência (P0)
    Route::get('/aging', [AgingReportController::class, 'index'])
        ->middleware('plan.feature:aging_report');

    // Curva ABC de Clientes e Fornecedores (P1)
    Route::get('/abc-clients', [AbcClientsReportController::class, 'index'])
        ->middleware('plan.feature:abc_clients');
    Route::post('/abc-clients', [AbcClientsReportController::class, 'export'])
        ->middleware('plan.feature:abc_clients');

    Route::get('/abc-suppliers', [AbcSuppliersReportController::class, 'index'])
        ->middleware('plan.feature:abc_suppliers');
    Route::post('/abc-suppliers', [AbcSuppliersReportController::class, 'export'])
        ->middleware('plan.feature:abc_suppliers');
});

// ============================================================================
// PUBLIC STORE ROUTES (NO AUTHENTICATION REQUIRED)
// ============================================================================

Route::prefix('store/{slug}')->group(function () {
    // Get store information
    Route::get('/info', [PublicStoreController::class, 'getStoreInfo']);
    
    // Get store products
    Route::get('/products', [PublicStoreController::class, 'getProducts']);
    
    // Get store payment methods
    Route::get('/payment-methods', [PublicStoreController::class, 'getPaymentMethods']);
    
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

    // Lookup existing client by CPF or phone (autofill checkout)
    Route::get('/clients/lookup', [PublicStoreController::class, 'lookupClient'])
        ->middleware('throttle:20,1');
    
    // Track order (public consultation by CPF or phone)
    Route::get('/orders/track', [PublicStoreController::class, 'trackOrder'])
        ->middleware('throttle:10,1'); // 10 requests per minute
    
    // Public Reviews (read-only)
    Route::get('/reviews', [ReviewApiController::class, 'publicIndex']); // Lista aprovadas
    Route::get('/reviews/featured', [ReviewApiController::class, 'publicFeatured']); // Em destaque
    Route::get('/reviews/stats', [ReviewApiController::class, 'publicStats']); // Estatísticas
});

// Public Reviews (create - outside store prefix to avoid slug duplication)
Route::prefix('public')->group(function () {
    Route::post('/reviews', [ReviewApiController::class, 'store']) // Criar avaliação
        ->middleware('throttle:3,10'); // 3 reviews per 10 minutes
    
    Route::post('/reviews/{uuid}/helpful', [ReviewApiController::class, 'incrementHelpful']) // Marcar como útil
        ->middleware('throttle:5,1'); // 5 per minute
});

// ============================================================================
// REVIEWS MANAGEMENT (AUTHENTICATED USERS - ADMIN)
// ============================================================================

Route::prefix('reviews')->middleware(['auth:api', 'tenant.blocked', 'trial.check'])->group(function () {
    // List & Stats
    Route::get('/', [ReviewApiController::class, 'index'])->middleware('throttle:read'); // Lista com filtros
    Route::get('/pending', [ReviewApiController::class, 'pending'])->middleware('throttle:read'); // Apenas pendentes
    Route::get('/stats', [ReviewApiController::class, 'stats'])->middleware('throttle:read'); // Estatísticas
    Route::get('/recent', [ReviewApiController::class, 'recent'])->middleware('throttle:read'); // Recentes (dashboard)
    Route::get('/{uuid}', [ReviewApiController::class, 'show'])->middleware('throttle:read'); // Ver uma específica
    
    // Moderation Actions
    Route::post('/{uuid}/approve', [ReviewApiController::class, 'approve'])->middleware('throttle:critical'); // Aprovar
    Route::post('/{uuid}/reject', [ReviewApiController::class, 'reject'])->middleware('throttle:critical'); // Rejeitar
    Route::post('/{uuid}/toggle-featured', [ReviewApiController::class, 'toggleFeatured'])->middleware('throttle:critical'); // Toggle destaque
    
    // Delete
    Route::delete('/{uuid}', [ReviewApiController::class, 'destroy'])->middleware('throttle:critical'); // Deletar
});

// ============================================================================
// ADMIN ROUTES (ADMIN AUTHENTICATION REQUIRED)
// ============================================================================

use App\Http\Controllers\Api\Admin\AdminPlanLimitController;
use App\Http\Controllers\Api\Admin\AdminPlanMigrationController;
use App\Http\Controllers\Api\Admin\AdminPlanController;
use App\Http\Controllers\Api\Admin\AdminFeatureDefinitionController;
use App\Http\Controllers\Api\Admin\{
    AdminAuthController,
    AdminDashboardController,
    AdminTenantController,
    AdminMetricsController,
    AdminEmailController,
    AdminProfileController
};

// Admin Authentication (no middleware)
Route::prefix('admin/auth')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:login');
});

// Admin Protected Routes
Route::prefix('admin')->middleware(['admin.auth', 'admin.log'])->group(function () {
    // Auth
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
    Route::get('/auth/me', [AdminAuthController::class, 'me']);
    Route::post('/auth/refresh', [AdminAuthController::class, 'refresh']);

    // Profile
    Route::put('/auth/profile', [AdminProfileController::class, 'update']);
    Route::put('/auth/password', [AdminProfileController::class, 'updatePassword']);
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard/activity', [AdminDashboardController::class, 'recentActivity']);
    Route::get('/dashboard/alerts', [AdminDashboardController::class, 'alerts']);
    
    // Tenants Management
    Route::prefix('tenants')->group(function () {
        Route::get('/', [AdminTenantController::class, 'index'])
            ->middleware('admin.permission:tenants.view');
        Route::get('/{id}', [AdminTenantController::class, 'show'])
            ->middleware('admin.permission:tenants.view');
        Route::put('/{id}', [AdminTenantController::class, 'update'])
            ->middleware('admin.permission:tenants.manage');
        Route::post('/{id}/activate', [AdminTenantController::class, 'activate'])
            ->middleware('admin.permission:tenants.manage');
        Route::post('/{id}/suspend', [AdminTenantController::class, 'suspend'])
            ->middleware('admin.permission:tenants.manage');
        Route::post('/{id}/block', [AdminTenantController::class, 'block'])
            ->middleware('admin.permission:tenants.manage');
        Route::post('/{id}/unblock', [AdminTenantController::class, 'unblock'])
            ->middleware('admin.permission:tenants.manage');
        Route::put('/{id}/plan', [AdminTenantController::class, 'updatePlan'])
            ->middleware('admin.permission:tenants.manage');
        Route::post('/{id}/pause-access', [AdminTenantController::class, 'pauseAccess'])
            ->middleware('admin.permission:tenants.manage');
        Route::post('/{id}/restore-access', [AdminTenantController::class, 'restoreAccess'])
            ->middleware('admin.permission:tenants.manage');
        Route::delete('/{id}', [AdminTenantController::class, 'destroy'])
            ->middleware('admin.permission:tenants.manage');
        Route::delete('/{id}/force', [AdminTenantController::class, 'forceDestroy'])
            ->middleware('admin.permission:tenants.manage');
    });
    
    // Metrics
    Route::prefix('metrics')->middleware('admin.permission:metrics.view')->group(function () {
        Route::get('/revenue', [AdminMetricsController::class, 'revenue']);
        Route::get('/usage', [AdminMetricsController::class, 'usage']);
        Route::get('/messages', [AdminMetricsController::class, 'messages']);
        Route::get('/growth', [AdminMetricsController::class, 'growth']);
        Route::get('/tenant/{tenantId}', [AdminMetricsController::class, 'tenantMetrics']);
    });
    
    // Plans Management
    Route::prefix('plans')->middleware('admin.permission:tenants.manage')->group(function () {
        Route::get('/', [AdminPlanController::class, 'index']);
        Route::get('/{id}', [AdminPlanController::class, 'show']);
        Route::post('/', [AdminPlanController::class, 'store']);
        Route::put('/{id}', [AdminPlanController::class, 'update']);
        Route::delete('/{id}', [AdminPlanController::class, 'delete']);
        Route::get('/{id}/features', [AdminFeatureDefinitionController::class, 'planFeatures']);
        Route::post('/{id}/features/sync', [AdminFeatureDefinitionController::class, 'syncPlanFeatures']);
    });

    // Feature Definitions (global catalog)
    Route::prefix('feature-definitions')->middleware('admin.permission:tenants.manage')->group(function () {
        Route::get('/', [AdminFeatureDefinitionController::class, 'index']);
        Route::post('/', [AdminFeatureDefinitionController::class, 'store']);
        Route::put('/{id}', [AdminFeatureDefinitionController::class, 'update']);
        Route::delete('/{id}', [AdminFeatureDefinitionController::class, 'destroy']);
    });

    // Plan Limits
    Route::prefix('plan-limits')->middleware('admin.permission:tenants.view')->group(function () {
        Route::get('/tenants-near-limit', [AdminPlanLimitController::class, 'tenantsNearLimit']);
        Route::get('/tenants-at-limit', [AdminPlanLimitController::class, 'tenantsAtLimit']);
        Route::get('/usage-report', [AdminPlanLimitController::class, 'usageReport']);
        Route::get('/plan-distribution', [AdminPlanLimitController::class, 'planDistribution']);
        Route::get('/tenant/{tenantId}/limits', [AdminPlanLimitController::class, 'tenantLimits']);
    });
    
    // Plan Migrations
    Route::prefix('plan-migrations')->middleware('admin.permission:tenants.manage')->group(function () {
        Route::post('/migrate', [AdminPlanMigrationController::class, 'migrate']);
        Route::get('/history', [AdminPlanMigrationController::class, 'history']);
        Route::post('/bulk-migrate', [AdminPlanMigrationController::class, 'bulkMigrate']);
    });
    
    // Email
    Route::prefix('email')->middleware('admin.permission:tenants.manage')->group(function () {
        Route::post('/send-bulk', [AdminEmailController::class, 'sendBulkEmail']);
        Route::get('/history', [AdminEmailController::class, 'emailHistory']);
        Route::get('/newsletter-subscribers', [AdminEmailController::class, 'newsletterSubscribers']);
    });

    // Subscription Management (v2)
    Route::prefix('subscriptions')->middleware('admin.permission:tenants.manage')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\Admin\AdminSubscriptionController::class, 'stats']);
        Route::get('/dunning', [\App\Http\Controllers\Api\Admin\AdminSubscriptionController::class, 'dunningList']);
        Route::get('/cancellation-pending', [\App\Http\Controllers\Api\Admin\AdminSubscriptionController::class, 'cancellationPendingList']);
        Route::get('/tenants/{id}/audit', [\App\Http\Controllers\Api\Admin\AdminSubscriptionController::class, 'auditLog']);
        Route::post('/tenants/{id}/override-status', [\App\Http\Controllers\Api\Admin\AdminSubscriptionController::class, 'overrideStatus']);
        Route::post('/tenants/{id}/finalize-cancellation', [\App\Http\Controllers\Api\Admin\AdminSubscriptionController::class, 'finalizeCancellation']);
        Route::post('/tenants/{id}/trigger-dunning', [\App\Http\Controllers\Api\Admin\AdminSubscriptionController::class, 'triggerDunning']);
    });
});
