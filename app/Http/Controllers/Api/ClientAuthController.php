<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClientLoginRequest;
use App\Http\Requests\Api\ClientRegisterRequest;
use App\Services\ClientAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientAuthController extends Controller
{
    public function __construct(private readonly ClientAuthService $authService) {}

    /**
     * Register a new client
     */
    public function register(ClientRegisterRequest $request, string $slug): JsonResponse
    {
        try {
            $tenant = $this->authService->findActiveTenantBySlug($slug);

            if (!$tenant) {
                return ApiResponseClass::sendResponse('', 'Loja não encontrada', 404);
            }

            if ($this->authService->emailExistsForTenant($request->email, $tenant->id)) {
                return ApiResponseClass::validationError(['email' => ['Email já cadastrado nesta loja']], 'Dados inválidos');
            }

            [$client, $token] = $this->authService->registerClient($request->validated(), $tenant);

            $ttlMinutes = config('jwt.ttl', 120);
            $cookie = cookie('client_auth_token', $token, $ttlMinutes, '/', null, true, true, false, 'strict');

            return ApiResponseClass::sendResponse([
                'client' => [
                    'uuid' => $client->uuid,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'cpf' => $client->cpf,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttlMinutes * 60
            ], 'Cliente registrado com sucesso', 201)->withCookie($cookie);

        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao registrar cliente');
        }
    }

    /**
     * Login client
     */
    public function login(ClientLoginRequest $request, string $slug): JsonResponse
    {
        try {
            $tenant = $this->authService->findActiveTenantBySlug($slug);

            if (!$tenant) {
                return ApiResponseClass::sendResponse('', 'Loja não encontrada', 404);
            }
            $client = $this->authService->authenticate($request->email, $request->password, $tenant);
            if (!$client) {
                return ApiResponseClass::sendResponse('', 'Email ou senha incorretos', 401);
            }

            $token = $this->authService->generateTokenForClient($client);

            $ttlMinutes = config('jwt.ttl', 120);
            $cookie = cookie('client_auth_token', $token, $ttlMinutes, '/', null, true, true, false, 'strict');

            return ApiResponseClass::sendResponse([
                'client' => [
                    'uuid' => $client->uuid,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'cpf' => $client->cpf,
                    'address' => $client->address,
                    'city' => $client->city,
                    'state' => $client->state,
                    'zip_code' => $client->zip_code,
                    'neighborhood' => $client->neighborhood,
                    'number' => $client->number,
                    'complement' => $client->complement,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttlMinutes * 60
            ], 'Login realizado com sucesso', 200)->withCookie($cookie);

        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao fazer login');
        }
    }

    /**
     * Get authenticated client info
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $client = JWTAuth::parseToken()->authenticate();

            if (!$client) {
                return ApiResponseClass::sendResponse('', 'Cliente não autenticado', 401);
            }

            return ApiResponseClass::sendResponse([
                'uuid' => $client->uuid,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'cpf' => $client->cpf,
                'address' => $client->address,
                'city' => $client->city,
                'state' => $client->state,
                'zip_code' => $client->zip_code,
                'neighborhood' => $client->neighborhood,
                'number' => $client->number,
                'complement' => $client->complement,
            ], 'Dados do cliente carregados com sucesso', 200);

        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar dados do cliente');
        }
    }

    /**
     * Logout client
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::parseToken()->invalidate();

            $cookie = cookie('client_auth_token', '', -1, '/', null, true, true, false, 'strict');

            return ApiResponseClass::sendResponse('', 'Logout realizado com sucesso', 200)
                ->withCookie($cookie);

        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao fazer logout');
        }
    }

    /**
     * Get client's orders
     */
    public function getOrders(Request $request): JsonResponse
    {
        try {
            $client = JWTAuth::parseToken()->authenticate();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não autenticado'
                ], 401);
            }

            // Get orders with products
            $orders = $this->authService->getClientOrders($client)
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'identify' => $order->identify,
                        'total' => (float) $order->total,
                        'formatted_total' => 'R$ ' . number_format($order->total, 2, ',', '.'),
                        'status' => $order->status,
                        'origin' => $order->origin,
                        'is_delivery' => $order->is_delivery,
                        'delivery_address' => $order->delivery_address,
                        'delivery_city' => $order->delivery_city,
                        'delivery_state' => $order->delivery_state,
                        'payment_method' => $order->payment_method,
                        'shipping_method' => $order->shipping_method,
                        'created_at' => $order->created_at->format('d/m/Y H:i'),
                        'created_at_human' => $order->created_at->diffForHumans(),
                        'products' => $order->products->map(function ($product) {
                            $imageUrl = \App\Helpers\ImageHelper::publicAssetPath($product->image, 'products');
                            
                            return [
                                'uuid' => $product->uuid,
                                'name' => $product->name,
                                'price' => (float) $product->pivot->price,
                                'quantity' => (int) $product->pivot->qty,
                                'subtotal' => (float) $product->pivot->price * (int) $product->pivot->qty,
                                'image' => $imageUrl,
                            ];
                        }),
                        'table' => $order->table ? [
                            'name' => $order->table->name,
                            'uuid' => $order->table->uuid,
                        ] : null,
                    ];
                });

            return ApiResponseClass::sendResponse([
                'orders' => $orders,
                'total_orders' => $orders->count(),
            ], 'Pedidos carregados com sucesso', 200);

        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar pedidos');
        }
    }
}

