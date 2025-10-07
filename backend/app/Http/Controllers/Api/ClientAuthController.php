<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientAuthController extends Controller
{
    /**
     * Register a new client
     */
    public function register(Request $request, string $slug): JsonResponse
    {
        try {
            // Find tenant by slug
            $tenant = Tenant::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loja não encontrada'
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:6|confirmed',
                'phone' => 'required|string|max:20',
                'cpf' => 'nullable|string|max:14',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if email already exists for this tenant
            $existingClient = Client::where('email', $request->email)
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($existingClient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email já cadastrado nesta loja'
                ], 422);
            }

            // Create client
            $client = Client::create([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'cpf' => $request->cpf,
                'is_active' => true,
            ]);

            // Generate JWT token
            $token = JWTAuth::fromUser($client);

            return response()->json([
                'success' => true,
                'message' => 'Cliente registrado com sucesso',
                'data' => [
                    'client' => [
                        'uuid' => $client->uuid,
                        'name' => $client->name,
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'cpf' => $client->cpf,
                    ],
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login client
     */
    public function login(Request $request, string $slug): JsonResponse
    {
        try {
            // Find tenant by slug
            $tenant = Tenant::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loja não encontrada'
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find client
            $client = Client::where('email', $request->email)
                ->where('tenant_id', $tenant->id)
                ->first();

            if (!$client || !Hash::check($request->password, $client->password ?? '')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou senha incorretos'
                ], 401);
            }

            // Check if client is active
            if (!$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conta desativada. Entre em contato com a loja.'
                ], 403);
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($client);

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'data' => [
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
                    'expires_in' => config('jwt.ttl') * 60
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer login',
                'error' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não autenticado'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
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
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar dados do cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout client
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::parseToken()->invalidate();

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer logout',
                'error' => $e->getMessage()
            ], 500);
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
            $orders = \App\Models\Order::where('client_id', $client->id)
                ->with(['products', 'table'])
                ->orderBy('created_at', 'desc')
                ->get()
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
                            return [
                                'uuid' => $product->uuid,
                                'name' => $product->name,
                                'price' => (float) $product->pivot->price,
                                'quantity' => (int) $product->pivot->qty,
                                'subtotal' => (float) $product->pivot->price * (int) $product->pivot->qty,
                                'image' => $product->url ?? $product->image,
                            ];
                        }),
                        'table' => $order->table ? [
                            'name' => $order->table->name,
                            'uuid' => $order->table->uuid,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'total_orders' => $orders->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar pedidos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

