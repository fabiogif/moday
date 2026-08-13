<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\Api\OfflineSyncRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Table;
use App\Services\AuthTenantService;
use App\Services\OfflineSyncService;
use App\Services\OrderService;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OfflineSyncController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected AuthTenantService $authTenantService,
        protected OfflineSyncService $offlineSyncService,
    ) {}

    /**
     * Returns a snapshot of products, categories and tables for offline use.
     * Supports incremental sync via ?updated_after=<timestamp_ms>
     */
    public function snapshot(Request $request): JsonResponse
    {
        $tenantId     = Auth::user()->tenant_id;
        $updatedAfter = $request->input('updated_after')
            ? \Carbon\Carbon::createFromTimestampMs($request->integer('updated_after'))
            : null;

        $productQuery  = Product::where('tenant_id', $tenantId)->whereNull('deleted_at');
        $categoryQuery = Category::where('tenant_id', $tenantId);
        $tableQuery    = Table::where('tenant_id', $tenantId);

        if ($updatedAfter) {
            $productQuery->where('updated_at', '>=', $updatedAfter);
            $categoryQuery->where('updated_at', '>=', $updatedAfter);
            $tableQuery->where('updated_at', '>=', $updatedAfter);
        }

        $products   = $productQuery->get(['id', 'name', 'price', 'image', 'is_active', 'updated_at']);
        $categories = $categoryQuery->orderBy('sort_order')->get(['id', 'name', 'sort_order']);
        $tables     = $tableQuery->get(['id', 'name', 'status']);

        return response()->json([
            'products' => $products->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'price'       => (float) $p->price,
                'category_id' => null,
                'image_url'   => $p->image ? asset('storage/' . $p->image) : null,
                'is_active'   => (bool) $p->is_active,
            ]),
            'categories' => $categories->map(fn ($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'sort_order' => $c->sort_order ?? 0,
            ]),
            'tables' => $tables->map(fn ($t) => [
                'id'      => $t->id,
                'name'    => $t->name,
                'section' => null,
                'status'  => $t->status ?? 'free',
            ]),
            'timestamp' => now()->timestampMs(),
        ]);
    }

    /** POST /api/offline/sync */
    public function sync(OfflineSyncRequest $request): JsonResponse
    {
        [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

        $results = $this->offlineSyncService->syncOrders($tenantId, $user->id, $request->orders);
        $synced  = $this->offlineSyncService->countSynced($results);

        return ApiResponseClass::sendResponse([
            'synced'  => $synced,
            'failed'  => count($results) - $synced,
            'results' => $results,
        ], "{$synced} pedido(s) sincronizado(s)", 200);
    }

    /** GET /api/offline/status */
    public function status(): JsonResponse
    {
        [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

        return ApiResponseClass::sendResponse([
            'online'    => true,
            'tenant_id' => $tenantId,
            'user_id'   => $user->id,
            'timestamp' => now()->toISOString(),
        ], 'OK', 200);
    }

    /**
     * Receives a batch of offline orders and persists them (legacy POS endpoint).
     */
    public function pushOrders(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $orders   = $request->input('orders', []);
        $results  = [];

        foreach ($orders as $orderData) {
            $localId = $orderData['local_id'] ?? null;

            try {
                $result = DB::transaction(function () use ($orderData, $tenantId) {
                    $tableId = $orderData['table_id'] ?? null;

                    if ($tableId) {
                        $table = Table::where('id', $tableId)->where('tenant_id', $tenantId)->first();

                        if ($table && $table->status === 'occupied') {
                            return ['success' => false, 'error' => 'Mesa já está ocupada.', 'conflict' => 'table_occupied'];
                        }
                    }

                    $order = Order::create([
                        'uuid'       => Str::uuid(),
                        'tenant_id'  => $tenantId,
                        'table_id'   => $tableId,
                        'status'     => 'pending',
                        'total'      => $orderData['total'] ?? 0,
                        'notes'      => $orderData['notes'] ?? null,
                        'created_at' => isset($orderData['created_at'])
                            ? \Carbon\Carbon::createFromTimestampMs($orderData['created_at'])
                            : now(),
                    ]);

                    foreach ($orderData['items'] ?? [] as $item) {
                        $product = Product::find($item['product_id']);

                        if ($product && !$product->is_active) {
                            Log::info('Offline order: inactive product included', [
                                'product_id' => $item['product_id'],
                                'order_id'   => $order->id,
                            ]);
                        }

                        OrderProduct::create([
                            'order_id'   => $order->id,
                            'product_id' => $item['product_id'],
                            'quantity'   => $item['quantity'],
                            'price'      => $item['unit_price'],
                            'notes'      => $item['notes'] ?? null,
                        ]);
                    }

                    return ['success' => true, 'remote_id' => (string) $order->id];
                });

                $results[] = array_merge(['local_id' => $localId], $result);
            } catch (\Exception $e) {
                Log::error('Offline order push failed', ['local_id' => $localId, 'error' => $e->getMessage()]);
                $results[] = ['local_id' => $localId, 'success' => false, 'error' => 'Erro interno ao processar pedido.'];
            }
        }

        return response()->json(['results' => $results]);
    }
}
